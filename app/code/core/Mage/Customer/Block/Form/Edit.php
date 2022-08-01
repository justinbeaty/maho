<?php
/**
 * Maho
 *
 * @category   Mage
 * @package    Mage_Customer
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2023 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Customer edit form block
 *
 * @category   Mage
 * @package    Mage_Customer
 */
class Mage_Customer_Block_Form_Edit extends Mage_Customer_Block_Account_Dashboard
{
    /**
     * Return extra EAV fields used in this form
     *
     * @return array
     */
    public function extraFields()
    {
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = $this->getCustomer();

        /** @var Mage_Customer_Model_Form $form */
        $form = Mage::getModel('customer/form');
        $form->setFormCode('customer_account_edit')
             ->setEntity($customer)
             ->initDefaultValues();

        $attributes = $form->getAttributes();
        foreach ($attributes as $code => $attribute) {
            if (!$attribute->getIsUserDefined()) {
                unset($attributes[$code]);
            }
        }

        return $attributes;
    }
}
