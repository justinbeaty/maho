/**
 * Maho
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license     https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 *
 */
class CategoryEditForm {

    constructor(config = {}) {
        this.config = config;
        this.containerEl = document.getElementById(config.containerDiv);
        if (!this.containerEl) {
            throw new Error(`Container with ID ${config.containerDiv} not found in DOM`);
        }

        this.ui = {
            addRootCategoryBtn: document.getElementById(this.config.addRootCategoryBtn),
            addSubCategoryBtn: document.getElementById(this.config.addSubCategoryBtn),
        }

        this.initVarienForm();
        this.initProductsGrid();

        this.tree = new MahoTree(this.config.treeDiv, {
            rootVisible: true,
            noLeafNodes: true,
            selectable: {
                mode: 'radio',
                hideInputs: true,
                onSelect: this.changeCategory.bind(this),
            },
            sortable: {
                //rootSortable: false,
                onEnd: this.moveCategory.bind(this),
            },
            lazyload: {
                nodeParameter: 'id',
                dataUrl: this.config.loadTreeUrl,
                onBeforeLoad: (node, params) => {
                    if (this.wasExpanded) {
                        params.append('expand_all', '1');
                    }
                },
                onLoadException: (node, error) => {
                    this.setMessage(Translator.translate('Error loading children: %s', error), 'error');
                }
            }
        })
    }

    getEditUrl() {
        let url = this.config.editUrl;

        const activeTab = window[this.config.tabsJsObjectName]?.activeTab;
        if (activeTab) {
            url = url.replace(/\/active_tab\/\w+/, '');
            url += `active_tab/${activeTab.name}/`;
        }

        return url;
    }

    initVarienForm() {
        this.formEl = this.containerEl.querySelector('form');
        if (!this.formEl) {
            throw new Error(`Container with ID ${config.containerDiv} does not contain a form element.`);
        }
        this.varienForm = new varienForm(this.formEl.id);
        if (this.config.useAjax) {
            this.varienForm._submit = this.submitCategory.bind(this);
        }
    }

    initProductsGrid() {
        const { gridJsObjectName, products } = window.productsInfo ?? {};
        const gridObj = window[gridJsObjectName];
        const inputEl = document.getElementById(this.config.categoryProductsEl);

        if (!gridObj || !products || !inputEl) {
            return
        }

        gridObj.updateSelected = () => {
            gridObj.reloadParams = { 'selected_products[]': Object.keys(products) };
            inputEl.value = new URLSearchParams(products).toString();
        }

        gridObj.initRowCallback = (gridObj, row) => {
            const checkboxEl = row.querySelector('.checkbox');
            const positionEl = row.querySelector('.input-text');
            if (checkboxEl && positionEl) {
                positionEl.disabled = !checkboxEl.checked;
                positionEl.addEventListener('change', (event) => {
                    if (checkboxEl.checked) {
                        products[checkboxEl.value] = positionEl.value;
                        gridObj.updateSelected();
                    }
                });
            }
        }

        gridObj.rowClickCallback = (gridObj, event) => {
            if (event.target.closest('td').querySelector('a, input:not([type=checkbox])')) {
                return;
            }
            const checkboxEl = event.target.closest('tr').querySelector('input[type=checkbox]');
            if (checkboxEl) {
                const checked = event.target === checkboxEl ? checkboxEl.checked : !checkboxEl.checked;
                gridObj.setCheckboxChecked(checkboxEl, checked);
            }
        }

        gridObj.checkboxCheckCallback = (gridObj, element, checked) => {
            const positionEl = event.target.closest('tr')?.querySelector('input[name=position]');
            if (positionEl) {
                positionEl.disabled = !checked;
            }
            if (checked) {
                products[element.value] = positionEl?.value ?? 0;
            } else {
                delete products[element.value];
            }
            gridObj.updateSelected();
        }

        gridObj.rows.forEach((row) => gridObj.initRowCallback(gridObj, row));
        gridObj.updateSelected();
    }

