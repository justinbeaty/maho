<?php

/**
 * Maho
 *
 * @package    Mage_ConfigurableSwatches
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2020-2024 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_ConfigurableSwatches_Helper_Productlist extends Mage_Core_Helper_Abstract
{
    protected $_moduleName = 'Mage_ConfigurableSwatches';

    /**
     * ID of attribute to be used for swatches on product listing
     *
     * @var string|false|null
     */
    protected $_swatchAttributeId = null;

    /**
     * Attribute model to be used for swatches on product listing
     *
     * @var Mage_Catalog_Model_Product_Type_Configurable_Attribute|false|null
     */
    protected $_swatchAttribute = null;

    /**
     * The current value for the swatch attribute that the product list is being filtered by.
     *
     * @var int|false|null
     */
    protected $_swatchAttributeFilteredValue = null;

    /**
     * Convert a catalog layer block with the right templates
     *
     * @param string $blockName
     */
    public function convertLayerBlock($blockName)
    {
        if (Mage::helper('configurableswatches')->isEnabled()
            && ($block = Mage::app()->getLayout()->getBlock($blockName))
            && $block instanceof Mage_Catalog_Block_Layer_View
        ) {
            // First, set a new template for the attribute that should show as a swatch
            if ($layer = $block->getLayer()) {
                foreach ($layer->getFilterableAttributes() as $attribute) {
                    if (Mage::helper('configurableswatches')->attrIsSwatchType($attribute)) {
                        $block->getChild($attribute->getAttributeCode() . '_filter')
                            ->setTemplate('configurableswatches/catalog/layer/filter/swatches.phtml');
                    }
                }
            }

            // Then set a specific renderer block for showing "currently shopping by" for the swatch attribute
            // (block class takes care of determining which attribute is applicable)
            if ($stateRenderersBlock = $block->getChild('state_renderers')) {
                $swatchRenderer = Mage::app()->getLayout()
                    ->addBlock('configurableswatches/catalog_layer_state_swatch', 'product_list.swatches');
                $swatchRenderer->setTemplate('configurableswatches/catalog/layer/state/swatch.phtml');
                $stateRenderersBlock->append($swatchRenderer);
            }
        }
    }

    /**
     * Get ID of the attribute that should be used for swatches on product listing
     *
     * @return string
     */
    public function getSwatchAttributeId()
    {
        if (is_null($this->_swatchAttributeId)) {
            $this->_swatchAttributeId =
                Mage::getStoreConfig(Mage_ConfigurableSwatches_Helper_Data::CONFIG_PATH_LIST_SWATCH_ATTRIBUTE);
        }
        return $this->_swatchAttributeId;
    }

    /**
     * Get model of attribute that should be used for swatches on product listing
     *
     * @return Mage_Catalog_Model_Product_Type_Configurable_Attribute|Mage_Eav_Model_Entity_Attribute_Abstract|false
     */
    public function getSwatchAttribute()
    {
        if (is_null($this->_swatchAttribute)) {
            $this->_swatchAttribute = Mage::getSingleton('eav/config')
                ->getAttribute('catalog_product', $this->getSwatchAttributeId());
        }
        return $this->_swatchAttribute;
    }

    /**
     * If the product list is currently filtered by the swatch attribute, get the selected value.
     *
     * @return int | false
     */
    protected function _getSwatchAttributeFilteredValue()
    {
        if (is_null($this->_swatchAttributeFilteredValue)) {
            $this->_swatchAttributeFilteredValue = false;
            foreach (Mage::getSingleton('catalog/layer')->getState()->getFilters() as $filter) {
                if (!$filter->getFilter()->hasAttributeModel()) {
                    continue;
                }

                if ($filter->getFilter()->getAttributeModel()->getAttributeId() == $this->getSwatchAttributeId()) {
                    $this->_swatchAttributeFilteredValue = $filter->getValue();
                }
            }
        }
        return $this->_swatchAttributeFilteredValue;
    }

    /**
     * See if the swatch matches a filter currently applied to the product list.
     *
     * @param int $optionId
     * @return bool
     */
    public function swatchMatchesFilter($optionId)
    {
        return ($optionId == $this->_getSwatchAttributeFilteredValue());
    }
}
