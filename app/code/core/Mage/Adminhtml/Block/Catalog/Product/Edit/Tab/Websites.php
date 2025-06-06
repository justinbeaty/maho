<?php

/**
 * Maho
 *
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Websites extends Mage_Adminhtml_Block_Store_Switcher
{
    protected $_storeFromHtml;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('catalog/product/edit/websites.phtml');
    }

    /**
     * Retrieve edited product model instance
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        return Mage::registry('product');
    }

    /**
     * Get store ID of current product
     *
     * @return int
     */
    #[\Override]
    public function getStoreId()
    {
        return $this->getProduct()->getStoreId();
    }

    /**
     * Get ID of current product
     *
     * @return int
     */
    public function getProductId()
    {
        return $this->getProduct()->getId();
    }

    /**
     * Retrieve array of website IDs of current product
     *
     * @return array
     */
    #[\Override]
    public function getWebsites()
    {
        return $this->getProduct()->getWebsiteIds();
    }

    /**
     * Returns whether product associated with website with $websiteId
     *
     * @param int $websiteId
     * @return bool
     */
    public function hasWebsite($websiteId)
    {
        return in_array($websiteId, $this->getProduct()->getWebsiteIds());
    }

    /**
     * Check websites block is readonly
     *
     * @return bool
     */
    public function isReadonly()
    {
        return $this->getProduct()->getWebsitesReadonly();
    }

    /**
     * Retrieve store name by its ID
     *
     * @param int $storeId
     * @return null|string
     */
    public function getStoreName($storeId)
    {
        return Mage::app()->getStore($storeId)->getName();
    }

    /**
     * Get HTML of store chooser
     *
     * @param Mage_Core_Model_Store $storeTo
     * @return string
     */
    public function getChooseFromStoreHtml($storeTo)
    {
        if (!$this->_storeFromHtml) {
            $this->_storeFromHtml = '<select name="copy_to_stores[__store_identifier__]" disabled="disabled">';
            $this->_storeFromHtml .= '<option value="0">' . Mage::helper('catalog')->__('Default Values') . '</option>';
            foreach ($this->getWebsiteCollection() as $_website) {
                if (!$this->hasWebsite($_website->getId())) {
                    continue;
                }
                $optGroupLabel = $this->escapeHtml($_website->getName());
                $this->_storeFromHtml .= '<optgroup label="' . $optGroupLabel . '"></optgroup>';
                foreach ($this->getGroupCollection($_website) as $_group) {
                    $optGroupName = $this->escapeHtml($_group->getName());
                    $this->_storeFromHtml .= '<optgroup label="&nbsp;&nbsp;&nbsp;&nbsp;' . $optGroupName . '">';
                    foreach ($this->getStoreCollection($_group) as $_store) {
                        $this->_storeFromHtml .= '<option value="' . $_store->getId() . '">&nbsp;&nbsp;&nbsp;&nbsp;';
                        $this->_storeFromHtml .= $this->escapeHtml($_store->getName()) . '</option>';
                    }
                }
                $this->_storeFromHtml .= '</optgroup>';
            }
            $this->_storeFromHtml .= '</select>';
        }
        return str_replace('__store_identifier__', (string) $storeTo->getId(), $this->_storeFromHtml);
    }
}
