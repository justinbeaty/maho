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

class Mage_Adminhtml_Block_Sales_Order_Shipment_View extends Mage_Adminhtml_Block_Widget_Form_Container
{
    /**
     * Mage_Adminhtml_Block_Sales_Order_Shipment_View constructor.
     */
    public function __construct()
    {
        $this->_objectId    = 'shipment_id';
        $this->_controller  = 'sales_order_shipment';
        $this->_mode        = 'view';

        parent::__construct();

        $this->_removeButton('reset');
        $this->_removeButton('delete');
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/emails')) {
            $this->_updateButton('save', 'label', Mage::helper('sales')->__('Send Tracking Information'));
            $this->_updateButton(
                'save',
                'onclick',
                Mage::helper('core/js')->getDeleteConfirmJs(
                    $this->getEmailUrl(),
                    Mage::helper('sales')->__('Are you sure you want to send Shipment email to customer?'),
                ),
            );
        }

        if ($this->getShipment()->getId()) {
            $this->_addButton('print', [
                'label'     => Mage::helper('sales')->__('Print'),
                'class'     => 'save',
                'onclick'   => Mage::helper('core/js')->getSetLocationJs($this->getPrintUrl()),
            ]);
        }
    }

    /**
     * Retrieve shipment model instance
     *
     * @return Mage_Sales_Model_Order_Shipment
     */
    public function getShipment()
    {
        return Mage::registry('current_shipment');
    }

    /**
     * @return string
     */
    #[\Override]
    public function getHeaderText()
    {
        if ($this->getShipment()->getEmailSent()) {
            $emailSent = Mage::helper('sales')->__('the shipment email was sent');
        } else {
            $emailSent = Mage::helper('sales')->__('the shipment email is not sent');
        }
        return Mage::helper('sales')->__(
            'Shipment #%1$s | %3$s (%2$s)',
            $this->getShipment()->getIncrementId(),
            $emailSent,
            $this->formatDate(
                $this->getShipment()->getCreatedAtDate(),
                'medium',
                true,
            ),
        );
    }

    /**
     * @return string
     */
    #[\Override]
    public function getBackUrl()
    {
        return $this->getUrl(
            '*/sales_order/view',
            [
                'order_id'  => $this->getShipment()->getOrderId(),
                'active_tab' => 'order_shipments',
            ],
        );
    }

    /**
     * @return string
     */
    public function getEmailUrl()
    {
        return $this->getUrl('*/sales_order_shipment/email', ['shipment_id'  => $this->getShipment()->getId()]);
    }

    /**
     * @return string
     */
    public function getPrintUrl()
    {
        return $this->getUrl('*/*/print', [
            'invoice_id' => $this->getShipment()->getId(),
        ]);
    }

    /**
     * @param string $flag
     * @return $this
     */
    public function updateBackButtonUrl($flag)
    {
        if ($flag) {
            if ($this->getShipment()->getBackUrl()) {
                return $this->_updateButton(
                    'back',
                    'onclick',
                    Mage::helper('core/js')->getSetLocationJs($this->getShipment()->getBackUrl()),
                );
            }

            return $this->_updateButton(
                'back',
                'onclick',
                Mage::helper('core/js')->getSetLocationJs($this->getUrl('*/sales_shipment/')),
            );
        }
        return $this;
    }
}
