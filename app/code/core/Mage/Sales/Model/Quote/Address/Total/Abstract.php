<?php

/**
 * Maho
 *
 * @package    Mage_Sales
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2023 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

abstract class Mage_Sales_Model_Quote_Address_Total_Abstract
{
    /**
     * Total Code name
     *
     * @var string
     */
    protected $_code;

    /**
     * @var Mage_Sales_Model_Quote_Address
     */
    protected $_address = null;

    /**
     * Various abstract abilities
     * @var bool
     */
    protected $_canAddAmountToAddress = true;
    protected $_canSetAddressAmount   = true;

    /**
     * Key for item row total getting
     *
     * @var string
     */
    protected $_itemRowTotalKey = null;

    /**
     * Set total code code name
     *
     * @param string $code
     * @return Mage_Sales_Model_Quote_Address_Total_Abstract
     */
    public function setCode($code)
    {
        $this->_code = $code;
        return $this;
    }

    /**
     * Retrieve total code name
     *
     * @return string
     */
    public function getCode()
    {
        return $this->_code;
    }

    /**
     * Label getter
     *
     * @return string
     */
    public function getLabel()
    {
        return '';
    }

    /**
     * Collect totals process.
     *
     * @return $this
     */
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        $this->_setAddress($address);
        /**
         * Reset amounts
         */
        $this->_setAmount(0);
        $this->_setBaseAmount(0);
        return $this;
    }

    /**
     * Fetch (Retrieve data as array)
     *
     * @return $this|array
     */
    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        $this->_setAddress($address);
        return [];
    }

    /**
     * Set address which can be used inside totals calculation
     *
     * @return  Mage_Sales_Model_Quote_Address_Total_Abstract
     */
    protected function _setAddress(Mage_Sales_Model_Quote_Address $address)
    {
        $this->_address = $address;
        return $this;
    }

    /**
     * Get quote address object
     *
     * @throw   Mage_Core_Exception if address not declared
     * @return  Mage_Sales_Model_Quote_Address
     */
    protected function _getAddress()
    {
        if ($this->_address === null) {
            Mage::throwException(
                Mage::helper('sales')->__('Address model is not defined.'),
            );
        }
        return $this->_address;
    }

    /**
     * Set total model amount value to address
     *
     * @param   float $amount
     * @return  Mage_Sales_Model_Quote_Address_Total_Abstract
     */
    protected function _setAmount($amount)
    {
        if ($this->_canSetAddressAmount) {
            $this->_getAddress()->setTotalAmount($this->getCode(), $amount);
        }
        return $this;
    }

    /**
     * Set total model base amount value to address
     *
     * @param   float $baseAmount
     * @return  Mage_Sales_Model_Quote_Address_Total_Abstract
     */
    protected function _setBaseAmount($baseAmount)
    {
        if ($this->_canSetAddressAmount) {
            $this->_getAddress()->setBaseTotalAmount($this->getCode(), $baseAmount);
        }
        return $this;
    }

    /**
     * Add total model amount value to address
     *
     * @param   float $amount
     * @return  Mage_Sales_Model_Quote_Address_Total_Abstract
     */
    protected function _addAmount($amount)
    {
        if ($this->_canAddAmountToAddress) {
            $this->_getAddress()->addTotalAmount($this->getCode(), $amount);
        }
        return $this;
    }

    /**
     * Add total model base amount value to address
     *
     * @param   float $baseAmount
     * @return  Mage_Sales_Model_Quote_Address_Total_Abstract
     */
    protected function _addBaseAmount($baseAmount)
    {
        if ($this->_canAddAmountToAddress) {
            $this->_getAddress()->addBaseTotalAmount($this->getCode(), $baseAmount);
        }
        return $this;
    }

    /**
     * Get all items except nominals
     *
     * @return Mage_Sales_Model_Quote_Address_Item[]
     */
    protected function _getAddressItems(Mage_Sales_Model_Quote_Address $address)
    {
        return $address->getAllNonNominalItems();
    }

    /**
     * Getter for row default total
     *
     * @return float
     */
    public function getItemRowTotal(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        if (!$this->_itemRowTotalKey) {
            return 0;
        }
        return $item->getDataUsingMethod($this->_itemRowTotalKey);
    }

    /**
     * Getter for row default base total
     *
     * @return float
     */
    public function getItemBaseRowTotal(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        if (!$this->_itemRowTotalKey) {
            return 0;
        }
        return $item->getDataUsingMethod('base_' . $this->_itemRowTotalKey);
    }

    /**
     * Whether the item row total may be compounded with others
     *
     * @return bool
     */
    public function getIsItemRowTotalCompoundable(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        if ($item->getData("skip_compound_{$this->_itemRowTotalKey}")) {
            return false;
        }
        return true;
    }

    /**
     * Process model configuration array.
     * This method can be used for changing models apply sort order
     *
     * @param   array $config
     * @param   Mage_Core_Model_Store $store
     * @return  array
     */
    public function processConfigArray($config, $store)
    {
        return $config;
    }
}
