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

    storeNode(key, obj) {
        this.nodeDataMap.set(key, {
            ...obj,
            getPath: () => obj.path,
        });
    }

    getRootNode() {
        return this.rootNode;
    }

    getNodeById(id) {
        if (id) {
            return this.nodeDataMap.get(this.rootEl.querySelector(`#${id}`));
        }
    }

    buildRootNode(data) {
        console.log(data);
        if (typeof data !== 'object') {
            throw new Error('Root node must be an object or array');
        }

        if (Array.isArray(data)) {
            this.config.rootVisible = false;
            this.rootNode = {
                id: 'root',
                text: 'Root',
                children: data,
            }
        } else {
            this.rootNode = {
                children: [],
                ...data
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

    buildNode({ children, ...obj }, parentEl = null, prepend = false) {

        const liEl = document.createElement('li');
        const hasChildren = Array.isArray(children);

        if (prepend) {
            (parentEl ?? this.rootEl).prepend(liEl);
        } else {
            (parentEl ?? this.rootEl).append(liEl);
        }

        this.storeNode(liEl, obj);

        if (obj.id) {
            liEl.id = obj.id;

            if (parentEl) {
                obj.path = this.getNodeById(parentEl.closest('li').id).path + '/' + obj.id;
                console.log(obj.path)
            } else {
                obj.path = obj.id
            }
        }

        const divEl = document.createElement('div');
        divEl.classList.add('label');

        if (obj.selectable ?? this.config.selectable) {
            divEl.innerHTML = '<span class="icon"></span><label><input type="checkbox"><span></span></label>';
            const inputEl = divEl.querySelector('input');
            if (obj.name) {
                inputEl.setAttribute('name', obj.name);
            }
            if (obj.checked) {
                inputEl.setAttribute('checked', '');
            }
            if (obj.disabled) {
                inputEl.setAttribute('disabled', '');
            }
            if (this.selectableOpts.mode === 'radio') {
                inputEl.type = 'radio';
                inputEl.name = this.selectableOpts.radioName;
            }
        } else {
            divEl.innerHTML = '<span class="icon"></span><span></span>';
        }

        const name = obj.name ?? obj.text ?? (hasChildren ? 'Folder' : 'Node');
        divEl.querySelector('span:not(.icon)').textContent = unescapeHTML(name);
        liEl.dataset.text = name;

        const icons = (obj.icon ?? obj.cls ?? '').trim().split(/\s+/).filter(Boolean);
        if (icons.length === 0) {
            icons.push(hasChildren ? this.config.iconFolder : this.config.iconLeaf);
        }
        if (obj.disabled) {
            icons.push('disabled');
        }
        divEl.querySelector('span.icon').classList.add(...icons);

        if (hasChildren) {
            const detailsEl = document.createElement('details');
            if (obj.expanded) {
                detailsEl.setAttribute('open', '');
            }
            detailsEl.innerHTML = '<summary></summary><ul></ul>';
            detailsEl.querySelector('summary').append(divEl);
            liEl.append(detailsEl);

            if (children.length) {
                const ulEl = detailsEl.querySelector('ul');
                for (const child of children) {
                    this.buildNode(child, ulEl);
                }
                this.bindDraggableJs(ulEl);
                obj.hasLoadedChildren = true;
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

        const obj = this.nodeDataMap.get(detailsEl.closest('li'));
        if (obj.hasLoadedChildren === true) {
            return;
        }

        try {
            console.log('loading');

            const options = {
                method: 'POST',
                body: new URLSearchParams({
                    form_key: FORM_KEY,
                    node: obj.id,
                }),
            }

            //await new Promise(r => setTimeout(r, 1000));

            const response = await fetch(this.config.treeLoaderUrl, options);
            if (!response.ok) {
                throw new Error(`Server returned status ${response.status}`);
            }

            const children = await response.json();

            const ulEl = detailsEl.querySelector('ul');
            for (const child of children) {
                this.buildNode(child, ulEl);
            }

            obj.hasLoadedChildren = true;
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
