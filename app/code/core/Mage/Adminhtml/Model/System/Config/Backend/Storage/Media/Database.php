<?php

/**
 * Maho
 *
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Adminhtml_Model_System_Config_Backend_Storage_Media_Database extends Mage_Core_Model_Config_Data
{
    /**
     * Create db structure
     *
     * @return $this
     */
    #[\Override]
    protected function _afterSave()
    {
        $helper = Mage::helper('core/file_storage');
        $helper->getStorageModel(null, ['init' => true]);

        return $this;
    }
}
