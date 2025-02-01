/**
 * Maho
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright   Copyright (c) 2021-2023 The OpenMage Contributors (https://openmage.org)
 * @license     https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

document.addEventListener('DOMContentLoaded', () => {
    window.productConfigure = new ProductConfigure();
});

class ProductConfigure
{

    listTypes =                  {};
    itemsFilter =                {};

    current =                    {};

    confirmedCurrentId =         null;
    confirmCallback =            {};
    cancelCallback =             {};
    onLoadIFrameCallback =       {};
    showWindowCallback =         {};
    beforeSubmitCallback =       {};
    _listTypeId =                1;

    constructor() {
        this.initialize(...arguments);
    }

    /**
     * Initialize object
     */
    initialize() {
        this.blockMsg           = document.getElementById('product_composite_configure_messages');
        this.blockConfirmed     = document.getElementById('product_composite_configure_confirmed');
        this.blockForm          = document.getElementById('product_composite_configure_form');
        this.blockFormFields    = document.getElementById('product_composite_configure_form_fields');
        this.blockFormAdd       = document.getElementById('product_composite_configure_form_additional');
        this.blockFormConfirmed = document.getElementById('product_composite_configure_form_confirmed');

        this.varienForm = new varienForm('product_composite_configure_form');
    }

    /**
     * Initialize window elements
     */
    _initWindowElements() {
    }

    registerConfigurableFieldset(config) {
        console.log('registerConfigurableFieldset', this.restorePhase, config);
        ProductConfigure.spConfig = new Product.Config({
            containerId: this.blockFormFields.id,
            inputsInitialized: this.restorePhase ? true : null,
            ...config,
        });
    }

    /**
     * Returns next unique list type id
     */
    _generateListTypeId () {
        return `_internal_lt_${this._listTypeId++}`;
    }

    /**
     * Add product list types as scope and their urls
     * expamle: addListType('product_to_add', {urlFetch: 'http://magento...'})
     * expamle: addListType('wishlist', {urlSubmit: 'http://magento...'})
     *
     * @param type types as scope
     * @param urls obj can be
     *             - {urlFetch: 'http://magento...'} for fetching configuration fields through ajax
     *             - {urlConfirm: 'http://magento...'} for submit configured data through iFrame when clicked confirm button
     *             - {urlSubmit: 'http://magento...'} for submit configured data through iFrame
     */
    addListType(type, urls) {
        this.listTypes[type] ??= {};
        Object.extend(this.listTypes[type], urls);
        return this;
    }

    /**
     * Adds complex list type - that is used to submit several list types at once
     * Only urlSubmit is possible for this list type
     * expamle: addComplexListType(['wishlist', 'product_list'], 'http://magento...')
     *
     * @param type types as scope
     * @param urls obj can be
     *             - {urlSubmit: 'http://magento...'} for submit configured data through iFrame
     * @return type string
     */
    addComplexListType(types, urlSubmit) {
        const type = this._generateListTypeId();
        this.listTypes[type] = { complexTypes: types, urlSubmit };
        return type;
    }

    /**
     * Add filter of items
     *
     * @param listType scope name
     * @param itemsFilter
     */
    addItemsFilter(listType, itemsFilter) {
        if (!listType || !itemsFilter) {
            return false;
        }
        this.itemsFilter[listType] ??= [];
        this.itemsFilter[listType].push(...itemsFilter);
        return this;
    }

    /**
     * Returns id of block where configuration for an item is stored
     *
     * @param listType scope name
     * @param itemId
     * @return string
     */
    _getConfirmedBlockId(listType, itemId) {
        return `${this.blockConfirmed.id}[${listType}][${itemId}]`;
    }

    _getConfirmedBlock(listType, itemId) {
        let blockEl = this.blockConfirmed.querySelector(`[data-list-type="${listType}"][data-item-id="${itemId}"]`);
        if (!blockEl) {
            blockEl = document.createElement('div');
            blockEl.id = this._getConfirmedBlockId(listType, itemId);
            blockEl.dataset.listType = listType;
            blockEl.dataset.itemId = itemId;
            this.blockConfirmed.appendChild(blockEl);
        }
        return blockEl;
    }

    _setConfirmedBlock(listType, itemId, blockValue) {
        const blockEl = this._getConfirmedBlock(listType, itemId);
        if (blockValue instanceof Element) {
            const formEl = blockValue.closest('form');
            if (formEl) {
                blockEl.formData = new FormData(formEl);
            }
            blockEl.innerHTML = blockValue.innerHTML;
        } else if (typeof blockValue === 'string') {
            blockEl.innerHTML = blockValue;
        } else {
            throw new TypeError('blockValue must be of type Element or String');
        }
        return blockEl;
    }

    /**
     * Checks whether item has some configuration fields
     *
     * @param listType scope name
     * @param itemId
     * @return bool
     */
    itemConfigured(listType, itemId) {
        return this._getConfirmedBlock(listType, itemId).children.length ? true : false;
    }

    /**
     * Show configuration fields of item, if it not found then get it through ajax
     *
     * @param listType scope name
     * @param itemId
     */
    async showItemConfiguration(listType, itemId) {
        if (!listType || !itemId) {
            return false;
        }

        this.current = { listType, itemId };
        this.confirmedCurrentId = this._getConfirmedBlockId(listType, itemId);

        if (!this.itemConfigured(listType, itemId)) {
            await this._requestItemConfiguration(listType, itemId);
        }

        this._showWindow();
    }

    /**
     * Fetch configuration form for product and store in product_composite_configure_confirmed element
     *
     * @param listType scope name
     * @param itemId
     */
    async _requestItemConfiguration(listType, itemId) {
        console.log('_requestItemConfiguration', listType, itemId)
        try {
            const url = this.listTypes[listType].urlFetch;
            if (!url) {
                throw new MahoError('Product configuration form request URL not specified.');
            }
            const blockHtml = await mahoFetch(url, {
                method: 'POST',
                body: new URLSearchParams({ id: itemId }),
            });

            this._setConfirmedBlock(listType, itemId, blockHtml);

        } catch (error) {
            console.error(error);
            setMessagesDiv(error.message, 'error');
        }
    }

    /**
     * Show configuration window
     */
    _showWindow() {
        this.window = Dialog.confirm(null, {
            title: Translator.translate('Configure Product'),
            ok: this.onConfirmBtn.bind(this),
            cancel: this.onCancelBtn.bind(this),
        });

        this._initWindowElements();

        this.window.querySelector('.dialog-content').appendChild(this.blockForm);
        this._processFieldsData('item_restore');

        if (typeof this.showWindowCallback[this.current.listType] === 'function') {
            this.showWindowCallback[this.current.listType]();
        }
    }

    /**
     * Close configuration window
     */
    _closeWindow() {
        this.blockConfirmed.insertAdjacentElement('afterend', this.blockForm);
        this.clean('window');

        //this.blockMsg.textContent = '';
        //this.blockFormFields.textContent = '';
    }

    /**
     * Triggered on confirm button click
     * Do submit configured data through iFrame if needed
     */
    onConfirmBtn() {
        if (!this.varienForm.validate()) {
            return false;
        }

        // This saves form via AJAX
        if (this.listTypes[this.current.listType].urlConfirm) {
            return this.submit();
        }

        this._processFieldsData('item_confirm');
        if (typeof this.confirmCallback[this.current.listType] === 'function') {
            this.confirmCallback[this.current.listType]();
        }

        this._closeWindow();
        return this;
    }

    /**
     * Triggered on cancel button click
     */
    onCancelBtn() {
        if (typeof this.cancelCallback[this.current.listType] === 'function') {
            this.cancelCallback[this.current.listType]();
        }
        this._closeWindow();
        return this;
    }

    /**
     * Submit configured data through iFrame
     *
     * @param listType scope name
     */
    async submit(listType) {

        try {
            const submitForm = async (url, extraFormData = null) => {
                if (typeof this.beforeSubmitCallback[this.current.listType] === 'function') {
                    this.beforeSubmitCallback[this.current.listType]();
                }
                const formData = new FormData(this.blockForm);
                for (const [ name, value ] of Object.entries(extraFormData ?? {})) {
                    formData.append(name, value);
                }
                const result = await mahoFetch(url, {
                    method: 'POST',
                    body: formData,
                });
                this.clean('current');

                if (typeof this.onLoadIFrameCallback[this.current.listType] === 'function') {
                    this.onLoadIFrameCallback[this.current.listType](result);
                }
                return true;
            }

            // prepare data
            if (listType) {
                this.current = { listType, itemId: null };
            }

            const { urlConfirm, urlSubmit } = this.listTypes[this.current.listType];

            if (urlConfirm) {
                return submitForm(setRouteParams(urlConfirm, { id: this.current.itemId }));
            }

            if (urlSubmit) {
                // Sales order create
                this._processFieldsData('current_confirmed_to_form');

                // Disable item controls that duplicate added fields (e.g. sometimes qty controls can intersect)
                // so they won't be submitted
                for (const element of this.blockFormConfirmed.querySelectorAll('input, select, textarea')) {
                    element.dataset.disabled = element.disabled;
                    if (this.blockFormAdd.querySelector(`[name="${element.name}"]`)) {
                        element.disabled = true;
                    }
                }
                const extraFormData = new FormData();
                const complexTypes = this.listTypes[this.current.listType].complexTypes;
                if (complexTypes) {
                    extraFormData.append('configure_complex_list_types', complexTypes.join(','));
                }

                const result = await submitForm(urlSubmit, extraFormData);

                // Re-enable item controls
                for (const element of this.blockFormConfirmed.querySelectorAll('input, select, textarea')) {
                    element.disabled = element.dataset.disabled;
                    delete element.dataset.disabled;
                }

                this._processFieldsData('form_confirmed_to_confirmed');

                return result;
            }

            throw new MahoError(
                'Product configuration form error: %s',
                Translator.translate('Save URL not specified. Product configuration will not be saved. Press Cancel to exit.'),
            );
        } catch (error) {
            console.error(error);
            setMessagesDiv(error.message, 'error'); // todo window messages
            return false;
        }
    }

    /**
     * Add dynamically additional fields for form
     *
     * @param fields
     */
    addFields(fields) {
        this.blockFormAdd.append(...fields);
        return this;
    }

    /**
     * Helper to find qty of currently confirmed item
     */
    getCurrentConfirmedQtyElement() {
        var elms = $(this.confirmedCurrentId).getElementsByTagName('input');
        for (var i = 0; i < elms.length; i++) {
            if (elms[i].name == 'qty') {
                return elms[i];
            }
        }
    }

    /**
     * Helper to find qty of active form
     */
    getCurrentFormQtyElement() {
        var elms = this.blockFormFields.getElementsByTagName('input');
        for (var i = 0; i < elms.length; i++) {
            if (elms[i].name == 'qty') {
                return elms[i];
            }
        }
    }

    /**
     * Attach callback function triggered when confirm button was clicked
     *
     * @param confirmCallback
     */
    setConfirmCallback(listType, confirmCallback) {
        this.confirmCallback[listType] = confirmCallback;
        return this;
    }

    /**
     * Attach callback function triggered when cancel button was clicked
     *
     * @param cancelCallback
     */
    setCancelCallback(listType, cancelCallback) {
        this.cancelCallback[listType] = cancelCallback;
        return this;
    }

    /**
     * Attach callback function triggered when iFrame was loaded
     *
     * @param onLoadIFrameCallback
     */
    setOnLoadIFrameCallback(listType, onLoadIFrameCallback) {
        this.onLoadIFrameCallback[listType] = onLoadIFrameCallback;
        return this;
    }

    /**
     * Attach callback function triggered when iFrame was loaded
     *
     * @param showWindowCallback
     */
    setShowWindowCallback(listType, showWindowCallback) {
        this.showWindowCallback[listType] = showWindowCallback;
        return this;
    }

    /**
     * Attach callback function triggered before submitting form
     *
     * @param beforeSubmitCallback
     */
    setBeforeSubmitCallback(listType, beforeSubmitCallback) {
        this.beforeSubmitCallback[listType] = beforeSubmitCallback;
        return this;
    }

    /**
     * Clean object data
     *
     * @param method can be 'all' or 'current'
     */
    clean(method) {
        var listInfo = null;
        var listTypes = null;
        var removeConfirmed = function (listTypes) {
            this.blockConfirmed.childElements().each(function(elm) {
                for (var i = 0, len = listTypes.length; i < len; i++) {
                   var pattern = this.blockConfirmed.id + '[' + listTypes[i] + ']';
                   if (elm.id.indexOf(pattern) == 0) {
                       elm.remove();
                       break;
                   }
                }
            }.bind(this));
        }.bind(this);

        switch (method) {
            case 'current':
                listInfo = this.listTypes[this.current.listType];
                listTypes = [this.current.listType];
                if (listInfo.complexTypes) {
                    listTypes = listTypes.concat(listInfo.complexTypes);
                }
                removeConfirmed(listTypes);
            break;
            case 'window':
                    this.blockFormFields.update();
                    this.blockMsg.hide();
            break;
            default:
                // search in list types for its cleaning
                if (this.listTypes[method]) {
                    listInfo = this.listTypes[method];
                    listTypes = [method];
                    if (listInfo.complexTypes) {
                        listTypes = listTypes.concat(listInfo.complexTypes);
                    }
                    removeConfirmed(listTypes);
                // clean all
                } else if (!method) {
                    this.current = {};
                    this.blockConfirmed.update();
                    this.blockFormFields.update();
                    this.blockMsg.hide();
                }
            break;
        }
        this.blockFormAdd.update();
        this.blockFormConfirmed.update();
        this.blockForm.action = '';

        return this;
    }


    cleanNew(method) {

        this.blockFormAdd.textContent = '';
        this.blockFormConfirmed.textContent = '';
        this.blockForm.action = '';

        if (!method || method === 'window') {
            this.blockFormFields.textContent = '';
            this.blockMsg.textContent = '';
        }

        if (method === 'current') {
            listInfo = this.listTypes[this.current.listType];
            listTypes = [this.current.listType];
            if (listInfo.complexTypes) {
                listTypes = listTypes.concat(listInfo.complexTypes);
            }
            removeConfirmed(listTypes);
        }



        if (!method) {
            this.current = {}
            return this;
        }

        if (!this.listTypes[method]) {
            return this;
        }

        const removeConfirmed = (listTypes) => {
            for (const containerEl of this.blockConfirmed.children) {
                for (const listType of listTypes) {
                    if (containerEl.id.startsWith(`${this.blockConfirmed.id}[${listType}]`)) {
                        containerEl.remove();
                        break;
                    }
                }
            }
        };

        if (this.listTypes[method]) {
            console.log(`clean ${method}`);
            // search in list types for its cleaning
            // removeConfirmed([ method, ...this.listTypes[method]?.complexTypes ]);

        } else if (!method) {
            this.current = {}
        }


        return this;
    }

    /**
     * Process fields data: save, restore, move saved to form and back
     *
     * @param method can be 'item_confirm', 'item_restore', 'current_confirmed_to_form', 'form_confirmed_to_confirmed'
     */
    _processFieldsData(method) {
        const { listType, itemId } = this.current;
        const listInfo = this.listTypes[listType];

        console.log('_processFieldsData', method, listType, itemId)


        if (method === 'item_confirm') {
            const blockEl = this._setConfirmedBlock(listType, itemId, this.blockFormFields.firstElementChild);

        }

        if (method === 'item_restore') {
            const blockEl = this._getConfirmedBlock(listType, itemId);

            this.blockFormFields.replaceChildren(blockEl.cloneNode(true));

            if (blockEl.formData) {
                restoreFormData(this.blockForm, blockEl.formData);
                this.restorePhase = true;
                console.log('HAVE FORM DATA', Object.fromEntries(blockEl.formData))
            } else {
                console.log('NO FORM DATA');
            }

            for (const oldScriptEl of this.blockFormFields.querySelectorAll('script:not([src])')) {
                const newScriptEl = document.createElement('script');
                //console.log(oldScriptEl.innerHTML)
                newScriptEl.appendChild(document.createTextNode(oldScriptEl.innerHTML));
                oldScriptEl.replaceWith(newScriptEl);
            }
            this.restorePhase = false;

        }

        if (method === 'current_confirmed_to_form') {
            const allowedListTypes = {
                [this.current.listType]: true,
            };
            for (let complexType of listInfo.complexTypes ?? []) {
                allowedListTypes[complexType] = true;
            }

            // Clear div element
            this.blockFormConfirmed.replaceChildren();

            for (const blockConfirmed of this.blockConfirmed.children) {
                const { listType, itemId } = blockConfirmed.dataset;

                if (!allowedListTypes[listType]) {
                    continue;
                }
                if (this.itemsFilter[listType] && this.itemsFilter[listType].indexOf(itemId) === -1) {
                    continue;
                }

                const blockFormConfirmed = this.blockFormConfirmed.appendChild(blockConfirmed.cloneNode(true));
                restoreFormData(blockFormConfirmed, blockConfirmed.formData ?? new FormData());
                this._renameFields(method, blockFormConfirmed, listInfo.complexTypes ? true : false);
            }

        }

        if (method === 'form_confirmed_to_confirmed') {
            // for (const blockFormConfirmed of this.blockFormConfirmed.children) {
            //     const { listType, itemId } = blockFormConfirmed.dataset;
            //     this._renameFields(method, blockFormConfirmed, listInfo.complexTypes ? true : false);
            //     const blockConfirmed = this._getConfirmedBlock(listType, itemId);
            //     blockConfirmed.replaceWith(blockFormConfirmed.cloneNode(true));
            // }
        }
    }

    /**
     * Internal function for rename fields names of some list type
     *
     * If usePrefix is true, fields will be renamed to `list[$listType][item][$itemId]`
     *            otherwise, fields will be renamed to `item[$itemId]` and back
     *
     * @param method can be 'current_confirmed_to_form', 'form_confirmed_to_confirmed'
     * @param blockItem
     * @param usePrefix
     */
    _renameFields(method, blockItem, usePrefix) {
        const { listType, itemId } = blockItem.dataset;

        let pattern, patternFlat;
        let replacement, replacementFlat;
        if (method == 'current_confirmed_to_form') {
            pattern         = RegExp('(\\w+)(\\[?)');
            patternFlat     = RegExp('(\\w+)');
            replacement     = 'item[' + itemId + '][$1]$2';
            replacementFlat = 'item_' + itemId + '_$1';
            if (usePrefix) {
                replacement = 'list[' + listType + '][item][' + itemId + '][$1]$2';
                replacementFlat = 'list_' + listType + '_' + replacementFlat;
            }
        } else if (method == 'form_confirmed_to_confirmed') {
            var stPattern = 'item\\[' + itemId + '\\]\\[(\\w+)\\](.*)';
            var stPatternFlat = 'item_' + itemId + '_(\\w+)';
            if (usePrefix) {
                stPattern = 'list\\[' + listType + '\\]\\[item\\]\\[' + itemId + '\\]\\[(\\w+)\\](.*)';
                stPatternFlat = 'list_' + listType + '_' + stPatternFlat;
            }
            pattern         = new RegExp(stPattern);
            patternFlat     = new RegExp(stPatternFlat);
            replacement     = '$1$2';
            replacementFlat = '$1';
        } else {
            return false;
        }
        for (const inputEl of blockItem.querySelectorAll('input[name], select[name], textarea[name]')) {
            inputEl.name = inputEl.type === 'file'
                ? inputEl.name.replace(patternFlat, replacementFlat)
                : inputEl.name.replace(pattern, replacement)
        };
    }

    _convertNameToForm(name, listType, itemId) {
        const pattern = /(\w+)(\[?)/;
        const replacement = usePrefix
              ? `list[${listType}][item][${itemId}][$1]$2`
              : `item[${itemId}][$1]$2`
        return name.replace(pattern, replacement);
    }
    _convertNameToFormFlat(name, listType, itemId) {
        const pattern = /(\w+)/;
        const replacement = usePrefix
              ? `list_${listType}_item_${itemId}_$1`
              : `item_${itemId}_$1`;
        return name.replace(pattern, replacement);
    }

    _convertNameToConfirmed(name, listType, itemId) {
        const pattern = usePrefix
              ? new RegExp(`item\[${itemId}\]\[(\w+)\](.*)`)
              : new RegExp(`list\[${listType}\]\[item\]\[${itemId}\]\[(\w+)\](.*)`);
        const replacement = '$1$2';
        return name.replace(pattern, replacement);
    }
    _convertNameToConfirmedFlat(name, listType, itemId) {
        const pattern = usePrefix
              ? new RegExp(`item_${itemId}_(\w+)`)
              : new RegExp(`list_${listType}_item_${itemId}_(\w+)`);
        const replacement = '$1';
        return name.replace(pattern, replacement);
    }

    /**
     * Check if qty selected correctly
     *
     * @param object element
     * @param object event
     */
    changeOptionQty(element, event) {
        var checkQty = true;
        if ('undefined' != typeof event) {
            if (event.keyCode == 8 || event.keyCode == 46) {
                checkQty = false;
            }
        }
        if (checkQty && (Number(element.value) <= 0 || isNaN(Number(element.value)))) {
            element.value = 1;
        }
    }
};
