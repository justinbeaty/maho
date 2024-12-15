
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
            dataUrl: null,
            rootVisible: true,
            varienSetHasChanges: true,
            cssVars: {},
        }, config);

        this.selectableOpts = {
            mode: 'nested',
            hideInputs: false,
            radioName: this.uniqId,
        }

        this.sortableOpts = {
	    group: `sortable.${this.uniqId}`,
	    animation: 150,
	    fallbackOnBody: true,
	    swapThreshold: 0.65,
            containDepth: false,
        };

        if (typeof this.config.selectable === 'string' || this.config.selectable instanceof String) {
            this.selectableOpts.mode = this.config.selectable;
            this.config.selectable = true;
        } else if (typeof this.config.selectable === 'object' && this.config.selectable !== null) {
            this.selectableOpts = Object.assign(this.selectableOpts, this.config.selectable);
            this.config.selectable = true;
            if (typeof this.selectableOpts.onSelect === 'function') {
                this.selectableOpts.onSelect = this.selectableOpts.onSelect.bind(this);
            }
        }

        if (typeof this.config.sortable === 'string' || this.config.sortable instanceof String) {
            this.sortableOpts.group = this.config.sortable;
            this.config.sortable = true;
        } else if (typeof this.config.sortable === 'object' && this.config.sortable !== null) {
            this.sortableOpts = Object.assign(this.sortableOpts, this.config.sortable);
            this.config.sortable = true;
            // TODO, bind all callbacks in sortableOpts too?
        }

        this.createElement();
        containerEl.appendChild(this.rootEl);

        this.bindEventListeners();
        this.bindDraggableJs(this.rootEl);
    }

    setRootNode(node) {
        console.log(node)
        if (node instanceof MahoTreeNode) {
            this.rootNode = node;
        } else if (Array.isArray(node)) {
            this.rootNode = new MahoTreeNode(this, {
                id: '__root__',
                text: 'Root',
                expanded: true,
                children: node,
            });
        } else if (typeof node === 'object' && node !== null) {
            this.rootNode = new MahoTreeNode(this, {
                expanded: true,
                children: [],
                ...node,
            });
        } else {
            throw new TypeError('Root node must be an object, array, or MahoTreeNode');
        }

        this.rootEl.replaceChildren(this.rootNode.ui.wrap);
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
        if (this.config.rootVisible === false) {
            this.rootEl.classList.add('hide-root-node');
        }
    }

    bindEventListeners() {
        this.rootEl.addEventListener('change', (event) => {
            const targetEl = event.target;
            if (targetEl.tagName !== 'INPUT' || ['checkbox', 'radio'].includes(targetEl.type) === false) {
                return;
            }
            if (this.selectableOpts.mode === 'nested') {
                this.updateNestedCheckboxes();
            } else if (this.selectableOpts.mode === 'single') {
                this.rootEl.querySelectorAll('input[type=checkbox]:checked').forEach((el) => {
                    el.checked = el === targetEl;
                });
            }
            if (typeof this.selectableOpts.onSelect === 'function') {
                this.selectableOpts.onSelect(this.getChecked());
            }
        });

        this.mutationObserver = new MutationObserver((mutationList, observer) => {
            for (const mutation of mutationList) {
                for (const el of mutation.addedNodes) {
                    if (el.tagName === 'LI') {
                        el.querySelectorAll(':scope ul').forEach(this.bindDraggableJs.bind(this));
                    }
                }
            }
            if (this.selectableOpts.mode === 'nested') {
                this.updateNestedCheckboxes();
            }
        });

        this.mutationObserver.observe(this.rootEl, { childList: true, subtree: true });
    }

    bindDraggableJs(el) {
        if (!this.config.sortable || el.dataset.group) {
            return
        }
        let group = this.sortableOpts.group;
        if (this.sortableOpts.containDepth === true) {
            let current = el, depth = 0;
            while (current !== this.rootEl) {
                current = current.parentNode.closest('ul');
                depth++;
            }
            group += '.' + depth;
        }
        el.dataset.group = group;
        new Sortable(el, { ...this.sortableOpts, group });
    }

    updateNestedCheckboxes() {
        if (this.selectableOpts.mode !== 'nested') {
            return;
        }
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

    storeNode(key, node) {
        this.nodeDataMap.set(key, node);
    }

    getRootNode() {
        return this.rootNode;
    }

    getNodeById(id) {
        if (id) {
            return this.nodeDataMap.get(this.rootEl.querySelector('#' + id));
        }
    }

    getChecked() {
        return Array.from(this.rootEl.querySelectorAll('input:checked')).map((el) => {
            return this.nodeDataMap.get(el.closest('li'));
        });
    }

    expandAll() {
        this.rootEl.querySelectorAll('details').forEach((el) => el.open = true);
    }

    collapseAll() {
        this.rootEl.querySelectorAll('details').forEach((el) => el.open = false);
    }

    dispatchEvent() {
        console.log('DEPRECATED dispatchEvent');
        this.rootEl.dispatchEvent(...arguments);
    }

    buildRootNode(node) {
        console.log('DEPRECATED buildRootNode');
        return this.setRootNode(node);
    }
}

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

        this.id = this.attributes.id ?? generateRandomString(6);
        this.text = this.attributes.text ?? this.attributes.name;
        this.icons = (this.attributes.icon ?? this.attributes.cls ?? '').trim().split(/\s+/).filter(Boolean);
        this.type = Array.isArray(children) ? 'folder' : 'leaf';

        //
        this.text ??= this.type.charAt(0).toUpperCase() + this.type.slice(1);
        if (this.icons.length === 0) {
            this.icons.push(this.type);
        }

        this.createElement();
        this.bindEventListeners();

        if (this.type === 'folder') {
            for (const child of children) {
                this.appendChild(new MahoTreeNode(this.tree, child))
            }
            if (children.length) {
                this.hasLoadedChildren = true;
            }
            this.ui.wrap.append(this.ui.details);
        } else {
            this.ui.wrap.append(this.ui.label);
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
        this.ui.details.innerHTML = '<summary></summary><ul></ul>';
        this.ui.summary = this.ui.details.children[0];
        this.ui.ctNode = this.ui.details.children[1];
    }

    bindEventListeners() {
        this.ui.checkbox?.addEventListener('change', () => {
            if (this.tree.selectableOpts.mode === 'nested') {
                if (this.ui.checkbox.checked) {
                    this.selectChildren();
                } else {
                    this.deselectChildren();
                }
            }
            if (this.tree.config.varienSetHasChanges) {
                window.varienElementMethods?.setHasChanges(this.ui.checkbox);
            }
        });
        this.ui.details?.addEventListener('toggle', () => {
            if (this.ui.details.open === true && this.hasLoadedChildren === false) {
                this.loadChildren();
            }
        });
    }

    getUI() {
        return this.ui;
    }

    get parentNode() {
        const el = this.ui.wrap.parentElement?.closest('li');
        if (el) {
            return this.tree.nodeDataMap.get(el);
        }
    }

    get childNodes() {
        return Array.from(this.ui.ctNode.children).map((el) => {
            return this.tree.nodeDataMap.get(el);
        });
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

    select() {
        if (this.ui.checkbox) {
            this.ui.checkbox.checked = true;
            this.ui.checkbox.indeterminate = false;
        }
    }

    deselect() {
        if (this.ui.checkbox) {
            this.ui.checkbox.checked = false;
            this.ui.checkbox.indeterminate = false;
        }
    }

    selectChildren() {
        this.ui.ctNode?.querySelectorAll('input[type=checkbox]').forEach((el) => {
            el.checked = true;
            el.indeterminate = false;
        });
    }

    deselectChildren() {
        this.ui.ctNode?.querySelectorAll('input[type=checkbox]').forEach((el) => {
            el.checked = false;
            el.indeterminate = false;
        });
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
            const response = await fetch(this.tree.config.dataUrl, options);
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
