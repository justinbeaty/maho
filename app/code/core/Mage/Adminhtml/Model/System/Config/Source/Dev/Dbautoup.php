<?php

/**
 * Maho
 *
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Adminhtml_Model_System_Config_Source_Dev_Dbautoup
{
    public function toOptionArray()
    {
        return [
            ['value' => Mage_Core_Model_Resource::AUTO_UPDATE_ALWAYS, 'label' => Mage::helper('adminhtml')->__('Always (during development)')],
            ['value' => Mage_Core_Model_Resource::AUTO_UPDATE_ONCE,   'label' => Mage::helper('adminhtml')->__('Only Once (version upgrade)')],
            ['value' => Mage_Core_Model_Resource::AUTO_UPDATE_NEVER,  'label' => Mage::helper('adminhtml')->__('Never (production)')],
        ];
    }
}
