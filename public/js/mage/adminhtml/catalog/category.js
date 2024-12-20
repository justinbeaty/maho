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

        this.addRootCategoryBtn = document.getElementById(this.config.addRootCategoryBtn);
        this.addSubCategoryBtn  = document.getElementById(this.config.addSubCategoryBtn);

        this.initVarienForm();

        this.tree = new MahoTree(this.config.treeDiv, {
            rootVisible: true,
            noLeafNodes: true,
            selectable: {
                mode: 'radio',
                hideInputs: true,
                onSelect: this.changeCategory.bind(this),
            },
            sortable: {
                rootSortable: false,
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
                    this.setMessage(`Error loading children: ${error}`, 'error');
                }
            }
        })
    }

    initVarienForm() {
        this.formEl = this.containerEl.querySelector('form');
        if (!this.formEl) {
            throw new Error(`Container with ID ${config.containerDiv} does not contain a form element.`);
        }
        this.varienForm = new varienForm(this.formEl.id);
    }

    renderTree(config) {
        console.log(config)
        const { root_visible, can_add_root, category_id, store_id, expanded, ...rest } = config.parameters;

        this.storeId = parseInt(store_id) || 0;
        this.addRootCategoryBtn?.classList.toggle('no-display', !can_add_root);

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
        if (category === undefined) {
            return;
        }
        this.updateContent(this.config.editUrl + `store/${this.storeId}/id/${category.id}/`);
    }

    categorySubmit(url) {
        this.varienForm._submit = async () => {
            try {
                const formData = new FormData(this.formEl);

                const response = await fetch(this.formEl.action, {
                    method: 'POST',
                    body: formData,
                });

                if (!response.ok) {
                    throw new Error(`Server returned status ${response.status}`);
                }

                const data = await response.json();



                this.updateContent(this.config.editUrl + `store/${this.storeId}/id/${data.category_id}/`);

            } catch (error) {
                this.setMessage(error, 'error');
            }
        }
        this.varienForm.submit();
    }

    categoryReset(url) {
        this.updateContent(url, {active_tab_id: false})
    }

    async categoryDelete(url) {
        const confirmed = confirm('Are you sure you want to delete this category?'); // translate
        if (!confirmed) {
            return;
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                body: new URLSearchParams({
                    form_key: FORM_KEY,
                }),
            });

            if (!response.ok) {
                throw new Error(`Server returned status ${response.status}`);
            }

            const result = await response.json();

            if (result.error) {
                throw new Error(result.error);
            }

            this.tree.getNodeById(result.category_id)?.remove();
            this.tree.getNodeById(result.parent_id)?.select();


            //this.updateContent(this.config.editUrl + `store/${this.storeId}/id/${result.parent_id}/`);

        } catch (error) {
            this.setMessage(error, 'error');
        }
    }

    categoryAdd(url, isRoot) {
        const parent = isRoot ? { id: 1 } : this.getSelectedCategory();
        if (parent === undefined) {
            alert('Please select a parent category before adding a new one.'); // translate
            return;
        }

        url = url.replace(/\/store\/\d+/, '');
        url += `store/${this.storeId}/parent/${parent.id}/`;

        this.updateContent(url);
    }

    async updateContent(url, params = {}) {
        showLoader();

        try {
            url = new URL(url);
            url.searchParams.set('isAjax', 'true');

            params.active_tab_id ??= window[this.config.tabsJsObjectName]?.activeTab.id;

            const options = {
                method: 'POST',
                body: new URLSearchParams({
                    form_key: FORM_KEY,
                    ...params,
                }),
            }

            //await new Promise(r => setTimeout(r, 1000));

            const response = await fetch(url, options);
            if (!response.ok) {
                throw new Error(`Server returned status ${response.status}`);
            }

            const result = await response.json();

            this.clearMessage();

            if (result.error) {
                throw new Error(result.error);
            }
            if (result.content) {
                updateElementHTML(this.containerEl, result.content);
            }
            if (result.messages) {
                this.setMessageHTML(result.messages);
            }

            if (window.categoryInfo) {
                let node = this.tree.rootNode;
                for (const breadcrumb of window.categoryInfo.breadcrumbs) {
                    if (!this.tree.getNodeById(breadcrumb.id)) {
                        this.tree.expandPath(node.getPath());
                        node.prependChild(new MahoTreeNode(this.tree, breadcrumb));
                    }
                    node = this.tree.getNodeById(breadcrumb.id);
                    node.setText(breadcrumb.text);
                }
                if (this.addSubCategoryBtn) {
                    this.addSubCategoryBtn.disabled = !window.categoryInfo.can_add_sub;
                }
            }

            window[this.config.tabsJsObjectName]?.moveTabContentInDest();
            this.initVarienForm();

        } catch (error) {
            console.log(error)
            this.setMessage(error, 'error');
        }

        hideLoader();
        toolbarToggle.start();
    }

    async switchStore(event, switcher) {
        if (switcher.useConfirm) {
            const confirmed = confirm('Please confirm site switching. All data that hasn\'t been saved will be lost.');
            if (!confirmed) {
                event.target.value = this.storeId === 0 ? '' : this.storeId;
                return;
            }
        }

        showLoader();

        try {

            const storeId = parseInt(event.target.value) || 0;

            let url = this.config.switchTreeUrl;
            if (storeId) {
                url += `store/${storeId}/`;
            }
            const category = this.getSelectedCategory();
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
                throw new Error(`Server returned status ${response.status}`);
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
                throw new Error(`Server returned status ${response.status}`);
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
