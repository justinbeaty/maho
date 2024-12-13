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
class ReviewEditForm {
    constructor(config = {}) {
        this.config = config;
        this.bindEventListeners();

        // ratingItemsUrl
        // Add:  $this->getUrl('*/*/ratingItems')
        // Edit: $this->getUrl('*/*/ratingItems', ['_current' => true])
    }

    bindEventListeners() {
        const storeSelectEl = document.getElementById('select_stores');
        if (storeSelectEl) {
            storeSelectEl.addEventListener('change', () => this.updateRating());
        }
    }

    showForm() {
        toggleParentVis('add_review_form');
        toggleVis('reviewProductGrid');
        toggleVis('save_button');
        toggleVis('reset_button');
    }

    hideForm() {
        toggleParentVis('add_review_form');
        toggleVis('save_button');
        toggleVis('reset_button');
    }


    gridRowClick(data, event) {
        console.log(data, event)
        const url = event.target.closest('tr').title;
        if (url) {
            this.loadProductData(url);
            this.showForm();
        }
    }

    async loadProductData(url) {
        // Backwards compatibility
        url ??= this.productInfoUrl;

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

            const data = await response.json();
            if (data.error || !data.id) {
                throw new Error(data.message);
            }

            const linkEl = document.createElement('a');
            linkEl.setAttribute('href', this.config.productEditUrl + `id/${data.id}`);
            linkEl.setAttribute('target', '_blank');
            linkEl.textContent = data.name;

            document.getElementById('product_name').replaceChildren(linkEl);
            document.getElementById('product_id').value = data.id;

            this.showForm();

        } catch (error) {
            console.error(`Error loading product: ${error}`);
        }
    }

    updateRating() {
        elements = [
            $("select_stores"),
            $("rating_detail").getElementsBySelector("input[type=\'radio\']")
        ].flatten();
        $('save_button').disabled = true;
        var params = Form.serializeElements(elements);
        if (!params.isAjax) {
            params.isAjax = "true";
        }
        if (!params.form_key) {
            params.form_key = FORM_KEY;
        }
        new Ajax.Updater(
            "rating_detail",
            this.config.ratingItemsUrl,
            {
                parameters:params,
                evalScripts: true,
                onComplete:function(){
                    $('save_button').disabled = false;
                }
            }
        );
    }

}
