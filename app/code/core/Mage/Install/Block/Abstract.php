<?php

/**
 * Maho
 *
 * @package    Mage_Install
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2021-2024 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

abstract class Mage_Install_Block_Abstract extends Mage_Core_Block_Template
{
    /**
     * Retrieve installer model
     *
     * @return Mage_Install_Model_Installer
     */
    public function getInstaller()
    {
        return Mage::getSingleton('install/installer');
    }

    /**
     * Retrieve wizard model
     *
     * @return Mage_Install_Model_Wizard
     */
    public function getWizard()
    {
        return Mage::getSingleton('install/wizard');
    }

    /**
     * Retrieve current installation step
     *
     * @return Varien_Object
     */
    public function getCurrentStep()
    {
        return $this->getWizard()->getStepByRequest($this->getRequest());
    }
}
