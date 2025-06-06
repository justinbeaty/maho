<?php

/**
 * Maho
 *
 * @package    Mage_Core
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2020-2025 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Core_Model_Config_Element extends Varien_Simplexml_Element
{
    /**
     * @param string $var
     * @param string|true $value
     * @return bool
     */
    public function is($var, $value = true)
    {
        $flag = $this->$var;

        if ($value === true) {
            $flag = strtolower((string) $flag);
            if (!empty($flag) && $flag !== 'false' && $flag !== 'off') {
                return true;
            } else {
                return false;
            }
        }

        return !empty($flag) && (strcasecmp((string) $value, (string) $flag) === 0);
    }

    /**
     * @return false|string
     */
    public function getClassName()
    {
        if ($this->class) {
            $model = (string) $this->class;
        } elseif ($this->model) {
            $model = (string) $this->model;
        } else {
            return false;
        }
        return Mage::getConfig()->getModelClassName($model);
    }
}
