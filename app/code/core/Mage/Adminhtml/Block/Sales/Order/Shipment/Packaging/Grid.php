<?php

/**
 * Maho
 *
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2025 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Adminhtml_Block_Sales_Order_Shipment_Packaging_Grid extends Mage_Adminhtml_Block_Template
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('sales/order/shipment/packaging/grid.phtml');
    }

    /**
     * Return collection of shipment items
     *
     * @return array
     */
    public function getCollection()
    {
        if ($this->getShipment()->getId()) {
            $collection = Mage::getModel('sales/order_shipment_item')->getCollection()
                    ->setShipmentFilter($this->getShipment()->getId());
        } else {
            $collection = $this->getShipment()->getAllItems();
        }
        return $collection;
    }

    /**
     * Return collection of shipment items available to be packed
     */
    public function getAvailableItems(): array
    {
        $items = [];
        $order = $this->getShipment()->getOrder();
        foreach ($this->getCollection() as $item) {
            if ($item->getIsVirtual()) {
                continue;
            }
            $orderItem = $order->getItemById($item->getOrderItemId());
            if ($orderItem->isShipSeparately() && !($orderItem->getParentItemId() || $orderItem->getParentItem())) {
                continue;
            }
            if (!$orderItem->isShipSeparately() && ($orderItem->getParentItemId() || $orderItem->getParentItem())) {
                continue;
            }
            $items[] = $item;
        }
        return $items;
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
     * Can display customs value
     *
     * @return bool
     */
    public function displayCustomsValue()
    {
        $storeId = $this->getShipment()->getStoreId();
        $order = $this->getShipment()->getOrder();
        $address = $order->getShippingAddress();
        $shipperAddressCountryCode = Mage::getStoreConfig(
            Mage_Shipping_Model_Shipping::XML_PATH_STORE_COUNTRY_ID,
            $storeId,
        );
        $recipientAddressCountryCode = $address->getCountryId();
        if ($shipperAddressCountryCode != $recipientAddressCountryCode) {
            return true;
        }
        return false;
    }

    /**
     * Format price
     *
     * @param   float $value
     * @return  string
     */
    public function formatPrice($value)
    {
        return sprintf('%.2F', $value);
    }
}
