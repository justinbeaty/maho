<?php

/**
 * Maho
 *
 * @package    Mage_Customer
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2020-2024 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Customer_Model_Group_Api extends Mage_Api_Model_Resource_Abstract
{
    /**
     * Retrieve groups
     *
     * @return array
     */
    public function items()
    {
        $collection = Mage::getModel('customer/group')->getCollection();

        $result = [];
        foreach ($collection as $group) {
            /** @var Mage_Customer_Model_Group $group */
            $result[] = $group->toArray(['customer_group_id', 'customer_group_code']);
        }

        return $result;
    }
}
