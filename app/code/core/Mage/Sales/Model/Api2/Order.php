<?php

/**
 * Maho
 *
 * @package    Mage_Sales
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2023 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Sales_Model_Api2_Order extends Mage_Api2_Model_Resource
{
    /**
     * Parameters' names in config with special ACL meaning
     */
    public const PARAM_GIFT_MESSAGE   = '_gift_message';
    public const PARAM_ORDER_COMMENTS = '_order_comments';
    public const PARAM_PAYMENT_METHOD = '_payment_method';
    public const PARAM_TAX_NAME       = '_tax_name';
    public const PARAM_TAX_RATE       = '_tax_rate';

    /**
     * Add gift message info to select
     *
     * @return $this
     */
    protected function _addGiftMessageInfo(Mage_Sales_Model_Resource_Order_Collection $collection)
    {
        $collection->getSelect()->joinLeft(
            ['gift_message' => $collection->getTable('giftmessage/message')],
            'main_table.gift_message_id = gift_message.gift_message_id',
            [
                'gift_message_from' => 'gift_message.sender',
                'gift_message_to'   => 'gift_message.recipient',
                'gift_message_body' => 'gift_message.message',
            ],
        );

        return $this;
    }

    /**
     * Add order payment method field to select
     *
     * @return $this
     */
    protected function _addPaymentMethodInfo(Mage_Sales_Model_Resource_Order_Collection $collection)
    {
        $collection->getSelect()->joinLeft(
            ['payment_method' => $collection->getTable('sales/order_payment')],
            'main_table.entity_id = payment_method.parent_id',
            ['payment_method' => 'payment_method.method'],
        );

        return $this;
    }

    /**
     * Add order tax information to select
     *
     * @return $this
     */
    protected function _addTaxInfo(Mage_Sales_Model_Resource_Order_Collection $collection)
    {
        $taxInfoFields = [];

        if ($this->_isTaxNameAllowed()) {
            $taxInfoFields['tax_name'] = 'order_tax.title';
        }
        if ($this->_isTaxRateAllowed()) {
            $taxInfoFields['tax_rate'] = 'order_tax.percent';
        }
        if ($taxInfoFields) {
            $collection->getSelect()->joinLeft(
                ['order_tax' => $collection->getTable('sales/order_tax')],
                'main_table.entity_id = order_tax.order_id',
                $taxInfoFields,
            );
            $collection->getSelect()->group('main_table.entity_id');
        }
        return $this;
    }

    /**
     * Retrieve a list or orders' addresses in a form of [order ID => array of addresses, ...]
     *
     * @param array $orderIds Orders identifiers
     * @return array
     */
    protected function _getAddresses(array $orderIds)
    {
        $addresses = [];

        if ($this->_isSubCallAllowed('order_address')) {
            $addressesFilter = $this->_getSubModel('order_address', [])->getFilter();
            // do addresses request if at least one attribute allowed
            if ($addressesFilter->getAllowedAttributes()) {
                /** @var Mage_Sales_Model_Resource_Order_Address_Collection $collection */
                $collection = Mage::getResourceModel('sales/order_address_collection');

                $collection->addAttributeToFilter('parent_id', $orderIds);

                foreach ($collection->getItems() as $item) {
                    $addresses[$item->getParentId()][] = $addressesFilter->out($item->toArray());
                }
            }
        }
        return $addresses;
    }

    /**
     * Retrieve collection instance for orders list
     *
     * @return Mage_Sales_Model_Resource_Order_Collection
     */
    protected function _getCollectionForRetrieve()
    {
        /** @var Mage_Sales_Model_Resource_Order_Collection $collection */
        $collection = Mage::getResourceModel('sales/order_collection');

        $this->_applyCollectionModifiers($collection);

        return $collection;
    }

    /**
     * Retrieve collection instance for single order
     *
     * @param int $orderId Order identifier
     * @return Mage_Sales_Model_Resource_Order_Collection
     */
    protected function _getCollectionForSingleRetrieve($orderId)
    {
        /** @var Mage_Sales_Model_Resource_Order_Collection $collection */
        $collection = Mage::getResourceModel('sales/order_collection');

        return $collection->addFieldToFilter($collection->getResource()->getIdFieldName(), $orderId);
    }

    /**
     * Retrieve a list or orders' comments in a form of [order ID => array of comments, ...]
     *
     * @param array $orderIds Orders' identifiers
     * @return array
     */
    protected function _getComments(array $orderIds)
    {
        $comments = [];

        if ($this->_isOrderCommentsAllowed() && $this->_isSubCallAllowed('order_comment')) {
            $commentsFilter = $this->_getSubModel('order_comment', [])->getFilter();
            // do comments request if at least one attribute allowed
            if ($commentsFilter->getAllowedAttributes()) {
                foreach ($this->_getCommentsCollection($orderIds)->getItems() as $item) {
                    $comments[$item->getParentId()][] = $commentsFilter->out($item->toArray());
                }
            }
        }
        return $comments;
    }

    /**
     * Prepare and return order comments collection
     *
     * @param array $orderIds Orders' identifiers
     * @return Mage_Sales_Model_Resource_Order_Status_History_Collection|Object
     */
    protected function _getCommentsCollection(array $orderIds)
    {
        /** @var Mage_Sales_Model_Resource_Order_Status_History_Collection $collection */
        $collection = Mage::getResourceModel('sales/order_status_history_collection');
        $collection->setOrderFilter($orderIds);

        return $collection;
    }

    /**
     * Retrieve a list or orders' items in a form of [order ID => array of items, ...]
     *
     * @param array $orderIds Orders identifiers
     * @return array
     */
    protected function _getItems(array $orderIds)
    {
        $items = [];

        if ($this->_isSubCallAllowed('order_item')) {
            $itemsFilter = $this->_getSubModel('order_item', [])->getFilter();
            // do items request if at least one attribute allowed
            if ($itemsFilter->getAllowedAttributes()) {
                /** @var Mage_Sales_Model_Resource_Order_Item_Collection $collection */
                $collection = Mage::getResourceModel('sales/order_item_collection');

                $collection->addAttributeToFilter('order_id', $orderIds);

                foreach ($collection->getItems() as $item) {
                    $items[$item->getOrderId()][] = $itemsFilter->out($item->toArray());
                }
            }
        }
        return $items;
    }

    /**
     * Check gift messages information is allowed
     *
     * @return bool
     */
    public function _isGiftMessageAllowed()
    {
        return in_array(self::PARAM_GIFT_MESSAGE, $this->getFilter()->getAllowedAttributes());
    }

    /**
     * Check order comments information is allowed
     *
     * @return bool
     */
    public function _isOrderCommentsAllowed()
    {
        return in_array(self::PARAM_ORDER_COMMENTS, $this->getFilter()->getAllowedAttributes());
    }

    /**
     * Check payment method information is allowed
     *
     * @return bool
     */
    public function _isPaymentMethodAllowed()
    {
        return in_array(self::PARAM_PAYMENT_METHOD, $this->getFilter()->getAllowedAttributes());
    }

    /**
     * Check tax name information is allowed
     *
     * @return bool
     */
    public function _isTaxNameAllowed()
    {
        return in_array(self::PARAM_TAX_NAME, $this->getFilter()->getAllowedAttributes());
    }

    /**
     * Check tax rate information is allowed
     *
     * @return bool
     */
    public function _isTaxRateAllowed()
    {
        return in_array(self::PARAM_TAX_RATE, $this->getFilter()->getAllowedAttributes());
    }

    /**
     * Get orders list
     *
     * @return array
     */
    protected function _retrieveCollection()
    {
        $collection = $this->_getCollectionForRetrieve();

        if ($this->_isPaymentMethodAllowed()) {
            $this->_addPaymentMethodInfo($collection);
        }
        if ($this->_isGiftMessageAllowed()) {
            $this->_addGiftMessageInfo($collection);
        }
        $this->_addTaxInfo($collection);

        $ordersData = [];

        foreach ($collection->getItems() as $order) {
            $ordersData[$order->getId()] = $order->toArray();
        }
        if ($ordersData) {
            foreach ($this->_getAddresses(array_keys($ordersData)) as $orderId => $addresses) {
                $ordersData[$orderId]['addresses'] = $addresses;
            }
            foreach ($this->_getItems(array_keys($ordersData)) as $orderId => $items) {
                $ordersData[$orderId]['order_items'] = $items;
            }
            foreach ($this->_getComments(array_keys($ordersData)) as $orderId => $comments) {
                $ordersData[$orderId]['order_comments'] = $comments;
            }
        }
        return $ordersData;
    }
}
