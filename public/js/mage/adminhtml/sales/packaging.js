/**
 * Maho
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright   Copyright (c) 2022 The OpenMage Contributors (https://openmage.org)
 * @copyright   Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license     https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
class Packaging
{
    constructor() {
        this.initialize(...arguments);
    }

    /**
     * Initialize object
     */
    initialize(params) {
        console.log(params)

        this.packageIncrement = 0;
        this.packages = [];
        this.itemsAll = [];
        this.createLabelUrl = params.createLabelUrl ? params.createLabelUrl : null;
        this.itemsGridUrl = params.itemsGridUrl ? params.itemsGridUrl : null;
        this.errorQtyOverLimit = params.errorQtyOverLimit;
        this.titleDisabledSaveBtn = params.titleDisabledSaveBtn;

        /*
        this.window = $('packaging_window');
        this.windowMask = $('popup-window-mask');
        this.packagesContent = $('packages_content');
        this.template = $('package_template');
        */

        this.paramsCreateLabelRequest = {};
        this.validationErrorMsg = params.validationErrorMsg;

        this.defaultItemsQty            = params.shipmentItemsQty ? params.shipmentItemsQty : null;
        this.defaultItemsPrice          = params.shipmentItemsPrice ? params.shipmentItemsPrice : null;
        this.defaultItemsName           = params.shipmentItemsName ? params.shipmentItemsName : null;
        this.defaultItemsWeight         = params.shipmentItemsWeight ? params.shipmentItemsWeight : null;
        this.defaultItemsProductId      = params.shipmentItemsProductId ? params.shipmentItemsProductId : null;
        this.defaultItemsOrderItemId    = params.shipmentItemsOrderItemId ? params.shipmentItemsOrderItemId : null;

        this.shippingInformation= params.shippingInformation ? params.shippingInformation : null;
        this.thisPage           = params.thisPage ? params.thisPage : null;
        this.customizableContainers = params.customizable ? params.customizable : [];

        this.eps = .000001;
    }

    setLabelCreatedCallback(callback) {
        this.labelCreatedCallback = callback;
    }
    setCancelCallback(callback) {
        this.cancelCallback = callback;
    }
    setConfirmPackagingCallback(callback) {
        this.confirmPackagingCallback = callback;
    }
    setItemQtyCallback(callback) {
        this.itemQtyCallback = callback;
    }
    setCreateLabelUrl(url) {
        this.createLabelUrl = url;
    }
    setParamsCreateLabelRequest(params) {
        Object.extend(this.paramsCreateLabelRequest, params);
    }

    showWindow() {
        const template = document.getElementById('packaging_window_template');
        if (!template) {
            return;
        }

        this.window = Dialog.info(template.innerHTML, {
            title: 'Create Packages', // TODO translate
            className: 'packaging-window',
            ok: this.confirmPackaging.bind(this),
            cancel: this.cancelPackaging.bind(this),
        });

        this.packagesContent = this.window.querySelector('#packages_content');
        if (this.packagesContent.children.length === 0) {
            this.newPackage();
        }
    }

    updateMessage(message) {
        const block = this.window.querySelector('.messages');
        block.innerHTML = message;
        toggleVis(block, true);
    }

    clearMessage() {
        const block = this.window.querySelector('.messages');
        block.textContent = '';
        toggleVis(block, false);
    }

    cancelPackaging() {
        if (typeof this.cancelCallback === 'function') {
            this.cancelCallback();
        }
    }

    confirmPackaging(params) {
        if (typeof this.confirmPackagingCallback === 'function') {
            this.confirmPackagingCallback();
        }
        return false;
    }

    checkAllItems(headCheckbox) {
        checkAllItems.closest('table').querySelectorAll('tbody input[type=checkbox]').forEach((checkbox) => {
            checkbox.checked = headCheckbox.checked;
            this._observeQty.call(checkbox);
        });
    }

    cleanPackages() {
        this.packagesContent.update();
        this.packages = [];
        this.itemsAll = [];
        this.packageIncrement = 0;
        this._setAllItemsPackedState();
        this.clearMessage();
    }

    sendCreateLabelRequest() {
        if (!this.validate()) {
            this.updateMessage(this.validationErrorMsg);
            return;
        }

        this.clearMessage();
        if (!this.createLabelUrl) {
            return;
        }

        const packagesParams = [];

        for (const packageBlock of this.packagesContent.children) {
            const packageId = packageBlock.dataset.id;
            const weight = parseFloat(packageBlock.querySelector('input[name=container_weight]').value);
            const length = parseFloat(packageBlock.querySelector('input[name=container_length]').value);
            const width  = parseFloat(packageBlock.querySelector('input[name=container_width]').value);
            const height = parseFloat(packageBlock.querySelector('input[name=container_height]').value);

            packagesParams[packageId] = {
                container:       packageBlock.querySelector('select[name=package_container]').value,
                weight_units:    packageBlock.querySelector('select[name=container_weight_units]').value,
                dimension_units: packageBlock.querySelector('select[name=container_dimension_units]').value,
                weight:          isNaN(weight) ? '' : weight,
                length:          isNaN(length) ? '' : length,
                width:           isNaN(width)  ? '' : width,
                height:          isNaN(height) ? '' : height,
            };

            const customsValue = packageBlock.querySelector('input[name=package_customs_value]');
            if (parseFloat(customsValue?.value)) {
                packagesParams[packageId]['customs_value'] = parseFloat(customsValue.value);
            } else {
                packagesParams[packageId]['customs_value'] = 0;
            }

            const packageSize = packageBlock.querySelector('select[name=package_size]');
            if (packageSize?.value) {
                packagesParams[packageId]['size'] = packageSize.value;
            }

            const containerGirth = packageBlock.querySelector('select[name=container_girth]');
            const containerGirthDimensionUnits = packageBlock.querySelector('select[name=container_girth_dimension_units]');
            if (containerGirth?.value) {
                packagesParams[packageId]['girth'] = containerGirth.value;
                packagesParams[packageId]['girth_dimension_units'] = containerGirthDimensionUnits?.value;
            }

            const contentType = packageBlock.querySelector('select[name=content_type]');
            const contentTypeOther = packageBlock.querySelector('select[name=content_type_other]');
            if (contentType && contentTypeOther) {
                packagesParams[packageId]['content_type'] = contentType.value;
                packagesParams[packageId]['content_type_other'] = contentTypeOther.value;
            } else {
                packagesParams[packageId]['content_type'] = '';
                packagesParams[packageId]['content_type_other'] = '';
            }

            const deliveryConfirmation = packageBlock.querySelector('select[name=delivery_confirmation_types]');
            if (deliveryConfirmation) {
                packagesParams[packageId]['delivery_confirmation'] =  deliveryConfirmation.value;
            }
        }

        for (const packageId of Object.keys(this.packages)) {
            if (isNaN(packageId) || !packagesParams[packageId]) {
                continue;
            }

            for (const [ key, val ] of Object.entries(packagesParams[packageId])) {
                this.paramsCreateLabelRequest[`packages[${packageId}][params][${key}]`] = val;
            }

            for (const [ packedItemId, packedItem ] of Object.entries(this.packages[packageId]['items'])) {
                if (isNaN(packedItemId)) {
                    continue;
                }

                for (const [ key, val ] of Object.entries(packedItem)) {
                    this.paramsCreateLabelRequest[`packages[${packageId}][items][${packedItemId}][${key}]`] = val;
                }

                this.paramsCreateLabelRequest[`packages[${packageId}][items][${packedItemId}][price]`] =
                    this.defaultItemsPrice[packedItemId];
                this.paramsCreateLabelRequest[`packages[${packageId}][items][${packedItemId}][name]`] =
                    this.defaultItemsName[packedItemId];
                this.paramsCreateLabelRequest[`packages[${packageId}][items][${packedItemId}][weight]`] =
                    this.defaultItemsWeight[packedItemId];
                this.paramsCreateLabelRequest[`packages[${packageId}][items][${packedItemId}][product_id]`] =
                    this.defaultItemsProductId[packedItemId];
                this.paramsCreateLabelRequest[`packages[${packageId}][items][${packedItemId}][order_item_id]`] =
                    this.defaultItemsOrderItemId[packedItemId];
            }
        }

        new Ajax.Request(this.createLabelUrl, {
            parameters: this.paramsCreateLabelRequest,
            onSuccess: (transport) => {
                var response = transport.responseText;
                if (response.isJSON()) {
                    response = response.evalJSON();
                    if (response.error) {
                        this.updateMessage(response.message);
                    } else if (response.ok && Object.isFunction(this.labelCreatedCallback)) {
                        this.labelCreatedCallback(response);
                    }
                }
            }
        });

        const { code, carrier_title, method_title, price } = this.paramsCreateLabelRequest;

        if (code && carrier_title && method_title && price) {
            this.paramsCreateLabelRequest = { code, carrier_title, method_title, price };
        } else {
            this.paramsCreateLabelRequest = {};
        }
    }

    validate() {
        const dimensionElements = this.window.querySelectorAll(
            'input[name=container_length], input[name=container_width], input[name=container_height]'
        );

        // If at least one dimensional el has a value, all must be filled
        const dimensionRequired = Array.from(dimensionElements).some((el) => !!el.value);
        for (const el of dimensionElements) {
            el.classList.toggle('required-entry', dimensionRequired);
        }

        const valid = Array.from(this.window.querySelectorAll('[id^=package_block] input')).map((el) => this.validateElement(el));
        return valid.every(Boolean);
    }

    validateElement(el) {
        for (const value of el.classList) {
            if (Validation.isVisible(el) && !Validation.get(value).test(el.value, el)) {
                el.classList.add('validation-failed');
                return false;
            }
        }
        el.classList.remove('validation-failed');
        return true;
    }

    validateCustomsValue() {
        const items = [];
        for (const packageBlock of this.packagesContent.children) {
            const itemsPrepare = packageBlock.querySelector('.package_prepare, .package_prapare');
            if (itemsPrepare) {
                items.push(...itemsPrepare.querySelectorAll('.grid tbody tr'));
            }
            const itemsPacked = packageBlock.querySelector('.package_items');;
            if (itemsPacked) {
                items.push(...itemsPacked.select('.grid tbody tr'));
            }
        }

        let isValid = true;
        for (const item of items) {
            const itemCustomsValue = item.querySelector('[name=customs_value]');
            if (!this.validateElement(itemCustomsValue)) {
                isValid = false;
            }
        }

        if (isValid) {
            this.clearMessage();
        } else {
            this.updateMessage(this.validationErrorMsg);
        }
        return isValid;
    }

    newPackage() {
        const template = document.getElementById('packaging_package_template');

        const packageBlock = document.createElement('div');
        packageBlock.innerHTML = template.innerHTML;
        packageBlock.classList.add('package-block');
        packageBlock.dataset.id = ++this.packageIncrement;
        packageBlock.id = `package_block_${this.packageIncrement}`;
        packageBlock.querySelector('.package-number span').textContent = this.packageIncrement;
        packageBlock.select('.AddSelectedBtn')[0].hide();

        this.packagesContent.appendChild(packageBlock);
    }

    deletePackage(obj) {
        const packageBlock = obj.closest('div[id^=package_block]');
        const packageId = packageBlock.dataset.id;

        delete this.packages[packageId];
        packageBlock.remove();
        this.clearMessage();
        this._setAllItemsPackedState();
    }

    deleteItem(obj) {
        const item = obj.closest('tr');
        const itemId = item.querySelector('[type=checkbox]').value;

        const packageBlock = obj.closest('[id^=package_block]');
        const packageItems = packageBlock.querySelector('.package_items');
        const packageId = packageBlock.dataset.id;

        delete this.packages[packageId]['items'][itemId];
        item.remove();

        if (item.parentElement.rows.length === 0) {
            toggleVis(packageItems, false);
        }

        this.clearMessage();
        this._recalcContainerWeightAndCustomsValue(packItems);
        this._setAllItemsPackedState();
    }

    recalcContainerWeightAndCustomsValue(obj) {
        var pack = $(obj).up('div[id^="package_block"]');
        var packItems = pack.select('.package_items')[0];
        if (packItems) {
            if (!this.validateCustomsValue()) {
                return;
            }
            this._recalcContainerWeightAndCustomsValue(packItems);
        }
    }

    async getItemsForPack(obj) {
        if (!this.itemsGridUrl) {
            return;
        }

        const packageBlock = obj.closest('[id^=package_block]');
        const packagePrepare = packageBlock.querySelector('.package_prepare, .package_prepare');
        const productGrid = packagePrepare.querySelector('.grid_prepare');

        try {
            const html = await mahoFetch(this.itemsGridUrl, {
                method: 'POST',
                body: new URLSearchParams({
                    //shipment_id: this.shipmentId,
                }),
            })

            updateElementHtmlAndExecuteScripts(productGrid, html);
            this._processPackagePrepare(productGrid);

            if (productGrid.select('.grid tbody tr').length) {
                packageBlock.select('.AddItemsBtn')[0].hide();
                packageBlock.select('.AddSelectedBtn')[0].show();
                packagePrepare.show();
            } else {
                productGrid.textContent = '';
            }

        } catch (error) {
            console.error(error)
            productGrid.textContent = '';
        }
    }

    getPackedItemsQty() {
        var items = [];
        for (var packageId in this.packages) {
             if (!isNaN(packageId)) {
                 for (var packedItemId in this.packages[packageId]['items']) {
                     if (!isNaN(packedItemId)) {
                         if (items[packedItemId]) {
                             items[packedItemId] += this.packages[packageId]['items'][packedItemId]['qty'];
                         } else {
                             items[packedItemId] = this.packages[packageId]['items'][packedItemId]['qty'];
                         }
                     }
                 }
             }
        }
        return items;
    }

    _parseQty(obj) {
        const qty = obj.classList.contains('qty-decimal')
            ? parseFloat(obj.value)
            : parseInt(obj.value);

        if (isNaN(qty) || qty <= 0) {
            return 1;
        }
        return qty;
    }

    packItems(obj) {
        const packageBlock = obj.closest('[id^=package_block]');
        const packageId = packageBlock.dataset.id;
        const packagePrepare = packageBlock.querySelector('.package_prepare, .package_prepare');
        const productGrid = packagePrepare.querySelector('.grid_prepare');

        this.clearMessage();

        // Parse qty inputs
        for (const item of productGrid.querySelectorAll('.grid tbody tr')) {
            const qtyInput = item.querySelector('[name=qty]');
            qtyInput.value = this._parseQty(qtyInput);
        }

        // Check if qty exceeds the total shipped quantity
        let validateQty = true;
        for (const item of productGrid.querySelectorAll('.grid tbody tr')) {
            const checkbox = item.querySelector('[type=checkbox]');
            const qtyInput = item.querySelector('[name=qty]');
            if (checkbox.checked && this._checkExceedsQty(checkbox.value, qtyInput.value)) {
                qtyInput.classList.add('validation-failed');
                validateQty = false;
            } else {
                qtyInput.classList.remove('validation-failed');
            }
        }

        if (!validateQty) {
            this.updateMessage(this.errorQtyOverLimit);
            return;
        }

        if (!this.validateCustomsValue()) {
            return;
        }

        // Prepare items for packing and hide unselected rows
        let anySelected = false;
        for (const item of productGrid.querySelectorAll('.grid tbody tr')) {
            const checkbox = item.querySelector('[type=checkbox]');
            const qtyInput = item.querySelector('[name=qty]');

            if (checkbox.checked) {
                anySelected = true;
                qtyInput.disabled = true;
                toggleVis(checkbox.closest('td'), false);
                toggleVis(item.querySelector('.delete'), true);
            } else {
                item.remove();
            }
        }

        // packing items
        if (anySelected) {
            toggleVis(productGrid.querySelector('.grid th:has([type=checkbox])'), false);


            var packItems = packageBlock.select('.package_items')[0];
            if (!packItems) {
                packagePrepare.insert(new Element('div').addClassName('grid_prepare'));
                packagePrepare.insert({after: productGrid});
                packItems = productGrid.removeClassName('grid_prepare').addClassName('package_items');
                packItems.select('.grid tbody tr').each(function(item) {
                    var itemId = item.select('[type="checkbox"]')[0].value;
                    var qtyValue  = parseFloat(item.select('[name="qty"]')[0].value);
                    qtyValue = (qtyValue <= 0) ? 1 : qtyValue;

                    if ('undefined' == typeof this.packages[packageId]) {
                        this.packages[packageId] = {'items': [], 'params': {}};
                    }
                    if ('undefined' == typeof this.packages[packageId]['items'][itemId]) {
                        this.packages[packageId]['items'][itemId] = {};
                        this.packages[packageId]['items'][itemId]['qty'] = qtyValue;
                    } else {
                        this.packages[packageId]['items'][itemId]['qty'] += qtyValue;
                    }
                }.bind(this));
            } else {
                productGrid.select('.grid tbody tr').each(function(item) {
                    var itemId = item.select('[type="checkbox"]')[0].value;
                    var qtyValue  = parseFloat(item.select('[name="qty"]')[0].value);
                    qtyValue = (qtyValue <= 0) ? 1 : qtyValue;

                    if ('undefined' == typeof this.packages[packageId]['items'][itemId]) {
                        this.packages[packageId]['items'][itemId] = {};
                        this.packages[packageId]['items'][itemId]['qty'] = qtyValue;
                        packItems.select('.grid tbody')[0].insert(item);
                    } else {
                        this.packages[packageId]['items'][itemId]['qty'] += qtyValue;
                        var packItem = packItems.select('[type="checkbox"][value="'+itemId+'"]')[0].up('tr').select('[name="qty"]')[0];
                        packItem.value = this.packages[packageId]['items'][itemId]['qty'];
                    }
                }.bind(this));
                productGrid.update();
            }
            $(packItems).show();
            this._recalcContainerWeightAndCustomsValue(packItems);
        } else {
            productGrid.update();
        }

        // show/hide disable/enable
        packagePrepare.hide();
        packageBlock.select('.AddSelectedBtn')[0].hide();
        packageBlock.select('.AddItemsBtn')[0].show();
        this._setAllItemsPackedState();
    }

    validateItemQty (itemId, qty) {
        return (this.defaultItemsQty[itemId] < qty) ? this.defaultItemsQty[itemId] : qty;
    }

    changeMeasures(obj) {
        var incr = 0;
        var incrSelected = 0;
        obj.childElements().each(function(option) {
            if (option.selected) {
                incrSelected = incr;
            }
            incr++;
        }.bind(this));

        var packageBlock = $(obj).up('[id^="package_block"]');
        packageBlock.select('.measures').each(function(item){
            if (item.name != obj.name) {
                var incr = 0;
                item.select('option').each(function(option){
                    if (incr == incrSelected) {
                        item.value = option.value;
                        //option.selected = true
                    }
                    incr++;
                }.bind(this));
            }
        }.bind(this));

    }

    checkSizeAndGirthParameter(obj, enabled) {
        if (enabled == 0) {
            return;
        }
        var currentNode = obj;

        while (currentNode.nodeName != 'TBODY') {
            currentNode = currentNode.parentNode;
        }
        if (!currentNode) {
            return;
        }

        var packageSize = currentNode.select('select[name=package_size]');
        var packageContainer = currentNode.select('select[name=package_container]');
        var packageGirth = currentNode.select('input[name=container_girth]');
        var packageGirthDimensionUnits = currentNode.select('select[name=container_girth_dimension_units]');

        if (packageSize.length <= 0) {
            return;
        }

        var girthEnabled = (packageSize[0].value == 'LARGE' && (packageContainer[0].value == 'NONRECTANGULAR'
            || packageContainer[0].value == 'VARIABLE' ));

        if (!girthEnabled) {
            packageGirth[0].value='';
            packageGirth[0].disable();
            packageGirth[0].addClassName('disabled');
            packageGirthDimensionUnits[0].disable();
            packageGirthDimensionUnits[0].addClassName('disabled');
        } else {
            packageGirth[0].enable();
            packageGirth[0].removeClassName('disabled');
            packageGirthDimensionUnits[0].enable();
            packageGirthDimensionUnits[0].removeClassName('disabled');
        }

        var sizeEnabled = (packageContainer[0].value == 'NONRECTANGULAR' || packageContainer[0].value == 'RECTANGULAR'
            || packageContainer[0].value == 'VARIABLE');

        if (!sizeEnabled) {
            option = document.createElement('OPTION');
            option.value = '';
            option.text = '';
            packageSize[0].options.add(option);
            packageSize[0].value = '';
            packageSize[0].disable();
            packageSize[0].addClassName('disabled');
        } else {
            for (i = 0; i < packageSize[0].length; i ++) {
                if (packageSize[0].options[i].value == '') {
                    packageSize[0].removeChild(packageSize[0].options[i]);
                }
            }
            packageSize[0].enable();
            packageSize[0].removeClassName('disabled');
        }
    }

    changeContainerType(obj) {
        if (this.customizableContainers.length <= 0) {
            return;
        }

        var disable = true;
        for (var i in this.customizableContainers) {
            if (this.customizableContainers[i] == obj.value) {
                disable = false;
                break;
            }
        }

        var currentNode = obj;
        while (currentNode.nodeName != 'TBODY') {
            currentNode = currentNode.parentNode;
        }
        if (!currentNode) {
            return;
        }

        $(currentNode).select(
            'input[name=container_length],input[name=container_width],input[name=container_height],select[name=container_dimension_units]'
        ).each(function(inputElement) {
            if (disable) {
                Form.Element.disable(inputElement);
                inputElement.addClassName('disabled');
                if (inputElement.nodeName == 'INPUT') {
                    $(inputElement).value = '';
                }
            } else {
                Form.Element.enable(inputElement);
                inputElement.removeClassName('disabled');
            }
        });
    }

    changeContentTypes(obj) {
        var packageBlock = $(obj).up('[id^="package_block"]');
        var contentType = packageBlock.select('[name=content_type]')[0];
        var contentTypeOther = packageBlock.select('[name=content_type_other]')[0];
        if (contentType.value == 'OTHER') {
            Form.Element.enable(contentTypeOther);
            contentTypeOther.removeClassName('disabled');
        } else {
            Form.Element.disable(contentTypeOther);
            contentTypeOther.addClassName('disabled');
        }

    }

