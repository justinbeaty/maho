<?php

/**
 * Maho
 *
 * @category   Mage
 * @package    Mage_Core
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2020-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Model for working with system.xml module files
 *
 * @category   Mage
 * @package    Mage_Core
 */
class Mage_Core_Model_Config_System extends Mage_Core_Model_Config_Base
{
    /**
     * @param string $module
     * @return $this
     */
    public function load($module)
    {
        $file = Mage::getConfig()->getModuleDir('etc', $module) . DS . 'system.xml';
        $file = mahoFindFileInIncludePath($file);
        $this->loadFile($file);
        return $this;
    }
}
