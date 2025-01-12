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
        this.activeOnlyOne = activeOnlyOne || false;
        this.container = document.getElementById(this.containerId);
        this.items = Array.from(document.querySelectorAll(`#${this.containerId} dt`));
        this.loader = new varienLoader(true);

        let links = Array.from(document.querySelectorAll(`#${this.containerId} dt a`));
        links.forEach((link, index) => {
            if (link.href) {
                link.addEventListener('click', this.clickItem.bind(this));
                this.items[index].dd = this.items[index].nextElementSibling;
                this.items[index].link = link;
            }
        });

        this.initFromCookie();
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

    clickItem(event) {
        let item = event.target.closest('dt');
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
        event.stopPropagation();
        event.preventDefault();
    }

    showItem(item) {
        if (item && item.link) {
            if (item.link.href) {
                this.loadContent(item);
            }

            item.classList.add('open');
            item.dd.classList.add('open');
        }
    }

    hideItem(item) {
        item.classList.remove('open');
        item.dd.classList.remove('open');
    }

    isItemVisible(item) {
        return item.classList.contains('open');
    }

    loadContent(item) {
        if (item.link.href.endsWith('#')) {
            return;
        }
        if (item.link.classList.contains('ajax')) {
            this.loadingItem = item;
            this.loader.load(item.link.href, {updaterId: this.loadingItem.dd.id}, this.setItemContent.bind(this));
            return;
        }
        location.href = item.link.href;
    }

    setItemContent(content) {
        if (content.isJSON) {
            return;
        }
        this.loadingItem.dd.innerHTML = content;
    }

    hideAllItems() {
        this.items.forEach((item) => {
            if (item.id) {
                item.classList.remove('open');
                item.dd.classList.remove('open');
            }
        });
    }
}


const Fieldset = {
    saveUrl: null,

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
