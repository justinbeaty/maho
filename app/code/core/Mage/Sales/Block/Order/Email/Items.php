<?php

/**
 * Maho
 *
 * @package    Mage_Sales
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * @method Mage_Sales_Model_Order getOrder()
 */
class Mage_Sales_Block_Order_Email_Items extends Mage_Sales_Block_Items_Abstract
{
    public function getGiftMessageOrder(): ?Mage_GiftMessage_Model_Message
    {
        if (!$this->isModuleOutputEnabled('Mage_GiftMessage')) {
            return null;
        }
        /** @var Mage_GiftMessage_Helper_Message $helper */
        $helper = $this->helper('giftmessage/message');
        $_order = $this->getOrder();
        if ($helper->isMessagesAvailable('order', $_order, $_order->getStore()) && $_order->getGiftMessageId()) {
            return $helper->getGiftMessage($_order->getGiftMessageId());
        }
        return null;
    }
}
