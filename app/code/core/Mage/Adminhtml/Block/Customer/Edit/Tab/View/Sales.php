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

class Mage_Adminhtml_Block_Customer_Edit_Tab_View_Sales extends Mage_Adminhtml_Block_Template
{
    /**
     * Sales entity collection
     *
     * @var Mage_Sales_Model_Entity_Sale_Collection
     */
    protected $_collection;

    protected $_groupedCollection;
    protected $_websiteCounts;

    /**
     * Currency model
     *
     * @var Mage_Directory_Model_Currency
     */
    protected $_currency;

    public function __construct()
    {
        parent::__construct();
        $this->setId('customer_view_sales_grid');
    }

    #[\Override]
    public function _beforeToHtml()
    {
        $this->_currency = Mage::getModel('directory/currency')
            ->load(Mage_Directory_Helper_Data::getConfigCurrencyBase());

        $this->_collection = Mage::getResourceModel('sales/sale_collection')
            ->setCustomerFilter(Mage::registry('current_customer'))
            ->setOrderStateFilter(Mage_Sales_Model_Order::STATE_CANCELED, true)
            ->load();

        $this->_groupedCollection = [];

        foreach ($this->_collection as $sale) {
            if (!is_null($sale->getStoreId())) {
                $store      = Mage::app()->getStore($sale->getStoreId());
                $websiteId  = $store->getWebsiteId();
                $groupId    = $store->getGroupId();
                $storeId    = $store->getId();

                $sale->setWebsiteId($store->getWebsiteId());
                $sale->setWebsiteName($store->getWebsite()->getName());
                $sale->setGroupId($store->getGroupId());
                $sale->setGroupName($store->getGroup()->getName());
            } else {
                $websiteId  = 0;
                $groupId    = 0;
                $storeId    = 0;

                $sale->setStoreName(Mage::helper('customer')->__('Deleted Stores'));
            }

            $this->_groupedCollection[$websiteId][$groupId][$storeId] = $sale;
            $this->_websiteCounts[$websiteId] = isset($this->_websiteCounts[$websiteId]) ? $this->_websiteCounts[$websiteId] + 1 : 1;
        }

        return parent::_beforeToHtml();
    }

    public function getWebsiteCount($websiteId)
    {
        return $this->_websiteCounts[$websiteId] ?? 0;
    }

    public function getRows()
    {
        return $this->_groupedCollection;
    }

    public function getTotals()
    {
        return $this->_collection->getTotals();
    }

    /**
     * @deprecated after 1.4.0.0-rc1
     *
     * @param float $price
     * @return string
     */
    public function getPriceFormatted($price)
    {
        return $this->_currency->format($price);
    }

    /**
     * Format price by specified website
     *
     * @param float $price
     * @param null|int $websiteId
     * @return string
     */
    public function formatCurrency($price, $websiteId = null)
    {
        return Mage::app()->getWebsite($websiteId)->getBaseCurrency()->format($price);
    }
}
