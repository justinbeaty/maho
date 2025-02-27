<?php

/**
 * Maho
 *
 * @package    Mage_Usa
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Usa Ups type action Dropdown source
 *
 * @package    Mage_Usa
 */
class Mage_Usa_Model_Shipping_Carrier_Ups_Source_Type
{
    public function toOptionArray()
    {
        return [
            ['value' => 'UPS_XML', 'label' => Mage::helper('usa')->__('United Parcel Service XML')],
            ['value' => 'UPS_REST', 'label' => Mage::helper('usa')->__('United Parcel Service REST')],
        ];
    }
}
