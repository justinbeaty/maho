<?php
/**
 * Maho
 *
 * @category   Mage
 * @package    Mage_Uploader
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Uploader Config Instance Abstract Model
 *
 * @category   Mage
 * @package    Mage_Uploader
 */
abstract class Mage_Uploader_Model_Config_Abstract extends Varien_Object
{
    /**
     * Get file helper
     *
     * @return Mage_Uploader_Helper_File
     */
    protected function _getHelper()
    {
        return Mage::helper('uploader/file');
    }

    /**
     * Set/Get attribute wrapper
     * Also set data in cameCase for config values
     *
     * @param string $method
     * @param array $args
     * @return bool|mixed|Varien_Object
     * @throws Varien_Exception
     */
    #[\Override]
    public function __call($method, $args)
    {
        $key = lcfirst($this->_camelize(substr($method, 3)));
        switch (substr($method, 0, 3)) {
            case 'get':
                return $this->getData($key, $args[0] ?? null);

            case 'set':
                return $this->setData($key, $args[0] ?? null);

            case 'uns':
                return $this->unsetData($key);

            case 'has':
                return isset($this->_data[$key]);
        }
        throw new Varien_Exception('Invalid method ' . get_class($this) . '::' . $method . '(' . print_r($args, true) . ')');
    }
}
