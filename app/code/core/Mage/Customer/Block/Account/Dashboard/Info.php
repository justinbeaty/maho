<?php

/**
 * Maho
 *
 * @package    Mage_Customer
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2020-2024 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Customer_Block_Account_Dashboard_Info extends Mage_Core_Block_Template
{
    /**
     * @var Mage_Newsletter_Model_Subscriber|null
     */
    private $_subscription;

    /**
     * @return Mage_Customer_Model_Customer
     */
    public function getCustomer()
    {
        return Mage::getSingleton('customer/session')->getCustomer();
    }

    /**
     * @return string
     */
    public function getChangePasswordUrl()
    {
        return Mage::getUrl('*/account/edit/changepass/1');
    }

    /**
     * Get Customer Subscription Object Information
     *
     * @return Mage_Newsletter_Model_Subscriber
     */
    public function getSubscriptionObject()
    {
        if (is_null($this->_subscription)) {
            $this->_subscription = Mage::getModel('newsletter/subscriber')->loadByCustomer(
                Mage::getSingleton('customer/session')->getCustomer(),
            );
        }

        return $this->_subscription;
    }

    /**
     * Gets Customer subscription status
     *
     * @return bool
     */
    public function getIsSubscribed()
    {
        return $this->getSubscriptionObject()->isSubscribed();
    }

    /**
     *  Newsletter module availability
     *
     *  @return bool
     */
    public function isNewsletterEnabled()
    {
        return $this->getLayout()->getBlockSingleton('customer/form_register')->isNewsletterEnabled();
    }
}
