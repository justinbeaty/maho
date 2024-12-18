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

        this.storeId = 0;

        this.initVarienForm();

        this.tree = new MahoTree(this.config.treeDiv, {
            rootVisible: true,
            noLeafNodes: true,
            selectable: {
                mode: 'single',
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

                const data = await response.text();
                console.log(data);

                this.changeCategory();

            } catch (error) {
                this.setMessage(error, 'error');
            }
        }
        this.varienForm.submit();
    }

    categoryReset(url) {
        this.updateContent(url, {active_tab_id: false})
    }

    categoryDelete(url) {
        const confirmed = confirm('Are you sure you want to delete this category?'); // translate
        if (confirmed) {
            this.updateContent(url);
        }
    }

    addNew(url, isRoot) {

        const parent = isRoot ? { id: 1 } : this.getSelectedCategory();
        if (parent === undefined) {
            alert('Please select a parent category before adding a new one.'); // translate
            return;
        }

        url = url.replace(/\/store\/\d+/, '');
        url += `store/${this.storeId}/parent/${parent.id}/`;

        this.updateContent(url);
    }

    async switchStore(event, switcher)
    {
        if (switcher.useConfirm) {
            const confirmed = confirm('Please confirm site switching. All data that hasn\'t been saved will be lost.');
            if (!confirmed) {
                event.target.value = this.storeId === 0 ? '' : this.storeId;
                return;
            }
        }

        this.storeId = 1 * event.target.value;
        document.getElementById('addRootCategoryBtn')?.classList.toggle('no-display', this.storeId !== 0);

        showLoader();

        try {
            let url = this.config.switchTreeUrl;
            if (this.storeId) {
                url += `store/${this.storeId}/`;
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
            const { root_visible, ...rootNode } = result.parameters;

            this.tree.setRootVisible(root_visible);
            //rootNode.expanded = true;
            rootNode.children = result.data;
            this.tree.setRootNode(rootNode);

        } catch (error) {
            this.setMessage(error, 'error');
        }

        hideLoader();
        toolbarToggle.start();
    }

    async updateContent(url, params = {}, refreshTree = false) {
        // TODO, if no use AJAX, just setLocation here
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

            let data = await response.text();
            try {
                data = JSON.parse(data);
            } catch {
            }

            this.clearMessage();

            if (typeof data === 'object' && data !== null) {
                if (data.error) {
                    throw new Error(data.error);
                }
                if (data.content) {
                    updateElementHTML(this.containerEl, data.content);
                    //this.containerEl.innerHTML = data.content;
                }
                if (data.messages) {
                    this.setMessageHTML(data.messages);
                }
            } else {
                updateElementHTML(this.containerEl, data);
            }

            window[this.config.tabsJsObjectName]?.moveTabContentInDest();
            this.initVarienForm();
            return true;

        } catch (error) {
            this.setMessage(error, 'error');
        }

        hideLoader();
        toolbarToggle.start();
    }

    async moveCategory(event) {
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

            const text = await response.text();

            if (text !== 'SUCCESS') {
                throw new Error(text);
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
