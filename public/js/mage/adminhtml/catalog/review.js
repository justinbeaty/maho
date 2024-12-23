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
    }

    bindEventListeners() {
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('select_stores')?.addEventListener('change', this.updateRating.bind(this));
        });
    }

    toggleSaveButton(isEnabled = true) {
        const saveBtn = document.getElementById('save_button');
        if (saveBtn) {
            saveBtn.disabled = !isEnabled;
        }
    }

    toggleForm(isVisible = true) {
        document.getElementById('add_review_form')?.parentNode.classList.toggle('no-display', !isVisible);
        document.getElementById('reviewProductGrid')?.classList.toggle('no-display', !!isVisible);
        document.getElementById('save_button')?.classList.toggle('no-display', !isVisible);
        document.getElementById('reset_button')?.classList.toggle('no-display', !isVisible);
    }

    showForm() {
        this.toggleForm(true);
    }

    hideForm() {
        this.toggleForm(false);
    }

    async gridRowClick(data, event) {
        const url = event.target.closest('tr')?.title;
        const success = await this.loadProductData(url);
        if (success) {
            this.showForm();
        }
    }

    async loadProductData(url) {
        let success = false;
        showLoader();

        try {
            // Backwards compatibility: old code would store URL in `this.productInfoUrl`
            // from `gridRowClick()` and then call this function with no parameters
            if (!(url ??= this.productInfoUrl)) {
                throw new Error('Product info URL not found');
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

            const data = await response.json();
            if (data.error || !data.id) {
                throw new Error(data.message);
            }

            if (this.config.productEditUrl) {
                const linkEl = document.createElement('a');
                linkEl.setAttribute('href', this.config.productEditUrl + `id/${data.id}`);
                linkEl.setAttribute('target', '_blank');
                linkEl.textContent = data.name;
                document.getElementById('product_name').replaceChildren(linkEl);
            } else {
                document.getElementById('product_name').textContent = data.name;
            }

            document.getElementById('product_id').value = data.id;
            success = true;

        } catch (error) {
            console.error(`Error loading product: ${error}`);
        }

        hideLoader();
        return success;
    }

    async updateRating() {
        this.toggleSaveButton(false);

        try {
            if (!this.config.ratingItemsUrl) {
                throw new Error('Rating Items URL not found');
            }

            const body = new URLSearchParams({
                isAjax: 'true',
                form_key: FORM_KEY,
            });

            const elements = [
                document.getElementById('select_stores'),
                ...document.querySelectorAll('#rating_detail input[type=radio]'),
            ];

            for (const el of elements) {
                body.append(el.name, el.value);
            }

            const response = await fetch(this.config.ratingItemsUrl, { method: 'POST', body });
            if (!response.ok) {
                throw new Error(Translator.translate('Server returned status %s', response.status));
            }

            const html = await response.text();
            updateElementHTML(document.getElementById('rating_detail'), html);

        } catch (error) {
            console.error(`Error loading rating details: ${error}`);
        }

        this.toggleSaveButton(true);
    }
}