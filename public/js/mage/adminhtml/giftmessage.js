/**
 * Maho
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright   Copyright (c) 2022-2023 The OpenMage Contributors (https://openmage.org)
 * @license     https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

const giftMessagesController = {
    toogleRequired: function(source, objects)
    {
        if(!$(source).value.blank()) {
            objects.each(function(item) {
               $(item).addClassName('required-entry');
               var label = findFieldLabel($(item));
               if (label) {
                   var span = label.down('span');
                   if (!span) {
                       Element.insert(label, {bottom: '&nbsp;<span class="required">*</span>'});
                   }
               }
            });
        } else {
            objects.each(function(item) {
                if($(source).formObj && $(source).formObj.validator) {
                    $(source).formObj.validator.reset(item);
                }
                $(item).removeClassName('required-entry');
                var label = findFieldLabel($(item));
                if (label) {
                    var span = label.down('span');
                    if (span) {
                        Element.remove(span);
                    }
                }
                // Hide validation advices if exist
                if ($(item) && $(item).advices) {
                    $(item).advices.each(function (pair) {
                        if (pair.value != null) pair.value.hide();
                    });
                }
            });
        }
    },
    toogleGiftMessage: function(container) {
        if(!$(container).toogleGiftMessage) {
            $(container).toogleGiftMessage = true;
            $(this.getFieldId(container, 'edit')).show();
            $(container).down('.action-link').addClassName('open');
            $(container).down('.default-text').hide();
            $(container).down('.close-text').show();
        } else {
            $(container).toogleGiftMessage = false;
            $(this.getFieldId(container, 'message')).formObj = $(this.getFieldId(container, 'form'));

            if(!$(this.getFieldId(container, 'form')).validator) {
                $(this.getFieldId(container, 'form')).validator = new Validation(this.getFieldId(container, 'form'));
            }

            if(!$(this.getFieldId(container, 'form')).validator.validate()) {
                return false;
            }

            new Ajax.Request($(this.getFieldId(container, 'form')).action, {
                parameters: Form.serialize($(this.getFieldId(container, 'form')), true),
                loaderArea: container,
                onComplete: function(transport) {

                    $(container).down('.action-link').removeClassName('open');
                    $(container).down('.default-text').show();
                    $(container).down('.close-text').hide();
                    $(this.getFieldId(container, 'edit')).hide();
                    if (transport.responseText.match(/YES/g)) {
                        $(container).down('.default-text').down('.edit').show();
                        $(container).down('.default-text').down('.add').hide();
                    } else {
                        $(container).down('.default-text').down('.add').show();
                        $(container).down('.default-text').down('.edit').hide();
                    }

                }.bind(this)
            });
        }

        return false;
    },
    saveGiftMessage: function(container) {
        $(this.getFieldId(container, 'message')).formObj = $(this.getFieldId(container, 'form'));

        if(!$(this.getFieldId(container, 'form')).validator) {
            $(this.getFieldId(container, 'form')).validator = new Validation(this.getFieldId(container, 'form'));
        }

        if(!$(this.getFieldId(container, 'form')).validator.validate()) {
            return;
        }

        new Ajax.Request($(this.getFieldId(container, 'form')).action, {
            parameters: Form.serialize($(this.getFieldId(container, 'form')), true),
            loaderArea: container
        });
    },
    getFieldId: function(container, name) {
        return container + '_' + name;
    }
};

function findFieldLabel(field) {
    var tdField = $(field).up('td');
    if (tdField) {
       var tdLabel = tdField.previous('td');
       if (tdLabel) {
           var label = tdLabel.down('label');
           if (label) {
               return label;
           }
       }
    }

    return false;
}


/********************* GIFT OPTIONS POPUP ***********************/
class GiftOptionsPopup {

    giftOptionsWindowMask = null;
    giftOptionsWindow = null;

    constructor() {
        this.initialize();
    }

    initialize() {
        document.querySelectorAll('a[id^=gift_options_link_]').forEach((el) => {
            el.addEventListener('click', this.showItemGiftOptions.bind(this));
        });
    }

    showItemGiftOptions(event) {
        console.log(event)

        const itemId = event.target.id.replace('gift_options_link_', '');


        const productTitleEl = document.getElementById(`order_item_${itemId}_title`);
        const title = productTitleEl
              ? Translator.translate('Gift Options for') + ' ' + productTitleEl.textContent
              : Translator.translate('Gift Options');

        const template = document.getElementById('gift_options_configure').innerHTML;

        this.giftOptionsWindow = Dialog.info(template, {
            title,
            width: 600,
            height: 400,
            ok: this.onOkButton.bind(this),
            cancel: true,
        });
    }

    onOkButton() {
        const giftOptionsForm = new varienForm('gift_options_configuration_form');
        giftOptionsForm.canShowError = true;
        if (!giftOptionsForm.validate()) {
            return false;
        }
        giftOptionsForm.validator.reset();
        return true;
    }
};


/********************* GIFT OPTIONS SET ***********************/
class GiftMessageSet {

    destPrefix = 'current_item_giftmessage_';
    sourcePrefix = 'giftmessage_';
    fields = ['sender', 'recipient', 'message'];
    isObserved = false;

    constructor() {
        this.initialize();
    }

    initialize() {
        document.querySelectorAll('.action-link').forEach((el) => {
            el.addEventListener('click', this.setData.bind(this));
        });
    }

    setData(event) {
        this.id = event.target.id.replace('gift_options_link_', '');
        const formData = document.getElementById(`gift-message-form-data-${this.id}`);

        if (formData) {
            for (const field of this.fields) {
                const sourceEl = document.getElementById(`${this.sourcePrefix}${this.id}_${field}`);
                const destEl = document.getElementById(`${this.destPrefix}${field}`);
                if (sourceEl && destEl) {
                    destEl.value = sourceEl.value;
                }
            }
            toggleVis('gift_options_giftmessage', true);
        } else {
            toggleVis('gift_options_giftmessage', false);
        }

        if (!this.isObserved) {
            Event.observe('gift_options_ok_button', 'click', this.saveData.bind(this));
            this.isObserved = true;
        }
    }

    saveData(event){
        this.fields.each(function(el) {
            if ($(this.sourcePrefix + this.id + '_' + el) && $(this.destPrefix + el)) {
                $(this.sourcePrefix + this.id + '_' + el).value = $(this.destPrefix + el).value;
            }
        }, this);
        if ($(this.sourcePrefix + this.id + '_form')) {
            $(this.sourcePrefix + this.id + '_form').request();
        } else if (typeof(order) != 'undefined') {
            var data = order.serializeData('gift_options_data_' + this.id);
            order.loadArea(['items'], true, data.toObject());
        }
    }
};
