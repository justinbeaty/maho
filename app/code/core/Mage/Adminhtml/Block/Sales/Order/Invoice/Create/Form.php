<?php

/**
 * Maho
 *
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Adminhtml_Block_Sales_Order_Invoice_Create_Form extends Mage_Adminhtml_Block_Sales_Order_Abstract
{
    /**
     * Retrieve invoice order
     *
     * @return Mage_Sales_Model_Order
     */
    #[\Override]
    public function getOrder()
    {
        return $this->getInvoice()->getOrder();
    }

    /**
     * Retrieve source
     *
     * @return Mage_Sales_Model_Order_Invoice
     */
    public function getSource()
    {
        return $this->getInvoice();
    }

    /**
     * Retrieve invoice model instance
     *
     * @return Mage_Sales_Model_Order_Invoice
     */
    public function getInvoice()
    {
        return Mage::registry('current_invoice');
    }

    #[\Override]
    protected function _prepareLayout()
    {
        /*  $infoBlock = $this->getLayout()->createBlock('adminhtml/sales_order_view_info')
             ->setOrder($this->getInvoice()->getOrder());
         $this->setChild('order_info', $infoBlock);
*/
        /*  $this->setChild(
             'items',
               $this->getLayout()->createBlock('adminhtml/sales_order_invoice_create_items')
           );
           */
        $trackingBlock = $this->getLayout()->createBlock('adminhtml/sales_order_invoice_create_tracking');
        //$this->setChild('order_tracking', $trackingBlock);
        $this->setChild('tracking', $trackingBlock);

        /*
        $paymentInfoBlock = $this->getLayout()->createBlock('adminhtml/sales_order_payment')
           ->setPayment($this->getInvoice()->getOrder()->getPayment());
        $this->setChild('payment_info', $paymentInfoBlock);
        */
        return parent::_prepareLayout();
    }

    public function getSaveUrl()
    {
        return $this->getUrl('*/*/save', ['order_id' => $this->getInvoice()->getOrderId()]);
    }

    public function canCreateShipment()
    {
        foreach ($this->getInvoice()->getAllItems() as $item) {
            if ($item->getOrderItem()->getQtyToShip()) {
                return true;
            }
        }
        return false;
    }

    public function hasInvoiceShipmentTypeMismatch()
    {
        foreach ($this->getInvoice()->getAllItems() as $item) {
            if ($item->getOrderItem()->isChildrenCalculated() && !$item->getOrderItem()->isShipSeparately()) {
                return true;
            }
        }
        return false;
    }

    public function canShipPartiallyItem()
    {
        $value = $this->getOrder()->getCanShipPartiallyItem();
        if (!is_null($value) && !$value) {
            return false;
        }
        return true;
    }

    /**
     * Return forced creating of shipment flag
     *
     * @return int
     */
    public function getForcedShipmentCreate()
    {
        return (int) $this->getOrder()->getForcedDoShipmentWithInvoice();
    }
}
