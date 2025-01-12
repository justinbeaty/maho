/**
 * Maho
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright   Copyright (c) 2022 The OpenMage Contributors (https://openmage.org)
 * @license     https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class varienAccordion {
    constructor(containerId, activeOnlyOne) {
        this.containerId = containerId;
        this.container = document.getElementById(this.containerId);
        this.activeOnlyOne = activeOnlyOne || false;
        this.loader = new varienLoader(true);

        this.items = this.container.querySelectorAll(':scope > details');
        this.items.forEach((el) => {
            el.addEventListener('toggle', this.onToggle.bind(this));
        });

        //this.initFromCookie();
    }

    initFromCookie() {
        let activeItemId, visibility;
        if (this.activeOnlyOne && (activeItemId = Cookie.read(this.cookiePrefix() + 'active-item')) !== null) {
            this.hideAllItems();
            this.showItem(this.getItemById(activeItemId));
        } else if (!this.activeOnlyOne) {
            this.items.forEach((item) => {
                if ((visibility = Cookie.read(this.cookiePrefix() + item.id)) !== null) {
                    if (visibility == 0) {
                        this.hideItem(item);
                    } else {
                        this.showItem(item);
                    }
                }
            });
        }
    }

    cookiePrefix() {
        return `accordion-${this.containerId}-`;
    }

    getItemById(itemId) {
        let result = null;

        this.items.forEach((item) => {
            if (item.id == itemId) {
                result = item;
                throw $break;
            }
        });

        return result;
    }

    onToggle(event) {
        const item = event.target;

        /*
        if (this.activeOnlyOne) {
            this.hideAllItems();
            this.showItem(item);
            Cookie.write(this.cookiePrefix() + 'active-item', item.id, 30 * 24 * 60 * 60);
        } else {
            if (this.isItemVisible(item)) {
                this.hideItem(item);
                Cookie.write(this.cookiePrefix() + item.id, 0, 30 * 24 * 60 * 60);
            } else {
                this.showItem(item);
                Cookie.write(this.cookiePrefix() + item.id, 1, 30 * 24 * 60 * 60);
            }
        }
        */

        if (item.dataset.url) {
            if (item.dataset.target === 'ajax') {
                this.loadContent(item);
            } else {
                setLocation(item.dataset.url);
            }
        }

    }

    showItem(item) {
        item.open = true;
    }

    hideItem(item) {
        item.open = false;
    }

    isItemVisible(item) {
        return item.open;
    }

    hideAllItems() {
        this.items.forEach((item) => item.open = false);
    }

    async loadContent(item) {
        item.classList.add('loading');
        try {

            await new Promise(resolve => setTimeout(resolve, 1000))

            const contentsEl = item.querySelector('div[id|=dd]');
            const html = await mahoFetch(item.dataset.url, {
                method: 'POST',
                body: new URLSearchParams({
                    updaterId: contentsEl.id,
                }),
            });
            contentsEl.innerHTML = html;
            item.removeAttribute('data-url');
        } catch (error) {
            console.log(error)
            item.open = false;
        }
        item.classList.remove('loading');
    }
}


const Fieldset = {
    saveUrl: null,

    applyAllCollapse(formId) {
        document.querySelectorAll(`#${formId} details > fieldset`).forEach((container) => {
            Fieldset.applyCollapse(container.id);
        });
    },

    applyCollapse(containerId) {
        const detailsEl = document.getElementById(containerId).closest('details');
        const stateInputEl = document.getElementById(`${containerId}-state`);
        if (!detailsEl || !stateInputEl) {
            return;
        }
        detailsEl.addEventListener('toggle', () => {
            stateInputEl.value = +detailsEl.open;
            Fieldset.saveState(null, { container: containerId, value: stateInputEl.value });
        });
        stateInputEl.value = +detailsEl.open;
    },

    toggleCollapse(containerId) {
        const detailsEl = document.getElementById(containerId).closest('details');
        if (!detailsEl) {
            return;
        }
        detailsEl.open = !detailsEl.open;
    },

    /** @deprecated */
    addToPrefix (value) {
    },

    saveState(url, parameters) {
        url ??= Fieldset.saveUrl;
        if (url) {
            new Ajax.Request(url, {
                method: 'get',
                parameters: Object.toQueryString(parameters),
                loaderArea: false
            });
        }
    },
};
