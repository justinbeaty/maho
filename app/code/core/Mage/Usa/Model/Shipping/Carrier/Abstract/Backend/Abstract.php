<?php

/**
 * Maho
 *
 * @package    Mage_Usa
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


abstract class Mage_Usa_Model_Shipping_Carrier_Abstract_Backend_Abstract extends Mage_Core_Model_Config_Data
{
    /**
     * Source model to get allowed values
     *
     * @var string
     */
    protected $_sourceModel;

    /**
     * Field name to display in error block
     *
     * @var string
     */
    protected $_nameErrorField;

    /**
     * Set source model to get allowed values
     */
    abstract protected function _setSourceModelData();

    /**
     * Set field name to display in error block
     */
    abstract protected function _setNameErrorField();

    /**
     * Mage_Usa_Model_Shipping_Carrier_Ups_Backend_Abstract constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->_setSourceModelData();
        $this->_setNameErrorField();
    }

    /**
     * Check for presence in array with allow value.
     *
     * @throws Mage_Core_Exception
     * @return $this
     */
    #[\Override]
    protected function _beforeSave()
    {
        $sourceModel = Mage::getSingleton($this->_sourceModel);
        if (!method_exists($sourceModel, 'toOptionArray')) {
            Mage::throwException(Mage::helper('usa')->__('Method toOptionArray not found in source model.'));
        }
        $value = $this->getValue();
        foreach ($sourceModel->toOptionArray() as $allowedValue) {
            if (isset($allowedValue['value']) && $allowedValue['value'] == $value) {
                return $this;
            }
        }

        Mage::throwException(Mage::helper('usa')->__('Field "%s" has wrong value.', $this->_nameErrorField));
    }
}