    renderTree(config) {
        const { root_visible, can_add_root, category_id, store_id, expanded, ...rest } = config.parameters;

        this.storeId = parseInt(store_id) || 0;
        this.ui.addRootCategoryBtn?.classList.toggle('no-display', !can_add_root);

        this.tree.setRootVisible(root_visible);
        this.tree.setRootNode({
            ...rest,
            children: config.data,
            expanded: true,
        });

        if (expanded) {
            this.expandTree();
        }

        this.tree.getNodeById(category_id)?.select();
    }

    collapseTree() {
        this.wasExpanded = false;
        this.tree.collapseAll();
    }

    expandTree() {
        this.wasExpanded = true;
        this.tree.expandAll();
    }

    getSelectedCategory() {
        return this.tree.getChecked().pop();
    }

    changeCategory() {
        const category = this.getSelectedCategory();
        if (category && (category.id != window.categoryInfo?.category_id || this.storeId != window.categoryInfo?.store_id)) {
            this.updateContent(this.getEditUrl() + `store/${this.storeId}/id/${category.id}/`);
        }
    }

    resetCategory(url) {
        this.updateContent(url);
    }

    saveCategory(url) {
        this.varienForm.submit();
    }

    async submitCategory() {
        showLoader();
        try {
            const ajaxUrl = new URL(this.formEl.action,);
            ajaxUrl.searchParams.set('isAjax', 'true');

            const formData = new FormData(this.formEl);
            const response = await fetch(ajaxUrl, {
                method: 'POST',
                body: formData,
            });

            if (!response.ok) {
                throw new Error(Translator.translate('Server returned status %s', response.status));
            }

            const result = await response.json();
            if (result.error) {
                throw new Error(result.error);
            }

            this.updateContent(this.getEditUrl() + `store/${this.storeId}/id/${result.category_id}/`);

        } catch (error) {
            this.setMessage(error, 'error');
        }
        hideLoader();
    }

    async deleteCategory(url) {
        const confirmed = confirm(Translator.translate('Are you sure you want to delete this category?'));
        if (!confirmed) {
            return;
        }
        if (!this.config.useAjax) {
            url = url.replace(/\/form_key\/\w+/, '');
            url += `form_key/${FORM_KEY}/`;
            return setLocation(url);
        }

        showLoader();

        try {
            const response = await fetch(url, {
                method: 'POST',
                body: new URLSearchParams({
                    form_key: FORM_KEY,
                }),
            });

            if (!response.ok) {
                throw new Error(Translator.translate('Server returned status %s', response.status));
            }

            const result = await response.json();
            if (result.error) {
                throw new Error(result.error);
            }

            this.tree.getNodeById(result.category_id)?.remove();
            this.tree.getNodeById(result.parent_id)?.select();

        } catch (error) {
            this.setMessage(error, 'error');
        }

        hideLoader();
    }

    addCategory(url, isRoot) {
        const parent = isRoot ? { id: 1 } : this.getSelectedCategory();
        if (!parent) {
            alert(Translator.translate('Please select a parent category before adding a new one.'));
            return;
        }
        if (parent.id === 1) {
            this.tree.deselectAll();
        }

        url = url.replace(/\/store\/\d+/, '');
        url += `store/${this.storeId}/parent/${parent.id}/`;

        this.updateContent(url);
    }

