/**
 *
 */

var optionDefaultInputType = 'radio';


var attributeOption = {
    table : document.getElementById('attribute-options-table'),
    templateSyntax : /(^|.|\r|\n)({{(\w+)}})/,
    templateText : templateText,
    itemCount : 0,
    totalItems : 0,
    isReadOnly: <?= (int)$this->getReadOnly() ?>,
    add : function(data) {
        this.template = new Template(this.templateText, this.templateSyntax);
        let isNewOption = false;
        if (!data.id) {
            data.id = 'option_' + this.itemCount;
            isNewOption = true;
        }
        if (!data.intype) {
            data.intype = optionDefaultInputType;
        }
        if (!data.swatch) {
            data.swatch_class = 'swatch-disabled';
        }
        let newHTML = this.template.evaluate(data);
        this.table.insertAdjacentHTML('afterend', newHTML);
        if (isNewOption && !this.isReadOnly) {
            this.enableNewOptionDeleteButton(data.id);
        }
        this.bindRemoveButtons();
        this.bindSwatchButtons();
        this.bindSwatchRemoveButtons();
        this.itemCount++;
        this.totalItems++;
        this.updateItemsCountField();
    },
    remove : function(event){
        let element = event.target.closest('tr');
        if (element) {
            let elementFlags = element.querySelectorAll('.delete-flag');
            if (elementFlags.length > 0) {
                elementFlags[0].value = 1;
            }

            element.classList.add('no-display', 'template');
            this.totalItems--;
            this.updateItemsCountField();
        }
    },
    swatch : function(event){
        let element = event.target.closest('tr');
        if (element) {
            let elementSwatchValue = element.querySelector('.swatch-value');
            if (elementSwatchValue) {
                elementSwatchValue.disabled = false;
                elementSwatchValue.value = event.target.value;
            }

            event.target.classList.remove('swatch-disabled');
        }
    },
    swatchremove : function(event){
        if (!confirm(event.target.getAttribute('data-msg-delete'))) {
            return;
        }
        let element = event.target.closest('tr');
        if (element) {
            let elementSwatchValue = element.querySelector('.swatch-value');
            if (elementSwatchValue) {
                elementSwatchValue.disabled = false;
                elementSwatchValue.value = '';
            }

            let elementSwatchOption = element.querySelector('.swatch-option');
            if (elementSwatchOption) {
                elementSwatchOption.value = '';
                elementSwatchOption.classList.add('swatch-disabled');
            }
        }
    },
    updateItemsCountField: function() {
        let optionCountCheck = document.getElementById('option-count-check');
        optionCountCheck.value = this.totalItems > 0 ? '1' : '';
    },
    enableNewOptionDeleteButton: function(id) {
        document.querySelectorAll('#delete_button_container_' + id + ' button').forEach(function(button) {
            button.disabled = false;
            button.classList.remove('disabled');
        });
    },
    bindRemoveButtons: function() {
        let buttons = document.querySelectorAll('.delete-option');
        buttons.forEach(function(button) {
            if (!button.binded) {
                button.binded = true;
                button.addEventListener('click', attributeOption.remove.bind(attributeOption));
            }
        });
    },
    bindSwatchButtons: function() {
        let buttons = document.querySelectorAll('.swatch-option');
        buttons.forEach(function(button) {
            if (!button.dataset.binded) {
                button.dataset.binded = true;
                button.addEventListener('change', attributeOption.swatch.bind(attributeOption));
            }
        });
    },
    bindSwatchRemoveButtons: function() {
        let buttons = document.querySelectorAll('.swatch-delete');
        buttons.forEach(function(button) {
            if (!button.binded) {
                button.binded = true;
                button.addEventListener('click', attributeOption.swatchremove.bind(attributeOption));
            }
        });
    }
}
attributeOption.bindRemoveButtons();
let addNewOptionButton = document.getElementById('add_new_option_button');
if (addNewOptionButton) {
    addNewOptionButton.addEventListener('click', attributeOption.add.bind(attributeOption));
}


class EavAttributeOptionsForm {

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
            panelEl.querySelectorAll('input[name="default[]"]').forEach((el) => {
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
