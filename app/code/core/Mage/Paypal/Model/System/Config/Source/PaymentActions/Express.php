<?php

/**
 * Maho
 *
 * @package    Mage_Paypal
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Source model for available paypal express payment actions
 *
 * @package    Mage_Paypal
 */
class Mage_Paypal_Model_System_Config_Source_PaymentActions_Express
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $configModel = Mage::getModel('paypal/config');
        $configModel->setMethod(Mage_Paypal_Model_Config::METHOD_WPP_EXPRESS);
        return $configModel->getPaymentActions();
    }
}
