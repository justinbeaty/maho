class MahoTreeNode {

    constructor(tree, data) {
        if (!tree instanceof MahoTree) {
            throw new TypeError('Tree parameter must be instance of MahoTree');
        }
        if (typeof data !== 'object' || Array.isArray(data) || data === null) {
            throw new TypeError('Data parameter must be an object');
        }

        const { children, ...attributes } = data;

        this.ui = {};
        this.tree = tree;
        this.attributes = attributes;

        this.type = Array.isArray(children) ? 'folder' : 'leaf';
        this.id = this.attributes.id ?? generateRandomString(6);
        this.text = this.attributes.text ?? this.attributes.name;
        this.icons = (this.attributes.icon ?? this.attributes.cls ?? '').trim().split(/\s+/).filter(Boolean);

        //
        this.text ??= this.type.charAt(0).toUpperCase() + this.type.slice(1);
        if (this.icons.length === 0) {
            this.icons.push(this.type);
        }

        this.createElement();

        if (this.type === 'folder') {
            //this.childNodes = [];
            for (const child of children) {
                this.appendChild(new MahoTreeNode(this.tree, child))
            }
            if (children.length) {
                this.hasLoadedChildren = true;
            }
        } else {
            this.ui.wrap.append(divEl);
        }

        this.tree.storeNode(this.ui.wrap, this);
    }

    createElement() {
        this.ui.wrap = document.createElement('li');
        this.ui.wrap.id = this.id;
        this.ui.wrap.dataset.text = this.text;

        this.createLabelElement();

        if (this.type === 'folder') {
            this.createDetailsElement()
            this.ui.wrap.append(this.ui.details);
            this.ui.summary.append(this.ui.label);
        } else {
            this.ui.wrap.append(this.ui.label);
        }
    }

    createLabelElement() {
        if (this.attributes.selectable ?? this.tree.config.selectable) {
            this.ui.label = document.createElement('label');
            this.ui.label.innerHTML = '<span class="icon"></span><input type="checkbox"><span></span>';

            this.ui.checkbox = this.ui.label.querySelector('input');
            if (this.attributes.name) {
                this.ui.checkbox.setAttribute('name', this.attributes.name);
            }
            if (this.attributes.checked) {
                this.ui.checkbox.setAttribute('checked', '');
            }
            if (this.attributes.disabled) {
                this.ui.checkbox.setAttribute('disabled', '');
            }
            if (this.tree.selectableOpts.mode === 'radio') {
                this.ui.checkbox.type = 'radio';
                this.ui.checkbox.name = this.tree.selectableOpts.radioName;
            }
            this.ui.checkbox.addEventListener('change', () => {
                if (this.tree.selectableOpts.mode === 'nested') {
                    this.updateChildrenCheckboxes(this.ui.checkbox.checked);
                    this.updateParentCheckboxes();
                }
                window.varienElementMethods?.setHasChanges(this.ui.checkbox);
            });
        } else {
            this.ui.label = document.createElement('div');
            this.ui.label.innerHTML = '<span class="icon"></span><span></span>';
        }

        this.ui.label.classList.add('label');

        if (this.attributes.disabled) {
            this.ui.label.classList.add('disabled');
        }

        this.ui.textNode = this.ui.label.querySelector('span:not(.icon)');
        this.ui.textNode.textContent = unescapeHTML(this.text);

        this.ui.iconNode = this.ui.label.querySelector('span.icon');
        this.ui.iconNode.classList.add(...this.icons);
    }

    createDetailsElement() {
        this.ui.details = document.createElement('details');
        if (this.attributes.expanded) {
            this.ui.details.setAttribute('open', '');
        }
        this.ui.details.addEventListener('toggle', () => {
            if (this.ui.details.open === true && this.hasLoadedChildren === false) {
                this.loadChildren();
            }
        });
        this.ui.details.innerHTML = '<summary></summary><ul></ul>';
        this.ui.summary = this.ui.details.children[0];
        this.ui.ctNode = this.ui.details.children[1];
    }

    selectChildren() {
        this.updateChildrenCheckboxes(true);
    }

    deselectChildren() {
        this.updateChildrenCheckboxes(false);
    }

    updateChildrenCheckboxes(checked = true) {
        if (this.type !== 'folder') {
            return;
        }
        this.ui.ctNode.querySelectorAll('input[type=checkbox]').forEach((el) => {
            el.checked = checked;
            el.indeterminate = false;
        });
    }

    updateParentCheckboxes() {
        if (this.tree.selectableOpts.mode !== 'nested') {
            return;
        }
        let current = this;
        while (current = current.parentNode) {
            const children = Array.from(current.ui.ctNode.querySelectorAll(':scope input[type=checkbox]'));
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
    }

    appendChild(node) {
        if (this.type !== 'folder') {
            throw new Error('Cannot append child to leaf node');
        }
        this.ui.ctNode.append(node.ui.wrap);
    }

    prependChild(node) {
        if (this.type !== 'folder') {
            throw new Error('Cannot append child to leaf node');
        }
        this.ui.ctNode.prepend(node.ui.wrap);
    }

    async loadChildren() {
        try {
            const options = {
                method: 'POST',
                body: new URLSearchParams({
                    form_key: FORM_KEY,
                    node: this.id,
                }),
            }

            //await new Promise(r => setTimeout(r, 1000));
            const response = await fetch(this.tree.config.treeLoaderUrl, options);
            if (!response.ok) {
                throw new Error(`Server returned status ${response.status}`);
            }

            const children = await response.json();
            for (const child of children) {
                this.appendChild(new MahoTreeNode(this.tree, child))
            }
            this.hasLoadedChildren = true;

        } catch (error) {
            console.error('Error loading children:', error)
        }
    }
}


class MahoTree {
    constructor(container, config = {}) {
        const containerEl = document.getElementById(container);
        if (!containerEl) {
            throw new Error(`Element with ID ${container} not found in DOM`);
        }

        this.nodeDataMap = new WeakMap();
        this.uniqId = generateRandomString(6);

        this.config = Object.assign({
            selectable: false, // radio, single, simple, nested (true)
            sortable: false, // true or object
            rootVisible: true,
            cssVars: {},
        }, config);

        this.selectableOpts = {
            mode: this.config.selectable === true ? 'nested' : this.config.selectable,
            hideInputs: false,
            radioName: this.uniqId,
        }

        this.sortableOpts = {
	    group: this.config.sortable === true ? `sortable.${this.uniqId}` : this.config.sortable,
	    animation: 150,
	    fallbackOnBody: true,
	    swapThreshold: 0.65,
            containDepth: false,
        };

        if (typeof this.config.selectable === 'object' && this.config.selectable !== null) {
            this.selectableOpts = Object.assign(this.selectableOpts, this.config.selectable);
            this.config.selectable = true;
            if (typeof this.selectableOpts.onSelect === 'function') {
                this.selectableOpts.onSelect = this.selectableOpts.onSelect.bind(this);
            }
        }

        if (typeof this.config.sortable === 'object' && this.config.sortable !== null) {
            this.sortableOpts = Object.assign(this.sortableOpts, this.config.sortable);
            this.config.sortable = true;
            // TODO, bind all callbacks in sortableOpts too?
        }

        this.createElement();
        containerEl.appendChild(this.rootEl);

        this.bindEventListeners();
    }

    createElement() {
        this.rootEl = document.createElement('ul');
        this.rootEl.classList.add('maho-tree');

        for (const [cssVar, cssVal] of Object.entries(this.config.cssVars)) {
            this.rootEl.style.setProperty(`--${cssVar}`, cssVal);
        }
        if (this.selectableOpts.hideInputs === true) {
            this.rootEl.classList.add('hide-checkbox');
        }
    }

    // todo remove...
    dispatchEvent() {
        this.rootEl.dispatchEvent(...arguments);
    }

    bindEventListeners() {
        this.rootEl.addEventListener('change', (event) => {
            const targetEl = event.target;
            if (targetEl.tagName !== 'INPUT' || ['checkbox', 'radio'].includes(targetEl.type) === false) {
                return;
            }
            if (this.selectableOpts.mode === 'single') {
                this.rootEl.querySelectorAll('input[type=checkbox]:checked').forEach((el) => {
                    el.checked = el === targetEl;
                });
            }
            if (typeof this.selectableOpts.onSelect === 'function') {
                this.selectableOpts.onSelect(this.getChecked());
            }
        });
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
        // todo, create MahoTreeNode class
        const obj = {
            ...node,
            getPath: () => node.path,
            select: () => true,
            //getUI().checkbox
        }
        delete obj.children;
        this.nodeDataMap.set(key, obj);
        return obj;
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
                id: '__root__',
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

        return this.storeNode(liEl, node);
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

}