//******************** Private functions **********************************//
    _getItemsCount(items) {
        var count = 0;
        items.each(function(itemCount) {
            if (!isNaN(itemCount)) {
                count += parseFloat(itemCount);
            }
        }.bind(this));
        return count;
    }

    /**
     * Show/hide disable/enable buttons in case of all items packed state
     */
    _setAllItemsPackedState() {
        var addPackageBtn = this.window.select('.AddPackageBtn')[0];
        var savePackagesBtn = this.window.select('.SavePackagesBtn')[0];
        if (this._getItemsCount(this.itemsAll) > 0
                && (this._checkExceedsQtyFinal(this._getItemsCount(this.getPackedItemsQty()),this._getItemsCount(this.itemsAll)))
        ) {
            this.packagesContent.select('.AddItemsBtn').each(function(button){
                button.disabled = 'disabled';
                button.addClassName('disabled');
            });
            addPackageBtn.addClassName('disabled');
            Form.Element.disable(addPackageBtn);
            savePackagesBtn.removeClassName('disabled');
            Form.Element.enable(savePackagesBtn);
            savePackagesBtn.title = '';

            // package number recalculation
            var packagesRecalc = [];
            this.packagesContent.childElements().each(function(pack) {
                if (!pack.select('.package_items .grid tbody tr').length) {
                    pack.remove();
                }
            }.bind(this));
            var packagesCount = this.packagesContent.childElements().length;
            this.packageIncrement = packagesCount;
            this.packagesContent.childElements().each(function(pack) {
                var packageId = pack.id.match(/\d$/)[0];
                pack.id = 'package_block_' + packagesCount;
                pack.select('.package-number span')[0].update(packagesCount);
                packagesRecalc[packagesCount] = this.packages[packageId];
                --packagesCount;
            }.bind(this));
            this.packages = packagesRecalc;

        } else {
            this.packagesContent.select('.AddItemsBtn').each(function(button){
                button.removeClassName('disabled');
                Form.Element.enable(button);
            });
            addPackageBtn.removeClassName('disabled');
            Form.Element.enable(addPackageBtn);
            savePackagesBtn.addClassName('disabled');
            Form.Element.disable(savePackagesBtn);
            savePackagesBtn.title = this.titleDisabledSaveBtn;
        }
    }

    _processPackagePrapare(packagePrepare) {
        this._processPackagePrepare(packagePrepare);
    }

    _processPackagePrepare(packagePrapare) {
        var itemsAll = [];
        packagePrapare.select('.grid tbody tr').each(function(item) {
            var qty  = item.select('[name="qty"]')[0];
            var itemId = item.select('[type="checkbox"]')[0].value;
            var qtyValue = 0;
            if (Object.isFunction(this.itemQtyCallback)) {
                var value = this.itemQtyCallback(itemId);
                qtyValue = ((typeof value == 'string') && (value.length == 0)) ? 0 : parseFloat(value);
                if (isNaN(qtyValue) || qtyValue < 0) {
                    qtyValue = 1;
                }
                qtyValue = this.validateItemQty(itemId, qtyValue);
                qty.value = qtyValue;
            } else {
                var value = item.select('[name="qty"]')[0].value;
                qtyValue = ((typeof value == 'string') && (value.length == 0)) ? 0 : parseFloat(value);
                if (isNaN(qtyValue) || qtyValue < 0) {
                    qtyValue = 1;
                }
            }
            if (qtyValue == 0) {
                item.remove();
                return;
            }
            var packedItems = this.getPackedItemsQty();
            itemsAll[itemId] = qtyValue;
            for (var packedItemId in packedItems) {
                if (!isNaN(packedItemId)) {
                    var packedQty = packedItems[packedItemId];
                    if (itemId == packedItemId) {
                        if (qtyValue == packedQty || qtyValue <= packedQty) {
                            item.remove();
                        } else if (qtyValue > packedQty) {
                            /* fix float number precision */
                            qty.value = Number((qtyValue - packedQty).toFixed(4));
                        }
                    }
                }
            }
        }.bind(this));
        if (!this.itemsAll.length) {
            this.itemsAll = itemsAll;
        }

        packagePrapare.select('tbody input[type="checkbox"]').each(function(item){
            $(item).observe('change', this._observeQty);
            this._observeQty.call(item);
        }.bind(this));
    }

    _observeQty() {
        /** this = input[type="checkbox"] */
        var tr  = this.parentNode.parentNode,
            qty = $(tr.cells[tr.cells.length - 1]).select('input[name="qty"]')[0];

        if (qty.disabled = !this.checked) {
            $(qty).addClassName('disabled');
        } else {
            $(qty).removeClassName('disabled');
        }
    }

    _checkExceedsQty(itemId, qty) {
        var packedItemQty = this.getPackedItemsQty()[itemId] ? this.getPackedItemsQty()[itemId] : 0;
        var allItemQty = this.itemsAll[itemId];
        return (qty * (1 - this.eps) > (allItemQty *  (1 + this.eps)  - packedItemQty * (1 - this.eps)));
    }

    _checkExceedsQtyFinal(checkOne, defQty) {
        return checkOne * (1 + this.eps) >= defQty * (1 - this.eps);
    }

    _recalcContainerWeightAndCustomsValue(container) {
        const packageBlock = container.closest('[id^=package_block]');
        const packageId = packageBlock.dataset.id;

        let weight = 0, customsValue = 0;



        const containerWeight = packageBlock.querySelector('[name=container_weight]');
        if (containerWeight) {
            containerWeight.value = 0;
        }

        const containerCustomsValue = packageBlock.querySelector('[name=package_customs_value]');
        if (containerCustomsValue) {
            containerCustomsValue.value = 0;
        }

        for (const item of productGrid.querySelectorAll('.grid tbody tr')) {
            const itemId = item.querySelector('[type=checkbox]').value;
            const qtyInput = item.querySelector('[name=qty]');
            const qtyValue = this._parseQty(qtyInput);

            qtyInput.value = qtyValue;

            const itemWeight = parseFloat(this._getElementText(item.querySelector('.weight')));
            if (containerWeight) {
                containerWeight.value = parseFloat(containerWeight.value) + (itemWeight * qtyValue);
            }

            const itemCustomsValue = parseFloat(item.select('[name="customs_value"]')[0].value) || 0;


            containerCustomsValue.value = parseFloat(containerCustomsValue.value) + itemCustomsValue * qtyValue;
            this.packages[packageId]['items'][itemId]['customs_value'] = itemCustomsValue;
        }.bind(this));

        containerWeight.value = parseFloat(parseFloat(containerWeight.value).toFixed(4));
        containerCustomsValue.value = parseFloat(containerCustomsValue.value).toFixed(2);
        if (containerCustomsValue.value == 0) {
            containerCustomsValue.value = '';
        }
    }

    _getElementText(el) {
        if ('string' == typeof el.textContent) {
            return el.textContent;
        }
        if ('string' == typeof el.innerText) {
            return el.innerText;
        }
        var parser = new DOMParser();
        var doc = parser.parseFromString(el.innerHTML, 'text/html');
        return doc.body.textContent || "";
    }
//******************** End Private functions ******************************//
};
