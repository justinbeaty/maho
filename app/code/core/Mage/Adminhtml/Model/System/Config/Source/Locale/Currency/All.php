<?php

/**
 * Maho
 *
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Adminhtml_Model_System_Config_Source_Locale_Currency_All
{
    protected $_options;

    public function toOptionArray($isMultiselect)
    {
        if (!$this->_options) {
            $this->_options = Mage::app()->getLocale()->getOptionAllCurrencies();
        }
        $options = $this->_options;
        if (!$isMultiselect) {
            array_unshift($options, ['value' => '', 'label' => '']);
        }

        return $options;
    }
}
