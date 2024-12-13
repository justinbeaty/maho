class MahoTree {
    constructor(container, config = {}) {
        const containerEl = document.getElementById(container);
        if (!containerEl) {
            throw new Error(`Element with ID ${container} not found in DOM`);
        }

        this.config = Object.assign({
            selectable: false, // single, simple, nested (true)
            sortable: false, // true or object

            rootVisible: true,
            iconFolder: 'folder',
            iconLeaf: 'paper',
            cssVars: {},
        }, config);

        this.selectableOpts = {
            mode: this.config.selectable === true ? 'nested' : this.config.selectable,
            hideInputs: false,
        }

        this.sortableOpts = {
	    group: 'sortable',
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

        for (const [cssVar, value] of Object.entries(this.config.cssVars)) {
            this.rootEl.style.setProperty(`--${cssVar}`, value);
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

    buildRootNode(data) {
        console.log(data)

        let nodes = []
        if (Array.isArray(data)) {
            nodes = data;
        } else if (typeof data === 'object' && data !== null) {
            if (this.config.rootVisible) {
                nodes = [data];
            } else {
                nodes = data.children ?? [];
            }
        }
        for (const node of nodes) {
            this.buildNode(node);
        }


        this.bindDraggableJs(this.rootEl);
        if (this.selectableOpts.mode === 'nested') {
            this.updateParentCheckboxes();
        }
    }

    buildNode(obj, parentEl = null) {
        const hasChildren = Array.isArray(obj.children);

        const liEl = document.createElement('li');
        if (obj.id) {
            liEl.id = obj.id;
        }

        //
        (parentEl ?? this.rootEl).appendChild(liEl);

        const divEl = document.createElement('div');
        divEl.classList.add('label');

        if (obj.selectable ?? this.config.selectable) {
            divEl.innerHTML = '<span class="icon"></span><label><input type="checkbox"><span></span></label>';
            const checkbox = divEl.querySelector('input');
            if (obj.name) {
                checkbox.setAttribute('name', obj.name);
            }
            if (obj.checked) {
                checkbox.setAttribute('checked', '');
            }
            if (obj.disabled) {
                checkbox.setAttribute('disabled', '');
            }
        } else {
            divEl.innerHTML = '<span class="icon"></span><span></span>';
        }

        divEl.querySelector('span:not(.icon)').textContent = unescapeHTML(obj.text);

        const icons = (obj.icon ?? obj.cls ?? '').trim().split(/\s+/);
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
            detailsEl.querySelector('summary').appendChild(divEl);
            liEl.appendChild(detailsEl);

            const ulEl = detailsEl.querySelector('ul');
            for (const child of obj.children) {
                this.buildNode(child, ulEl);
            }
            this.bindDraggableJs(ulEl);
        } else {
            liEl.appendChild(divEl);
        }

        const data = {...obj};
        delete data.children;
        liEl.dataset.obj = JSON.stringify(data);

        liEl.dataset.text = obj.text;
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
        return Array.from(this.rootEl.querySelectorAll('input[type=checkbox]:checked')).map((el) => {
            return JSON.parse(el.closest('li').dataset.obj);
        });
    }

    updateChildCheckboxes(event) {
        const targetEl = event.target;
        if (targetEl.tagName !== 'INPUT' || targetEl.type !== 'checkbox') {
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
