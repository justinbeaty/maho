<?php

/**
 * Maho
 *
 * @package    Mage_Usa
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Usa_Model_Shipping_Carrier_Usps_Source_Method
{
    public function toOptionArray()
    {
        /** @var Mage_Usa_Model_Shipping_Carrier_Usps $usps */
        $usps = Mage::getSingleton('usa/shipping_carrier_usps');
        $arr = [];
        foreach ($usps->getCode('method') as $k => $v) {
            $arr[] = ['value' => $k, 'label' => Mage::helper('usa')->__($v)];
        }
        return $arr;
    }
}
