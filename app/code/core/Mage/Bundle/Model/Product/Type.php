<?php

/**
 * Maho
 *
 * @package    Mage_Bundle
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2025 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Bundle_Model_Product_Type extends Mage_Catalog_Model_Product_Type_Abstract
{
    /**
     * Product is composite
     *
     * @var bool
     */
    protected $_isComposite = true;

    /**
     * Cache key for Options Collection
     *
     * @var string
     */
    protected $_keyOptionsCollection        = '_cache_instance_options_collection';

    /**
     * Cache key for Selections Collection
     *
     * @var string
     */
    protected $_keySelectionsCollection     = '_cache_instance_selections_collection';

    /**
     * Cache key for used Selections
     *
     * @var string
     */
    protected $_keyUsedSelections           = '_cache_instance_used_selections';

    /**
     * Cache key for used selections ids
     *
     * @var string
     */
    protected $_keyUsedSelectionsIds        = '_cache_instance_used_selections_ids';

    /**
     * Cache key for used options
     *
     * @var string
     */
    protected $_keyUsedOptions              = '_cache_instance_used_options';

    /**
     * Cache key for used options ids
     *
     * @var string
     */
    protected $_keyUsedOptionsIds           = '_cache_instance_used_options_ids';

    /**
     * Product is configurable
     *
     * @var bool
     */
    protected $_canConfigure                = true;

    /**
     * Return relation info about used products
     *
     * @return Varien_Object Object with information data
     */
    #[\Override]
    public function getRelationInfo()
    {
        $info = new Varien_Object();
        $info->setTable('bundle/selection')
            ->setParentFieldName('parent_product_id')
            ->setChildFieldName('product_id');
        return $info;
    }

    /**
     * Retrieve Required children ids
     * Return grouped array, ex array(
     *   group => array(ids)
     * )
     *
     * @param int $parentId
     * @param bool $required
     * @return array
     */
    #[\Override]
    public function getChildrenIds($parentId, $required = true)
    {
        return Mage::getResourceSingleton('bundle/selection')
            ->getChildrenIds($parentId, $required);
    }

    /**
     * Retrieve parent ids array by requered child
     *
     * @param int|array $childId
     * @return array
     */
    #[\Override]
    public function getParentIdsByChild($childId)
    {
        return Mage::getResourceSingleton('bundle/selection')
            ->getParentIdsByChild($childId);
    }

    /**
     * Return product sku based on sku_type attribute
     *
     * @param Mage_Catalog_Model_Product $product
     * @return string
     */
    #[\Override]
    public function getSku($product = null)
    {
        $sku = parent::getSku($product);

        if ($this->getProduct($product)->getData('sku_type')) {
            return $sku;
        } else {
            $skuParts = [$sku];

            if ($this->getProduct($product)->hasCustomOptions()) {
                $customOption = $this->getProduct($product)->getCustomOption('bundle_selection_ids');
                $selectionIds = unserialize($customOption->getValue(), ['allowed_classes' => false]);
                if (!empty($selectionIds)) {
                    $selections = $this->getSelectionsByIds($selectionIds, $product);
                    foreach ($selections->getItems() as $selection) {
                        $skuParts[] = $selection->getSku();
                    }
                }
            }

            return implode('-', $skuParts);
        }
    }

    /**
     * Return product weight based on weight_type attribute
     *
     * @param Mage_Catalog_Model_Product $product
     * @return float
     */
    #[\Override]
    public function getWeight($product = null)
    {
        if ($this->getProduct($product)->getData('weight_type')) {
            return $this->getProduct($product)->getData('weight');
        } else {
            $weight = 0;

            if ($this->getProduct($product)->hasCustomOptions()) {
                $customOption = $this->getProduct($product)->getCustomOption('bundle_selection_ids');
                $selectionIds = unserialize($customOption->getValue(), ['allowed_classes' => false]);
                $selections = $this->getSelectionsByIds($selectionIds, $product);
                foreach ($selections->getItems() as $selection) {
                    $qtyOption = $this->getProduct($product)
                        ->getCustomOption('selection_qty_' . $selection->getSelectionId());
                    if ($qtyOption) {
                        $weight += $selection->getWeight() * $qtyOption->getValue();
                    } else {
                        $weight += $selection->getWeight();
                    }
                }
            }
            return $weight;
        }
    }

    /**
     * Check is virtual product
     *
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    #[\Override]
    public function isVirtual($product = null)
    {
        if ($this->getProduct($product)->hasCustomOptions()) {
            $customOption = $this->getProduct($product)->getCustomOption('bundle_selection_ids');
            $selectionIds = unserialize($customOption->getValue(), ['allowed_classes' => false]);
            $selections = $this->getSelectionsByIds($selectionIds, $product);
            $virtualCount = 0;
            foreach ($selections->getItems() as $selection) {
                if ($selection->isVirtual()) {
                    $virtualCount++;
                }
            }
            if ($virtualCount == count($selections)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Before save type related data
     *
     * @param Mage_Catalog_Model_Product $product
     * @return $this
     */
    #[\Override]
    public function beforeSave($product = null)
    {
        parent::beforeSave($product);
        $product = $this->getProduct($product);

        // If bundle product has dynamic weight, than delete weight attribute
        if (!$product->getData('weight_type') && $product->hasData('weight')) {
            $product->setData('weight', false);
        }

        if ($product->getPriceType() == Mage_Bundle_Model_Product_Price::PRICE_TYPE_DYNAMIC) {
            $product->setData(
                'msrp_enabled',
                Mage_Catalog_Model_Product_Attribute_Source_Msrp_Type_Enabled::MSRP_ENABLE_NO,
            );
            $product->unsetData('msrp');
            $product->unsetData('msrp_display_actual_price_type');
        }

        $product->canAffectOptions(false);

        if ($product->getCanSaveBundleSelections()) {
            $product->canAffectOptions(true);
            $selections = $product->getBundleSelectionsData();
            if ($selections) {
                $options = $product->getBundleOptionsData();
                if ($options) {
                    foreach ($options as $option) {
                        if (empty($option['delete']) || (int) $option['delete'] != 1) {
                            $product->setTypeHasOptions(true);
                            if ((int) $option['required'] == 1) {
                                $product->setTypeHasRequiredOptions(true);
                                break;
                            }
                        }
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Save type related data
     *
     * @param Mage_Catalog_Model_Product $product
     * @return $this
     */
    #[\Override]
    public function save($product = null)
    {
        parent::save($product);

        $product = $this->getProduct($product);
        /** @var Mage_Bundle_Model_Resource_Bundle $resource */
        $resource = Mage::getResourceModel('bundle/bundle');

        $options = $product->getBundleOptionsData();
        if ($options) {
            $product->setIsRelationsChanged(true);

            foreach ($options as $key => $option) {
                if (isset($option['option_id']) && $option['option_id'] == '') {
                    unset($option['option_id']);
                }

                $optionModel = Mage::getModel('bundle/option')
                    ->setData($option)
                    ->setParentId($product->getId())
                    ->setStoreId($product->getStoreId());

                $optionModel->isDeleted((bool) $option['delete']);
                $optionModel->save();

                $options[$key]['option_id'] = $optionModel->getOptionId();
            }

            $usedProductIds      = [];
            $excludeSelectionIds = [];

            $selections = $product->getBundleSelectionsData();
            if ($selections) {
                foreach ($selections as $index => $group) {
                    foreach ($group as $key => $selection) {
                        if (isset($selection['selection_id']) && $selection['selection_id'] == '') {
                            unset($selection['selection_id']);
                        }

                        if (!isset($selection['is_default'])) {
                            $selection['is_default'] = 0;
                        }

                        $selectionModel = Mage::getModel('bundle/selection')
                            ->setData($selection)
                            ->setOptionId($options[$index]['option_id'])
                            ->setParentProductId($product->getId());

                        $selectionModel->isDeleted((bool) $selection['delete']);
                        $selectionModel->save();

                        $selection['selection_id'] = $selectionModel->getSelectionId();

                        if ($selectionModel->getSelectionId() && !$selectionModel->isDeleted()) {
                            $excludeSelectionIds[] = $selectionModel->getSelectionId();
                            $usedProductIds[] = $selectionModel->getProductId();
                        }
                    }
                }

                $resource->dropAllUnneededSelections($product->getId(), $excludeSelectionIds);
                $resource->saveProductRelations($product->getId(), array_unique($usedProductIds));
            }

            if ($product->getData('price_type') != $product->getOrigData('price_type')) {
                $resource->dropAllQuoteChildItems($product->getId());
            }
        }

        return $this;
    }

    /**
     * Retrieve bundle options items
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getOptions($product = null)
    {
        return $this->getOptionsCollection($product)->getItems();
    }

    /**
     * Retrieve bundle options ids
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getOptionsIds($product = null)
    {
        return $this->getOptionsCollection($product)->getAllIds();
    }

    /**
     * Retrieve bundle option collection
     *
     * @param Mage_Catalog_Model_Product $product
     * @return Mage_Bundle_Model_Resource_Option_Collection
     */
    public function getOptionsCollection($product = null)
    {
        if (!$this->getProduct($product)->hasData($this->_keyOptionsCollection)) {
            $optionsCollection = Mage::getModel('bundle/option')->getResourceCollection()
                ->setProductIdFilter($this->getProduct($product)->getId())
                ->setPositionOrder();

            $storeId = $this->getStoreFilter($product);
            if ($storeId instanceof Mage_Core_Model_Store) {
                $storeId = $storeId->getId();
            }

            $optionsCollection->joinValues($storeId);
            $this->getProduct($product)->setData($this->_keyOptionsCollection, $optionsCollection);
        }
        return $this->getProduct($product)->getData($this->_keyOptionsCollection);
    }

    /**
     * Retrieve bundle selections collection based on used options
     *
     * @param array $optionIds
     * @param Mage_Catalog_Model_Product $product
     * @return Mage_Bundle_Model_Resource_Selection_Collection
     */
    public function getSelectionsCollection($optionIds, $product = null)
    {
        $keyOptionIds = (is_array($optionIds) ? implode('_', $optionIds) : '');
        $key = $this->_keySelectionsCollection . $keyOptionIds;
        if (!$this->getProduct($product)->hasData($key)) {
            $storeId = $this->getProduct($product)->getStoreId();
            $selectionsCollection = Mage::getResourceModel('bundle/selection_collection')
                ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
                ->addAttributeToSelect('tax_class_id') //used for calculation item taxes in Bundle with Dynamic Price
                ->setFlag('require_stock_items', true)
                ->setFlag('product_children', true)
                ->setPositionOrder()
                ->addStoreFilter($this->getStoreFilter($product))
                ->setStoreId($storeId)
                ->addFilterByRequiredOptions()
                ->setOptionIdsFilter($optionIds);

            if (!Mage::helper('catalog')->isPriceGlobal() && $storeId) {
                $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();
                $selectionsCollection->joinPrices($websiteId);
            }

            $this->getProduct($product)->setData($key, $selectionsCollection);
        }
        return $this->getProduct($product)->getData($key);
    }

    /**
     * Method is needed for specific actions to change given quote options values
     * according current product type logic
     * Example: the cataloginventory validation of decimal qty can change qty to int,
     * so need to change quote item qty option value too.
     *
     * @param   array           $options
     * @param   mixed           $value
     * @param   Mage_Catalog_Model_Product $product
     * @return  Mage_Bundle_Model_Product_Type
     */
    #[\Override]
    public function updateQtyOption($options, Varien_Object $option, $value, $product = null)
    {
        $optionProduct      = $option->getProduct($product);
        $optionUpdateFlag   = $option->getHasQtyOptionUpdate();
        $optionCollection   = $this->getOptionsCollection($product);

        $selections = $this->getSelectionsCollection($optionCollection->getAllIds(), $product);

        /** @var Mage_Bundle_Model_Selection $selection */
        foreach ($selections as $selection) {
            if ($selection->getProductId() == $optionProduct->getId()) {
                foreach ($options as &$option) {
                    if ($option->getCode() == 'selection_qty_' . $selection->getSelectionId()) {
                        if ($optionUpdateFlag) {
                            $option->setValue((int) $option->getValue());
                        } else {
                            $option->setValue($value);
                        }
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Prepare Quote Item Quantity
     *
     * @param mixed $qty
     * @param Mage_Catalog_Model_Product $product
     * @return int
     */
    #[\Override]
    public function prepareQuoteItemQty($qty, $product = null)
    {
        return (int) $qty;
    }

    /**
     * Checking if we can sale this bundle
     *
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    #[\Override]
    public function isSalable($product = null)
    {
        $salable = parent::isSalable($product);
        if (!is_null($salable)) {
            return $salable;
        }

        $optionCollection = $this->getOptionsCollection($product);

        if (!count($optionCollection->getItems())) {
            return false;
        }

        $requiredOptionIds = [];

        foreach ($optionCollection->getItems() as $option) {
            if ($option->getRequired()) {
                $requiredOptionIds[$option->getId()] = 0;
            }
        }

        $selectionCollection = $this->getSelectionsCollection($optionCollection->getAllIds(), $product);

        if (!count($selectionCollection->getItems())) {
            return false;
        }
        $salableSelectionCount = 0;
        /** @var Mage_Bundle_Model_Selection $selection */
        foreach ($selectionCollection as $selection) {
            if ($selection->isSalable()) {
                $requiredOptionIds[$selection->getOptionId()] = 1;
                $salableSelectionCount++;
            }
        }

        return (array_sum($requiredOptionIds) == count($requiredOptionIds) && $salableSelectionCount);
    }

    /**
     * Prepare product and its configuration to be added to some products list.
     * Perform standard preparation process and then prepare of bundle selections options.
     *
     * @param Mage_Catalog_Model_Product $product
     * @param string $processMode
     * @return array|string
     */
    #[\Override]
    protected function _prepareProduct(Varien_Object $buyRequest, $product, $processMode)
    {
        $result = parent::_prepareProduct($buyRequest, $product, $processMode);

        if (is_string($result)) {
            return $result;
        }

        $selections = [];
        $product = $this->getProduct($product);
        $isStrictProcessMode = $this->_isStrictProcessMode($processMode);

        $skipSaleableCheck = Mage::helper('catalog/product')->getSkipSaleableCheck();
        $_appendAllSelections = (bool) $product->getSkipCheckRequiredOption() || $skipSaleableCheck;

        $options = $buyRequest->getBundleOption();
        if (is_array($options)) {
            $options = array_filter($options, '\intval');
            $qtys = $buyRequest->getBundleOptionQty();
            foreach ($options as $_optionId => $_selections) {
                if (empty($_selections)) {
                    unset($options[$_optionId]);
                }
            }
            $optionIds = array_keys($options);

            if (empty($optionIds) && $isStrictProcessMode) {
                return Mage::helper('bundle')->__('Please select options for product.');
            }

            $product->getTypeInstance(true)->setStoreFilter($product->getStoreId(), $product);
            $optionsCollection = $this->getOptionsCollection($product);
            if (!$this->getProduct($product)->getSkipCheckRequiredOption() && $isStrictProcessMode) {
                foreach ($optionsCollection->getItems() as $option) {
                    if ($option->getRequired() && !isset($options[$option->getId()])) {
                        return Mage::helper('bundle')->__('Required options are not selected.');
                    }
                }
            }
            $selectionIds = [];

            foreach ($options as $optionId => $selectionId) {
                if (!is_array($selectionId)) {
                    if ($selectionId != '') {
                        $selectionIds[] = (int) $selectionId;
                    }
                } else {
                    foreach ($selectionId as $id) {
                        if ($id != '') {
                            $selectionIds[] = (int) $id;
                        }
                    }
                }
            }
            // If product has not been configured yet then $selections array should be empty
            if (!empty($selectionIds)) {
                $selections = $this->getSelectionsByIds($selectionIds, $product);

                // Check if added selections are still on sale
                foreach ($selections->getItems() as $selection) {
                    if (!$selection->isSalable() && !$skipSaleableCheck) {
                        $selectedOption = $optionsCollection->getItemById($selection->getOptionId());
                        if (is_array($options[$selectedOption->getId()]) && count($options[$selectedOption->getId()]) > 1) {
                            $moreSelections = true;
                        } else {
                            $moreSelections = false;
                        }
                        if ($selectedOption->getRequired()
                            && (!$selectedOption->isMultiSelection() || ($selectedOption->isMultiSelection() && !$moreSelections))
                        ) {
                            return Mage::helper('bundle')->__('Selected required options are not available.');
                        }
                    }
                }

                $optionsCollection->appendSelections($selections, false, $_appendAllSelections);

                $selections = $selections->getItems();
            } else {
                $selections = [];
            }
        } else {
            $product->setOptionsValidationFail(true);
            $product->getTypeInstance(true)->setStoreFilter($product->getStoreId(), $product);

            /** @var Mage_Bundle_Model_Product_Type $productType */
            $productType = $product->getTypeInstance(true);

            $optionCollection = $productType->getOptionsCollection($product);

            $optionIds = $productType->getOptionsIds($product);

            $selectionCollection = $productType->getSelectionsCollection(
                $optionIds,
                $product,
            );

            $options = $optionCollection->appendSelections($selectionCollection, false, $_appendAllSelections);

            foreach ($options as $option) {
                if ($option->getRequired() && count($option->getSelections()) == 1) {
                    $selections = array_merge($selections, $option->getSelections());
                } else {
                    $selections = [];
                    break;
                }
            }
        }
        if (count($selections) > 0 || !$isStrictProcessMode) {
            $uniqueKey = [$product->getId()];
            $selectionIds = [];

            // Shuffle selection array by option position
            usort($selections, [$this, 'shakeSelections']);

            foreach ($selections as $selection) {
                if ($selection->getSelectionCanChangeQty() && isset($qtys[$selection->getOptionId()])) {
                    $qty = (float) $qtys[$selection->getOptionId()] > 0 ? $qtys[$selection->getOptionId()] : 1;
                } else {
                    $qty = (float) $selection->getSelectionQty() ? $selection->getSelectionQty() : 1;
                }
                $qty = (float) $qty;

                $product->addCustomOption('selection_qty_' . $selection->getSelectionId(), $qty, $selection);
                $selection->addCustomOption('selection_id', $selection->getSelectionId());
                $product->addCustomOption('product_qty_' . $selection->getId(), $qty, $selection);

                /*
                 * Create extra attributes that will be converted to product options in order item
                 * for selection (not for all bundle)
                 */
                $price = $product->getPriceModel()->getSelectionFinalTotalPrice($product, $selection, 0, $qty);
                $attributes = [
                    'price'         => Mage::app()->getStore()->convertPrice($price),
                    'qty'           => $qty,
                    'option_label'  => $selection->getOption()->getTitle(),
                    'option_id'     => $selection->getOption()->getId(),
                ];

                $_result = $selection->getTypeInstance(true)->prepareForCart($buyRequest, $selection);
                if (is_string($_result) && !is_array($_result)) {
                    return $_result;
                }

                if (!isset($_result[0])) {
                    return Mage::helper('checkout')->__('Cannot add item to the shopping cart.');
                }

                $result[] = $_result[0]->setParentProductId($product->getId())
                    ->addCustomOption('bundle_option_ids', serialize(array_map('\intval', $optionIds)))
                    ->addCustomOption('bundle_selection_attributes', serialize($attributes));

                if ($isStrictProcessMode) {
                    $_result[0]->setCartQty($qty);
                }

                $selectionIds[] = $_result[0]->getSelectionId();
                $uniqueKey[] = $_result[0]->getSelectionId();
                $uniqueKey[] = $qty;
            }

            // "unique" key for bundle selection and add it to selections and bundle for selections
            $uniqueKey = implode('_', $uniqueKey);
            foreach ($result as $item) {
                $item->addCustomOption('bundle_identity', $uniqueKey);
            }
            $product->addCustomOption('bundle_option_ids', serialize(array_map('\intval', $optionIds)));
            $product->addCustomOption('bundle_selection_ids', serialize($selectionIds));

            return $result;
        }

        return $this->getSpecifyOptionMessage();
    }

    /**
     * Retrieve message for specify option(s)
     *
     * @return string
     */
    #[\Override]
    public function getSpecifyOptionMessage()
    {
        return Mage::helper('bundle')->__('Please specify product option(s).');
    }

    /**
     * Retrieve bundle selections collection based on ids
     *
     * @param array $selectionIds
     * @param Mage_Catalog_Model_Product $product
     * @return Mage_Bundle_Model_Resource_Selection_Collection
     */
    public function getSelectionsByIds($selectionIds, $product = null)
    {
        sort($selectionIds);

        $usedSelections     = $this->getProduct($product)->getData($this->_keyUsedSelections);
        $usedSelectionsIds  = $this->getProduct($product)->getData($this->_keyUsedSelectionsIds);

        if (!$usedSelections || serialize($usedSelectionsIds) != serialize($selectionIds)) {
            $storeId = $this->getProduct($product)->getStoreId();
            $usedSelections = Mage::getResourceModel('bundle/selection_collection')
                ->addAttributeToSelect('*')
                ->setFlag('require_stock_items', true)
                ->setFlag('product_children', true)
                ->addStoreFilter($this->getStoreFilter($product))
                ->setStoreId($storeId)
                ->setPositionOrder()
                ->addFilterByRequiredOptions()
                ->setSelectionIdsFilter($selectionIds);

            if (!Mage::helper('catalog')->isPriceGlobal() && $storeId) {
                $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();
                $usedSelections->joinPrices($websiteId);
            }
            $this->getProduct($product)->setData($this->_keyUsedSelections, $usedSelections);
            $this->getProduct($product)->setData($this->_keyUsedSelectionsIds, $selectionIds);
        }
        return $usedSelections;
    }

    /**
     * Retrieve bundle options collection based on ids
     *
     * @param array $optionIds
     * @param Mage_Catalog_Model_Product $product
     * @return Mage_Bundle_Model_Resource_Option_Collection
     */
    public function getOptionsByIds($optionIds, $product = null)
    {
        sort($optionIds);

        $usedOptions     = $this->getProduct($product)->getData($this->_keyUsedOptions);
        $usedOptionsIds  = $this->getProduct($product)->getData($this->_keyUsedOptionsIds);

        if (!$usedOptions || serialize($usedOptionsIds) != serialize($optionIds)) {
            $usedOptions = Mage::getModel('bundle/option')->getResourceCollection()
                ->setProductIdFilter($this->getProduct($product)->getId())
                ->setPositionOrder()
                ->joinValues(Mage::app()->getStore()->getId())
                ->setIdFilter($optionIds);
            $this->getProduct($product)->setData($this->_keyUsedOptions, $usedOptions);
            $this->getProduct($product)->setData($this->_keyUsedOptionsIds, $optionIds);
        }
        return $usedOptions;
    }

    /**
     * Prepare additional options/information for order item which will be
     * created from this product
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    #[\Override]
    public function getOrderOptions($product = null)
    {
        $optionArr = parent::getOrderOptions($product);

        $bundleOptions = [];

        $product = $this->getProduct($product);

        if ($product->hasCustomOptions()) {
            $customOption = $product->getCustomOption('bundle_option_ids');
            $optionIds = unserialize($customOption->getValue(), ['allowed_classes' => false]);
            $options = $this->getOptionsByIds($optionIds, $product);
            $customOption = $product->getCustomOption('bundle_selection_ids');
            $selectionIds = unserialize($customOption->getValue(), ['allowed_classes' => false]);
            $selections = $this->getSelectionsByIds($selectionIds, $product);
            foreach ($selections->getItems() as $selection) {
                if ($selection->isSalable()) {
                    $selectionQty = $product->getCustomOption('selection_qty_' . $selection->getSelectionId());
                    if ($selectionQty) {
                        $price = $product->getPriceModel()->getSelectionFinalTotalPrice(
                            $product,
                            $selection,
                            0,
                            $selectionQty->getValue(),
                        );

                        $option = $options->getItemById($selection->getOptionId());
                        if (!isset($bundleOptions[$option->getId()])) {
                            $bundleOptions[$option->getId()] = [
                                'option_id' => $option->getId(),
                                'label' => $option->getTitle(),
                                'value' => [],
                            ];
                        }

                        $bundleOptions[$option->getId()]['value'][] = [
                            'title' => $selection->getName(),
                            'qty'   => $selectionQty->getValue(),
                            'price' => Mage::app()->getStore()->convertPrice($price),
                        ];
                    }
                }
            }
        }

        $optionArr['bundle_options'] = $bundleOptions;

        /**
         * Product Prices calculations save
         */
        if ($product->getPriceType()) {
            $optionArr['product_calculations'] = self::CALCULATE_PARENT;
        } else {
            $optionArr['product_calculations'] = self::CALCULATE_CHILD;
        }

        $optionArr['shipment_type'] = $product->getShipmentType();

        return $optionArr;
    }

    /**
     * Sort selections method for usort function
     * Sort selections by option position, selection position and selection id
     *
     * @param  Mage_Catalog_Model_Product $a
     * @param  Mage_Catalog_Model_Product $b
     * @return int
     */
    public function shakeSelections($a, $b)
    {
        $aPosition = [
            $a->getOption()->getPosition(),
            $a->getOptionId(),
            $a->getPosition(),
            $a->getSelectionId(),
        ];
        $bPosition = [
            $b->getOption()->getPosition(),
            $b->getOptionId(),
            $b->getPosition(),
            $b->getSelectionId(),
        ];
        return $aPosition <=> $bPosition;
    }

    /**
     * Return true if product has options
     *
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    #[\Override]
    public function hasOptions($product = null)
    {
        $product    = $this->getProduct($product);
        $this->setStoreFilter($product->getStoreId(), $product);
        $optionIds  = $this->getOptionsCollection($product)->getAllIds();
        $collection = $this->getSelectionsCollection($optionIds, $product);

        if (count($collection) > 0 || $product->getOptions()) {
            return true;
        }

        return false;
    }

    /**
     * Allow for updates of chidren qty's
     *
     * @param Mage_Catalog_Model_Product $product
     * @return true
     */
    #[\Override]
    public function getForceChildItemQtyChanges($product = null)
    {
        return true;
    }

    /**
     * Retrieve additional searchable data from type instance
     * Using based on product id and store_id data
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    #[\Override]
    public function getSearchableData($product = null)
    {
        $searchData = parent::getSearchableData($product);
        $product = $this->getProduct($product);

        $optionSearchData = Mage::getSingleton('bundle/option')
            ->getSearchableData($product->getId(), $product->getStoreId());
        if ($optionSearchData) {
            $searchData = array_merge($searchData, $optionSearchData);
        }

        return $searchData;
    }

    /**
     * Check if product can be bought
     *
     * @param Mage_Catalog_Model_Product $product
     * @return $this
     * @throws Mage_Core_Exception
     */
    #[\Override]
    public function checkProductBuyState($product = null)
    {
        parent::checkProductBuyState($product);
        $product            = $this->getProduct($product);
        $productOptionIds   = $this->getOptionsIds($product);
        $productSelections  = $this->getSelectionsCollection($productOptionIds, $product);
        $selectionIds       = $product->getCustomOption('bundle_selection_ids');
        $selectionIds       = (array) unserialize($selectionIds->getValue(), ['allowed_classes' => false]);
        $buyRequest         = $product->getCustomOption('info_buyRequest');
        $buyRequest         = new Varien_Object(unserialize($buyRequest->getValue(), ['allowed_classes' => false]));
        $bundleOption       = $buyRequest->getBundleOption();

        if (empty($bundleOption) && empty($selectionIds)) {
            Mage::throwException($this->getSpecifyOptionMessage());
        }

        $skipSaleableCheck = Mage::helper('catalog/product')->getSkipSaleableCheck();
        foreach ($selectionIds as $selectionId) {
            /** @var Mage_Bundle_Model_Selection $selection */
            $selection = $productSelections->getItemById($selectionId);
            if (!$selection || (!$selection->isSalable() && !$skipSaleableCheck)) {
                Mage::throwException(
                    Mage::helper('bundle')->__('Selected required options are not available.'),
                );
            }
        }

        $product->getTypeInstance(true)->setStoreFilter($product->getStoreId(), $product);
        $optionsCollection = $this->getOptionsCollection($product);
        foreach ($optionsCollection->getItems() as $option) {
            if ($option->getRequired() && empty($selectionIds) && empty($bundleOption[$option->getId()])) {
                Mage::throwException(
                    Mage::helper('bundle')->__('Required options are not selected.'),
                );
            }
        }

        return $this;
    }

    /**
     * Retrieve products divided into groups required to purchase
     * At least one product in each group has to be purchased
     *
     * @param  Mage_Catalog_Model_Product $product
     * @return array
     */
    #[\Override]
    public function getProductsToPurchaseByReqGroups($product = null)
    {
        $product = $this->getProduct($product);
        $groups = [];
        $allProducts = [];
        $hasRequiredOptions = false;
        foreach ($this->getOptions($product) as $option) {
            $groupProducts = [];
            foreach ($this->getSelectionsCollection([$option->getId()], $product) as $childProduct) {
                $groupProducts[] = $childProduct;
                $allProducts[] = $childProduct;
            }
            if ($option->getRequired()) {
                $groups[] = $groupProducts;
                $hasRequiredOptions = true;
            }
        }
        if (!$hasRequiredOptions) {
            $groups = [$allProducts];
        }
        return $groups;
    }

    /**
     * Prepare selected options for bundle product
     *
     * @param  Mage_Catalog_Model_Product $product
     * @param  Varien_Object $buyRequest
     * @return array
     */
    #[\Override]
    public function processBuyRequest($product, $buyRequest)
    {
        $option     = $buyRequest->getBundleOption();
        $optionQty  = $buyRequest->getBundleOptionQty();

        $option     = (is_array($option)) ? array_filter($option, '\intval') : [];
        $optionQty  = (is_array($optionQty)) ? array_filter($optionQty, '\intval') : [];

        return [
            'bundle_option'     => $option,
            'bundle_option_qty' => $optionQty,
        ];
    }

    /**
     * Check if product can be configured
     *
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    #[\Override]
    public function canConfigure($product = null)
    {
        return $product instanceof Mage_Catalog_Model_Product
            && $product->isAvailable()
            && parent::canConfigure();
    }

    /**
     * Check if Minimum Advertise Price is enabled at least in one option
     *
     * @param Mage_Catalog_Model_Product $product
     * @param int $visibility
     * @return bool|null
     */
    #[\Override]
    public function isMapEnabledInOptions($product, $visibility = null)
    {
        /**
         * @TODO: In order to clarify is MAP enabled for product we can check associated products.
         * Commented for future improvements.
         */
        /*
        $collection = $this->getUsedProductCollection($product);
        $helper = Mage::helper('catalog');

        $result = null;
        $parentVisibility = $product->getMsrpDisplayActualPriceType();
        if ($parentVisibility === null) {
            $parentVisibility = $helper->getMsrpDisplayActualPriceType();
        }
        $visibilities = array($parentVisibility);
        foreach ($collection as $item) {
            if ($helper->canApplyMsrp($item)) {
                $productVisibility = $item->getMsrpDisplayActualPriceType();
                if ($productVisibility === null) {
                    $productVisibility = $helper->getMsrpDisplayActualPriceType();
                }
                $visibilities[] = $productVisibility;
                $result = true;
            }
        }

        if ($result && $visibility !== null) {
            if ($visibilities) {
                $maxVisibility = max($visibilities);
                $result = $result && $maxVisibility == $visibility;
            } else {
                $result = false;
            }
        }

        return $result;
        */

        return null;
    }
}
