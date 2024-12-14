class MahoTree {
    constructor(container, config = {}) {
        const containerEl = document.getElementById(container);
        if (!containerEl) {
            throw new Error(`Element with ID ${container} not found in DOM`);
        }

        this.nodeDataMap = new WeakMap();

        this.config = Object.assign({
            selectable: false, // radio, single, simple, nested (true)
            sortable: false, // true or object

            rootVisible: true,
            iconFolder: 'folder',
            iconLeaf: 'leaf',
            cssVars: {},
        }, config);

        this.selectableOpts = {
            mode: this.config.selectable === true ? 'nested' : this.config.selectable,
            hideInputs: false,
            radioName: [...Array(6)].map(() => Math.random().toString(36)[2]).join(''),
        }

        this.sortableOpts = {
	    group: this.config.sortable === true ? 'sortable' : this.config.sortable,
	    animation: 150,
	    fallbackOnBody: true,
	    swapThreshold: 0.65,

            containDepth: false,
        };

        if (typeof this.config.selectable === 'object' && this.config.selectable !== null) {
            this.selectableOpts = Object.assign(this.selectableOpts, this.config.selectable);
            this.config.selectable = true;
        }

        if (typeof this.config.sortable === 'object' && this.config.sortable !== null) {
            this.sortableOpts = Object.assign(this.sortableOpts, this.config.sortable);
            this.config.sortable = true;
        }

        this.rootEl = document.createElement('ul');
        this.rootEl.classList.add('maho-tree');

        for (const [cssVar, cssVal] of Object.entries(this.config.cssVars)) {
            this.rootEl.style.setProperty(`--${cssVar}`, cssVal);
        }

        // TODO, bind all callbacks in sortableOpts too?
        if (typeof this.selectableOpts.onSelect === 'function') {
            this.selectableOpts.onSelect = this.selectableOpts.onSelect.bind(this);
        }

        if (this.selectableOpts.hideInputs === true) {
            this.rootEl.classList.add('hide-checkbox');
        }
        containerEl.appendChild(this.rootEl);

        this.bindEventListeners();
    }

    bindEventListeners() {
        this.rootEl.addEventListener('change', this.updateChildCheckboxes.bind(this));
    }

    dispatchEvent() {
        this.rootEl.dispatchEvent(...arguments);
    }

    bindDraggableJs(el) {
        if (this.config.sortable) {
            let group = this.sortableOpts.group;
            if (this.sortableOpts.containDepth === true) {
                group += '.' + this.getDepth(el);
            }
            el.dataset.group = group;
            new Sortable(el, { ...this.sortableOpts, group });
	}
    }

    storeNode(key, node) {
        const obj = {
            ...node,
            getPath: () => node.path,
            select: () => true,
            //getUI().checkbox
        }
        delete obj.children;
        this.nodeDataMap.set(key, obj);
    }

    getRootNode() {
        return this.rootNode;
    }

    getNodeById(id) {
        if (id) {
            return this.nodeDataMap.get(this.rootEl.querySelector(`#${id}`));
        }
    }

    buildRootNode(node) {
        console.log(node);
        if (typeof node !== 'object') {
            throw new Error('Root node must be an object or array');
        }

        if (Array.isArray(node)) {
            this.config.rootVisible = false;
            this.rootNode = {
                id: 'root',
                text: 'Root',
                children: node,
            }
        } else {
            this.rootNode = {
                children: [],
                ...node,
            };
        }

        if (this.config.rootVisible) {
            this.buildNode(this.rootNode);
        } else {
            for (const child of this.rootNode.children) {
                this.buildNode(child);
            }
        }

        this.bindDraggableJs(this.rootEl);
        if (this.selectableOpts.mode === 'nested') {
            this.updateParentCheckboxes();
        }
    }

    buildNode(node, parentNode = null, prepend = false) {

        const liEl = document.createElement('li');

        node.liEl = liEl;
        if (parentNode) {
            node.parentEl = parentNode.liEl.querySelector('ul');
        }
        if (prepend) {
            (node.parentEl ?? this.rootEl).prepend(liEl);
        } else {
            (node.parentEl ?? this.rootEl).append(liEl);
        }

        this.storeNode(liEl, node);

        if (node.id) {
            liEl.id = node.id;
            node.path = parentNode ? `${parentNode.id}/${node.id}` : node.id;
        }

        const divEl = document.createElement('div');
        divEl.classList.add('label');

        if (node.selectable ?? this.config.selectable) {
            divEl.innerHTML = '<span class="icon"></span><label><input type="checkbox"><span></span></label>';
            const inputEl = divEl.querySelector('input');
            if (node.name) {
                inputEl.setAttribute('name', node.name);
            }
            if (node.checked) {
                inputEl.setAttribute('checked', '');
            }
            if (node.disabled) {
                inputEl.setAttribute('disabled', '');
            }
            if (this.selectableOpts.mode === 'radio') {
                inputEl.type = 'radio';
                inputEl.name = this.selectableOpts.radioName;
            }
        } else {
            divEl.innerHTML = '<span class="icon"></span><span></span>';
        }

        const hasChildren = Array.isArray(node.children);

        const name = node.name ?? node.text ?? (hasChildren ? 'Folder' : 'Node');
        divEl.querySelector('span:not(.icon)').textContent = unescapeHTML(name);
        liEl.dataset.text = name;

        const icons = (node.icon ?? node.cls ?? '').trim().split(/\s+/).filter(Boolean);
        if (icons.length === 0) {
            icons.push(hasChildren ? this.config.iconFolder : this.config.iconLeaf);
        }
        if (node.disabled) {
            icons.push('disabled');
        }
        divEl.querySelector('span.icon').classList.add(...icons);

        if (hasChildren) {
            const detailsEl = document.createElement('details');
            if (node.expanded) {
                detailsEl.setAttribute('open', '');
            }
            detailsEl.innerHTML = '<summary></summary><ul></ul>';
            detailsEl.querySelector('summary').append(divEl);
            liEl.append(detailsEl);

            if (node.children.length) {
                for (const child of node.children) {
                    this.buildNode(child, node);
                }
                this.bindDraggableJs(detailsEl.querySelector('ul'));
                node.hasLoadedChildren = true;
            } else if (this.config.treeLoaderUrl) {
                detailsEl.addEventListener('toggle', this.onDetailsToggle.bind(this));
            }
        } else {
            liEl.append(divEl);
        }
    }

    async onDetailsToggle(event) {
        const detailsEl = event.target;
        if (detailsEl.open === false) {
            return;
        }

        const node = this.nodeDataMap.get(detailsEl.closest('li'));
        if (node.hasLoadedChildren === true) {
            return;
        }

        try {
            console.log('loading');
            console.log(detailsEl)

            const options = {
                method: 'POST',
                body: new URLSearchParams({
                    form_key: FORM_KEY,
                    node: node.id,
                }),
            }

            //await new Promise(r => setTimeout(r, 1000));

            const response = await fetch(this.config.treeLoaderUrl, options);
            if (!response.ok) {
                throw new Error(`Server returned status ${response.status}`);
            }

            const children = await response.json();

            for (const child of children) {
                this.buildNode(child, node);
            }

            node.hasLoadedChildren = true;
        } catch (error) {
            console.error('Error loading children:', error)
        }

    }

    expandAll() {
        this.rootEl.querySelectorAll('details').forEach((el) => el.open = true);
    }

    collapseAll() {
        this.rootEl.querySelectorAll('details').forEach((el) => el.open = false);
    }

    getDepth(el) {
        let current = el, depth = 0;
        while (current !== this.rootEl) {
            depth++;
            current = current.parentElement.closest('ul');
        }
        return depth;
    }

    getChecked() {
        return Array.from(this.rootEl.querySelectorAll('input:checked')).map((el) => {
            return this.nodeDataMap.get(el.closest('li'));
        });
    }

    setHasChanges() {
        window.varienElementMethods?.setHasChanges(this.rootEl);
    }

    updateChildCheckboxes(event) {
        const targetEl = event.target;
        if (targetEl.tagName !== 'INPUT' || ['checkbox', 'radio'].includes(targetEl.type) === false) {
            return;
        }
        if (this.selectableOpts.mode === 'nested') {
            targetEl.closest('li').querySelectorAll('input[type=checkbox]').forEach((el) => {
                el.checked = targetEl.checked;
                el.indeterminate = false;
            });
            this.updateParentCheckboxes();
        } else if (this.selectableOpts.mode === 'single') {
            this.rootEl.querySelectorAll('input[type=checkbox]:checked').forEach((el) => {
                if (el !== targetEl) {
                    el.checked = false;
                }
            });
        }
        if (typeof this.selectableOpts.onSelect === 'function') {
            this.selectableOpts.onSelect(this.getChecked());
        }
        this.setHasChanges();
    }

    updateParentCheckboxes() {
        Array.from(this.rootEl.querySelectorAll('li')).reverse().forEach((el) => {
            const parent = el.querySelector('input[type=checkbox]');
            const children = Array.from(el.querySelectorAll(':scope ul input[type=checkbox]'));
            if (children.length) {
                if (children.every((el) => el.checked)) {
                    parent.checked = true;
                    parent.indeterminate = false;
                } else if (children.all((el) => !el.checked)) {
                    parent.checked = false;
                    parent.indeterminate = false;
                } else {
                    parent.checked = false;
                    parent.indeterminate = true;
                }
            }
        });
    }
}