    async updateContent(url, params = {}) {
        if (!this.config.useAjax) {
            return setLocation(url);
        }

        showLoader();

        try {
            const ajaxUrl = new URL(url);
            ajaxUrl.searchParams.set('isAjax', 'true');

            const response = await fetch(ajaxUrl, {
                method: 'POST',
                body: new URLSearchParams({
                    form_key: FORM_KEY,
                    ...params,
                }),
            });

            if (!response.ok) {
                throw new Error(Translator.translate('Server returned status %s', response.status));
            }

            const result = await response.json();

            this.clearMessage();

            if (result.error) {
                throw new Error(result.error);
            }
            if (result.title) {
                document.title = result.title;
            }
            if (result.content) {
                updateElementHtmlAndExecuteScripts(this.containerEl, result.content);
            }
            if (result.messages) {
                this.setMessageHTML(result.messages);
            }

            if (window.categoryInfo) {
                console.log(window.categoryInfo)
                let node = this.tree.rootNode;
                for (const breadcrumb of window.categoryInfo.breadcrumbs) {
                    if (!this.tree.getNodeById(breadcrumb.id)) {
                        await this.tree.expandPath(node.getPath());
                    }
                    if (!this.tree.getNodeById(breadcrumb.id)) {
                        node.appendChild(new MahoTreeNode(this.tree, breadcrumb));
                    }
                    node = this.tree.getNodeById(breadcrumb.id);
                    node.updateAttributes(breadcrumb);
                }
                if (this.ui.addSubCategoryBtn) {
                    this.ui.addSubCategoryBtn.disabled = !window.categoryInfo.can_add_sub;
                }
            }

            window[this.config.tabsJsObjectName]?.moveTabContentInDest();
            this.initVarienForm();
            this.initProductsGrid();

            history.replaceState(null, '', url);

        } catch (error) {
            this.setMessage(error, 'error');
        }

        hideLoader();
        toolbarToggle.start();
    }

    async switchStore(event, switcher) {
        if (switcher.useConfirm) {
            const confirmed = confirm(Translator.translate('Please confirm site switching. All data that hasn\'t been saved will be lost.'));
            if (!confirmed) {
                event.target.value = this.storeId === 0 ? '' : this.storeId;
                return;
            }
        }

        const storeId = parseInt(event.target.value) || 0;
        const category = this.getSelectedCategory();

        if (!this.config.useAjax) {
            let url = this.getEditUrl();
            if (storeId) {
                url += `store/${storeId}/`;
            }
            if (category) {
                url += `id/${category.id}/`
            }

            return setLocation(url);
        }

        showLoader();

        try {

            let url = this.config.switchTreeUrl;
            if (storeId) {
                url += `store/${storeId}/`;
            }
            if (category) {
                url += `id/${category.id}/`
            }

            const response = await fetch(url, {
                method: 'POST',
                body: new URLSearchParams({
                    form_key: FORM_KEY,
                }),
            });

            if (!response.ok) {
                throw new Error(Translator.translate('Server returned status %s', response.status));
            }

            const result = await response.json();
            this.renderTree(result);

        } catch (error) {
            this.setMessage(error, 'error');
        }

        hideLoader();
        toolbarToggle.start();
    }

    async moveCategory(event) {
        if (event.from === event.to && event.oldIndex === event.newIndex) {
            return;
        }

        showLoader();

        try {
            const node = this.tree.getNodeByEl(event.item);
            const response = await fetch(this.config.moveUrl, {
                method: 'POST',
                body: new URLSearchParams({
                    form_key: FORM_KEY,
                    id: node.id,
                    pid: node.parentNode.id,
                    aid: node.previousNode?.id ?? 0,
                }),
            });

            if (!response.ok) {
                throw new Error(Translator.translate('Server returned status %s', response.status));
            }

            const result = await response.json();
            if (result.error) {
                throw new Error(result.error);
            }
        } catch (error) {
            this.setMessage(error, 'error');
        }

        hideLoader();
    }

    ///////////////////

    clearMessage() {
        this.setMessageHTML('');
    }
    // success, error, notice
    setMessage(message, type = 'success') {
        this.setMessageHTML(`<ul class="messages"><li class="${type}-msg"><ul><li><span>${message}</span></li></ul></li></ul>`);
    }

    setMessageHTML(html) {
        document.getElementById('messages').innerHTML = html;
    }
}
