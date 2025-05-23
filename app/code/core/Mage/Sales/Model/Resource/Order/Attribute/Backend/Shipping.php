<?php

/**
 * Maho
 *
 * @package    Mage_Sales
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2020-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Sales_Model_Resource_Order_Attribute_Backend_Shipping extends Mage_Eav_Model_Entity_Attribute_Backend_Abstract
{
    /**
     * Perform operation before save
     *
     * @param Varien_Object|Mage_Sales_Model_Order $object
     * @return $this
     */
    #[\Override]
    public function beforeSave($object)
    {
        $shippingAddressId = $object->getShippingAddressId();
        if (is_null($shippingAddressId)) {
            $object->unsetShippingAddressId();
        }
        return $this;
    }

    /**
     * Perform operation after save
     *
     * @param Varien_Object|Mage_Sales_Model_Order $object
     * @return $this
     */
    #[\Override]
    public function afterSave($object)
    {
        $shippingAddressId = false;
        foreach ($object->getAddressesCollection() as $address) {
            if ($address->getAddressType() == 'shipping') {
                $shippingAddressId = $address->getId();
            }
        }
        if ($shippingAddressId) {
            $object->setShippingAddressId($shippingAddressId);
            $this->getAttribute()->getEntity()->saveAttribute($object, $this->getAttribute()->getAttributeCode());
        }
        return $this;
    }
}
