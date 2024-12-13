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
class EavAttributeSetForm {

    constructor(containerGroups, containerAttributes, config = {}) {
        this.tree1 = new MahoTree(containerGroups, {
            selectable: {
                mode: 'single',
                hideInputs: true,
            },
            sortable: {
                group: 'attributes',
                containDepth: true,
                onMove: this.onMove.bind(this),
                onChange: this.onChange.bind(this),
            }
            callbacks: {
                onUpdate: this.onSelect,
            },
        });
        this.tree2 = new MahoTree(containerAttributes, {
            sortable: {
                group: 'attributes.1',
                sort: false,
                onChange: (event) => this.sortAttributes(event.to),
            },
            cssVars: {
                'indent': '0',
                'line-style': 'none',
            },
        });
    }

    buildGroupTree(data) {
        this.tree1.buildRootNode(data);
        this.tree1.expandAll();
    }

    buildAttributeTree(data) {
        this.tree2.buildRootNode(data);
    }

    onSelect() {
        // show hide delete and rename buttons
    }

    onChange(event) {

        //return false;

    }

    onMove(event) {
        console.log(event)

        //if (

        return true;

        const isUnassigned = this.tree2.rootEl.contains(event.dragged);

        //
        if (isUnassigned && event.from === event.to) {
            return false;
        }

        return true;
    }

    sortAttributes(el) {
        if (el.tagName !== 'UL') {
            return;
        }
        [].slice.call(el.children)
            .sort((a, b) => a.dataset.text > b.dataset.text ? 1 : -1)
            .forEach(node => el.appendChild(node));
    }

    bindEventListeners() {
        //this.formEl.addEventListener('change', this.updateForm.bind(this), { capture: true });
    }

}
