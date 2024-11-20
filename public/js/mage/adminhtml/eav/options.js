/**
 *
 */
class EavAttributeOptionsForm {

    itemCount = 0;
    totalItems = 0;

    constructor(panelId, inputTypeOptionsInfo, template, config = {}) {
        this.config = config;
        this.inputTypeOptionsInfo = inputTypeOptionsInfo;
        this.panelEl = document.getElementById(panelId);
        if (!this.panelEl) {
            throw new Error(`Panel with ID ${panelId} not found in DOM`);
        }
        // PrototypeJS Template Instance
        this.template = new Template(template, /(^|.|\r|\n)({{(\w+)}})/);

        this.bindEventListeners();
    }

    bindEventListeners() {
        const addNewOptionButton = document.getElementById('add_new_option_button');
        if (addNewOptionButton) {
            addNewOptionButton.addEventListener('click', () => this.add());
        }
        const frontendInputEl = document.getElementById('frontend_input');
        if (frontendInputEl) {
            frontendInputEl.addEventListener('change', () => this.updateOptionsPanel());
        }
    }

    bindRowEventListeners(row) {
        const deleteOptionButton = row.querySelector('.delete-option');
        if (deleteOptionButton) {
            deleteOptionButton.addEventListener('click', this.remove.bind(this));
        }
        const swatchOptionButton = row.querySelector('.swatch-option');
        if (swatchOptionButton) {
            swatchOptionButton.addEventListener('click', this.swatch.bind(this));
        }
        const swatchDeleteButton = row.querySelector('.swatch-delete');
        if (swatchDeleteButton) {
            swatchDeleteButton.addEventListener('click', this.swatchRemove.bind(this));
        }
    }

    getInputTypeValue() {
        const el = document.getElementById('frontend_input');
        return el ? el.value : '';
    }

    getOptionInputType() {
        const inputType = this.getInputTypeValue();
        return this.inputTypeOptionsInfo[inputType]?.type;
    }

    add(option = {}) {
        if (!this.panelEl) {
            return;
        }

        const dummyEl = document.createElement('table');
        dummyEl.innerHTML = this.template.evaluate({
            id: `option_${this.itemCount}`,
            intype: this.getOptionInputType(),
            swatch_class: option.swatch ? '' : 'swatch-disabled',
            ...option,
        });

        const row = dummyEl.querySelector('tr');
        const tbody = this.panelEl.querySelector('tbody');

        tbody.insertBefore(row, tbody.childNodes[1]);
        this.bindRowEventListeners(row);

        this.itemCount++;
        this.totalItems++;
        this.updateItemsCountField();
    }

    addMany(options) {
        for (let option of options) {
            this.add(option)
        }
    }

    remove(event) {
        const trEl = event.target.closest('tr');
        if (!trEl) {
            return;
        }
        trEl.querySelectorAll('.delete-flag').forEach((el) => {
            el.value = 1;
        });
        trEl.classList.add('no-display');

        this.totalItems--;
        this.updateItemsCountField();
    }

    swatch(event) {
        const trEl = event.target.closest('tr');
        if (!trEl) {
            return;
        }
        trEl.querySelectorAll('.swatch-value').forEach((el) => {
            el.disabled = false;
            el.value = event.target.value;
        });
        event.target.classList.remove('swatch-disabled');
    }

    swatchRemove(event) {
        if (!confirm(event.target.getAttribute('data-msg-delete'))) {
            return;
        }
        const trEl = event.target.closest('tr');
        if (!trEl) {
            return;
        }
        trEl.querySelectorAll('.swatch-value').forEach((el) => {
            el.disabled = false;
            el.value = '';
        });
        trEl.querySelectorAll('.swatch-option').forEach((el) => {
            el.value = '';
            el.classList.add('swatch-disabled');
        });
    }

    updateItemsCountField() {
        const el = document.getElementById('option-count-check');
        if (el) {
            el.value = this.totalItems > 0 ? '1' : '';
        }
    }

    updateOptionsPanel() {
        if (!this.panelEl) {
            return;
        }

        const type = this.getOptionInputType();

        if (type) {
            this.panelEl.classList.remove('no-display');
            this.panelEl.querySelectorAll('input[name="default[]"]').forEach((el) => el.type = type);
        } else {
            this.panelEl.classList.add('no-display');
        }

        if ($F('frontend_input')=='select' && $F('is_required')==1) {
            $('option-count-check').addClassName('required-options-count');
        } else {
            $('option-count-check').removeClassName('required-options-count');
        }
    }
}
