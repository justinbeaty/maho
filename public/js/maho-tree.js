/**
 * Maho
 *
 * @category    Maho
 * @package     js
 * @copyright   Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license     https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */


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
            lazyload: false, // url or object
            rootVisible: true,
            noLeafNodes: false,
            varienSetHasChanges: true,
            cssVars: {},
        }, config);

        this.selectableOpts = {
            mode: 'nested',
            hideInputs: false,
            radioName: this.uniqId,
            onSelect: null,
        };

        this.sortableOpts = {
            group: `sortable.${this.uniqId}`,
            animation: 150,
            invertSwap: true,
            fallbackOnBody: true,
            containDepth: false,
            rootSortable: true,
        };

        this.lazyloadOpts = {
            dataUrl: null,
            nodeParameter: 'node',
            //preloadChildren: false,
            onBeforeLoad: null,
            onLoadException: null,
        };

        // Check for options using the string short-hand and set the appropriate option
        if (typeof this.config.selectable === 'string' || this.config.selectable instanceof String) {
            this.selectableOpts.mode = this.config.selectable;
            this.config.selectable = true;
        }
        if (typeof this.config.sortable === 'string' || this.config.sortable instanceof String) {
            this.sortableOpts.group = this.config.sortable;
            this.config.sortable = true;
        }
        if (typeof this.config.lazyload === 'string' || this.config.lazyload instanceof String) {
            this.lazyloadOpts.dataUrl = this.config.lazyload;
            this.config.lazyload = true;
        }

        // Check for options using full object definitions, and bind any callbacks to this tree instance
        for (const key of ['selectable', 'sortable', 'lazyload']) {
            if (typeof this.config[key] === 'object' && this.config[key] !== null) {
                const obj = Object.assign(this[key + 'Opts'], this.config[key]);
                for (const callback of Object.keys(obj)) {
                    if (typeof obj[callback] === 'function') {
                        obj[callback] = obj[callback].bind(this);
                    }
                }
                this.config[key] = true;
            }
        }

        this.createElement();
        containerEl.appendChild(this.rootEl);

        this.bindEventListeners();
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

    setRootVisible(flag) {
        this.config.rootVisible = flag;
        this.rootEl.classList.toggle('hide-root-node', !flag);
    }

    createElement() {
        this.rootEl = document.createElement('ul');
        this.rootEl.classList.add('maho-tree');
        this.setRootVisible(this.config.rootVisible);

        for (const [cssVar, cssVal] of Object.entries(this.config.cssVars)) {
            this.rootEl.style.setProperty(`--${cssVar}`, cssVal);
        }
        if (this.selectableOpts.hideInputs === true) {
            this.rootEl.classList.add('hide-checkbox');
        }
    }

    bindEventListeners() {
        this.rootEl.addEventListener('change', (event) => {
            //console.log('change', event)


            // Check if event.originalEvent === DragEvent
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
                        el.querySelectorAll(':scope ul').forEach(this.bindSortable.bind(this));
                    }
                }
            }
            if (this.selectableOpts.mode === 'nested') {
                this.updateNestedCheckboxes();
            }
        });

        this.mutationObserver.observe(this.rootEl, { childList: true, subtree: true });
    }

    bindSortable(el) {
        if (!this.config.sortable || Sortable.get(el)) {
            return;
        }
        if (this.sortableOpts.rootSortable === false && this.rootNode.ui.ctNode === el) {
            return;
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

        MahoTreeSortablePlugin.mount();

        const sortableInstance = new Sortable(el, { ...this.sortableOpts, group, mahoTree: this });
        if (el.closest('details')?.open !== true) {
            sortableInstance.option('disabled', true);
        }
    }

    updateNestedCheckboxes() {
        if (this.selectableOpts.mode !== 'nested') {
            return;
        }
        Array.from(this.rootEl.querySelectorAll('li')).reverse().forEach((el) => {
            const parent = el.querySelector('input[type=checkbox]');
            const children = Array.from(el.querySelectorAll(':scope ul input[type=checkbox]'));
            if (parent && children.length) {
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

    storeNode(el, node) {
        this.nodeDataMap.set(el, node);
    }

    getRootNode() {
        return this.rootNode;
    }

    getNodeByEl(el) {
        return this.nodeDataMap.get(el);
    }

    getNodeById(id) {
        return this.getNodeByEl(this.rootEl.querySelector(`li[data-id='${id}']`));
    }

    getNodeByText(text) {
        return this.getNodeByEl(this.rootEl.querySelector(`li[data-text='${text}']`));
    }

    getChecked() {
        return Array.from(this.rootEl.querySelectorAll('input:checked')).map((el) => {
            return this.getNodeByEl(el.closest('li'));
        });
    }

    async expandPath(path) {
        const parts = path.split('/').filter(Boolean);
        let current = this.rootNode;
        for (const part of parts) {
            const node = this.getNodeById(part);
            if (node) {
                current = await node.expand();
            } else {
                break;
            }
        }
        return current;
    }

    expandAll() {
        this.rootEl.querySelectorAll('details').forEach((el) => el.open = true);
    }

    collapseAll() {
        this.rootEl.querySelectorAll('details').forEach((el) => {
            if (this.config.rootVisible !== true && this.rootNode.ui.details === el) {
                return;
            }
            el.open = false;
        });
    }

    dispatchEvent() {
        this.rootEl.dispatchEvent(...arguments);
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

        let { children, ...attributes } = data;

        this.ui = {};
        this.tree = tree;
        this.attributes = attributes;

        if (this.tree.config.noLeafNodes || Array.isArray(children)) {
            this.type = 'folder';
            children ??= [];
        } else {
            this.type = 'leaf';
        }

        this.id = this.attributes.id ?? generateRandomString(6);
        this.text = this.attributes.text ?? this.attributes.name;
        this.icons = (this.attributes.icon ?? this.attributes.cls ?? '').trim().split(/\s+/).filter(Boolean);

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
            this.hasLoadedChildren = this.tree.config.lazyload !== true || children.length > 0;
            this.ui.wrap.append(this.ui.details);
        } else {
            this.ui.wrap.append(this.ui.label);
        }

        this.tree.storeNode(this.ui.wrap, this);
    }

    createElement() {
        this.ui.wrap = document.createElement('li');
        this.ui.wrap.dataset.id = this.id;
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
        this.ui.label?.addEventListener('dblclick', () => {
            if (this.ui.details) {
                if (this.ui.details.open) {
                    this.collapse()
                } else {
                    this.expand();
                }
                if (this.ui.checkbox && this.ui.checkbox.type !== 'radio') {
                    this.ui.checkbox.checked = !this.ui.checkbox.checked;
                    this.ui.checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        });
        this.ui.details?.addEventListener('toggle', () => {
            if (this.ui.details.open === true && this.hasLoadedChildren === false) {
                this.loadChildren();
            }
            if (this.tree.config.sortable) {
                const sortableInstance = Sortable.get(this.ui.ctNode);
                if (sortableInstance) {
                    sortableInstance.option('disabled', !this.ui.details.open);
                }
            }
        });
    }

    getUI() {
        return this.ui;
    }

    getPath() {
        const parts = [];
        let current = this;
        while (current) {
            parts.push(current.id);
            current = current.parentNode;
        }
        return parts.reverse().join('/');
    }

    contains(node) {
        return this.ui.wrap.contains(
            node.ui.wrap
        );
    }

    get parentNode() {
        const el = this.ui.wrap.parentElement?.closest('li');
        if (el) {
            return this.tree.getNodeByEl(el);
        }
    }

    get previousNode() {
        return this.ui.wrap.previousSibling;
    }

    get nextNode() {
        return this.ui.wrap.nextSibling;
    }

    get childNodes() {
        return Array.from(this.ui.ctNode.children).map((el) => {
            return this.tree.getNodeByEl(el);
        });
    }

    async expand() {
        if (this.ui.details) {
            if (this.hasLoadedChildren === false) {
                await this.loadChildren();
            }
            this.ui.details.open = true;
        }
        return this;
    }

    collapse() {
        if (this.ui.details) {
            this.ui.details.open = false;
        }
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

    remove() {
        this.ui.wrap.remove();
    }

    appendChild(node) {
        if (this.type !== 'folder') {
            throw new Error('Cannot add child to leaf node');
        }
        this.ui.ctNode.append(node.ui.wrap);
    }

    prependChild(node) {
        if (this.type !== 'folder') {
            throw new Error('Cannot add child to leaf node');
        }
        this.ui.ctNode.prepend(node.ui.wrap);
    }

    removeChild(node) {
        if (this.type !== 'folder') {
            throw new Error('Cannot remove child from leaf node');
        }
        if (this.ui.ctNode.contains(node.ui.wrap)) {
            node.ui.wrap.remove();
        }
    }

    sortChildren() {
        Array.from(this.ui.ctNode.children)
            .sort((a, b) => a.dataset.text > b.dataset.text ? 1 : -1)
            .forEach(node => this.ui.ctNode.appendChild(node));
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
        if (!this.tree.lazyloadOpts.dataUrl) {
            return;
        }

        const timeoutID = setTimeout(() => {
            this.ui.iconNode.classList.add('loading');
        }, LOADING_TIMEOUT ?? 200);

        try {
            const params = new URLSearchParams({
                form_key: FORM_KEY,
                [this.tree.lazyloadOpts.nodeParameter]: this.id,
            });

            if (typeof this.tree.lazyloadOpts.onBeforeLoad === 'function') {
                await this.tree.lazyloadOpts.onBeforeLoad(this, params);
            }

            await new Promise(r => setTimeout(r, 1000));

            const response = await fetch(this.tree.lazyloadOpts.dataUrl, {
                method: 'POST',
                body: params,
            });

            if (!response.ok) {
                throw new Error(`Server returned status ${response.status}`);
            }

            const children = await response.json();

            const newIds = [];
            for (const child of this.childNodes) {
                if (child.isNew) {
                    newIds.push(child.id);
                    child.isNew = false;
                } else {
                    child.remove();
                }
            }

            for (const child of children) {
                if (child.id && newIds.includes(child.id)) {
                    continue;
                }
                this.appendChild(new MahoTreeNode(this.tree, child))
            }
            this.hasLoadedChildren = true;

        } catch (error) {
            console.error('Error loading children:', error)
            if (typeof this.tree.lazyloadOpts.onLoadException === 'function') {
                this.tree.lazyloadOpts.onLoadException(this, error);
            }
        }

        clearTimeout(timeoutID)
        this.ui.iconNode.classList.remove('loading');
    }
}

class MahoTreeSortablePlugin
{
    static pluginName = 'mahoTree';
    static mounted = false;
    static state = {};

    constructor(sortable, el, options) {
        this.tree = options.mahoTree;
        this.defaults = {
	    dropClass: 'drop'
	};
    }

    static mount() {
        if (MahoTreeSortablePlugin.mounted === false) {
            MahoTreeSortablePlugin.mounted = true;
            Sortable.mount(MahoTreeSortablePlugin);
        }
    }

    delayStart({ dragEl }) {
        MahoTreeSortablePlugin.state = {
            dragNode: this.tree.getNodeByEl(dragEl),
            dropNode: null
        };
    }

    dragOver({ target }) {
        const state = MahoTreeSortablePlugin.state;

        // The node we are currently hovering over, potential to become new dropNode
        const hoverNode = this.tree.getNodeByEl(target.closest('li'));
        if (hoverNode === state.dropNode) {
            return;
        }

        // Drop node has changed, so reset styling
        state.dropNode?.ui.wrap.classList.remove(this.options.dropClass);
        state.dropNode = null;

        // Only folders can become a dropNode
        if (hoverNode.type !== 'folder') {
            return;
        }

        // Prevent dragNode from becoming a child of itself
        if (hoverNode.contains(state.dragNode) ||
            state.dragNode.contains(hoverNode)) {
            return;
        }

        // Set new dropNode, onDragend we will move dragNode into this folder
        state.dropNode = hoverNode;
        state.dropNode.ui.wrap.classList.add(this.options.dropClass);
    }

    drop({ activeSortable }) {
        const state = MahoTreeSortablePlugin.state;
        if (state.dropNode) {
            // Prepare the dragged node to be inserted
            state.dragNode.isNew = true;
            state.dropNode.ui.details.open = true;
            state.dropNode.ui.wrap.classList.remove(this.options.dropClass);

            // Insert into new sortable and animate
            activeSortable.captureAnimationState()
            state.dropNode.prependChild(state.dragNode);
            activeSortable.animateAll();
            this.sortable.animateAll();
        }
    }

    nulling() {
        MahoTreeSortablePlugin.state = {};
    }
}
