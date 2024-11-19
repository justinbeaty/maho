/**
 *
 */
class EavAttributeEditForm {

    constructor(formId, inputTypeDefs, config = {}) {
        this.inputTypeDefs = inputTypeDefs;
        this.config = config;
        this.formEl = document.getElementById(formId);
        if (!this.formEl) {
            throw new Error(`Form with ID ${formId} not found in DOM`);
        }
        this.bindEventListeners();
        this.updateForm();
    }

    bindEventListeners() {
        this.formEl.addEventListener('change', this.updateForm.bind(this), { capture: true });
    }

    setRowVisibility(id, isVisible) {
        const el = document.getElementById(id);
        if (el) {
            const tr = el.closest('tr');
            if (isVisible) {
                tr.classList.remove('no-display');
            } else {
                tr.blur();
                tr.classList.add('no-display');
            }
        }
    }

    setFieldsetVisibility(id, isVisible) {
        const el = document.getElementById(id);
        if (el) {
            if (isVisible) {
                el.classList.remove('no-display');
                el.previousElementSibling.classList.remove('no-display');
            } else {
                el.classList.add('no-display');
                el.previousElementSibling.classList.add('no-display');
            }
        }
    }

    getInputTypeValue() {
        const el = document.getElementById('frontend_input');
        if (el) {
            return el.value;
        }
        return '';
    }

    updateForm() {
        // Reset visibility of all rows and fieldsets
        this.formEl.querySelectorAll('tr.no-display').forEach((el) => {
            el.classList.remove('no-display');
        });
        this.formEl.querySelectorAll('.fieldset.no-display').forEach((el) => {
            el.classList.remove('no-display');
            el.previousElementSibling.classList.remove('no-display');
        });

        // Manually trigger dependence block conditions
        this.formEl.querySelectorAll('input, select, textarea').forEach((el) => {
            el.dispatchEvent(new FormElementDependenceEvent());
        });

        // Hide fields defined in config.xml eav_inputtypes nodes
        const inputType = this.getInputTypeValue();
        const hiddenFields = this.inputTypeDefs[inputType]?.hide_fields ?? [];

        for (let field of hiddenFields) {
            if (field === '_front_fieldset') {
                this.setFieldsetVisibility('front_fieldset', false);
            } else if (field === '_scope') {
                this.setRowVisibility('is_global', false);
            } else {
                this.setRowVisibility(field, false);
            }
        }

        // todo
        // if($('backend_type') && $('backend_type').options) {
        //     for(var i=0;i<$('backend_type').options.length;i++) {
        //         if($('backend_type').options[i].value=='int') {
        //             $('backend_type').selectedIndex = i;
        //         }
        //     }
        // }

        this.switchDefaultValueField();
        this.updateOptionsPanel();

        if (typeof this.config.callbacks?.afterUpdateForm === 'function') {
            this.config.callbacks.afterUpdateForm();
        }
    }

    switchDefaultValueField() {
        const inputType = this.getInputTypeValue();
        const defaultValueField = this.inputTypeDefs[inputType]?.default_value_field;

        const fields = [
            'default_value_text',
            'default_value_textarea',
            'default_value_date',
            'default_value_yesno',
        ];
        for (let field of fields) {
            this.setRowVisibility(field, field === defaultValueField);
        }
    }

    updateOptionsPanel() {
        const panelEl = document.querySelector('#manage-options-panel, #matage-options-panel');
        if (!panelEl) {
            return;
        }

        const optionDefaultInputTypes = {
            select: 'radio',
            customselect: 'radio',
            multiselect: 'checkbox',
        }
        const optionDefaultInputType = optionDefaultInputTypes[this.getInputTypeValue()];

        if (optionDefaultInputType) {
            panelEl.classList.remove('no-display');
            panelEl.querySelectorAll('default[]').forEach((el) => {
                el.type = optionDefaultInputType;
            });
        } else {
            panelEl.classList.add('no-display');
        }

        if ($F('frontend_input')=='select' && $F('is_required')==1) {
            $('option-count-check').addClassName('required-options-count');
        } else {
            $('option-count-check').removeClassName('required-options-count');
        }
    }
}
