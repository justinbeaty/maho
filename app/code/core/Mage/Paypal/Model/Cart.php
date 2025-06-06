<?php

/**
 * Maho
 *
 * @package    Mage_Paypal
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2025 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * PayPal-specific model for shopping cart items and totals
 * The main idea is to accommodate all possible totals into PayPal-compatible 4 totals and line items
 */
class Mage_Paypal_Model_Cart
{
    /**
     * Totals that PayPal supports when passing shopping cart
     *
     * @var string
     */
    public const TOTAL_SUBTOTAL = 'subtotal';
    public const TOTAL_DISCOUNT = 'discount';
    public const TOTAL_TAX      = 'tax';
    public const TOTAL_SHIPPING = 'shipping';

    /**
     * Order or quote instance
     *
     * @var Mage_Sales_Model_Order|Mage_Sales_Model_Quote
     */
    protected $_salesEntity = null;

    /**
     * Rendered cart items
     * Array of Varien_Objects
     *
     * @var array
     */
    protected $_items = [];

    /**
     * Rendered cart totals
     * Associative array with the keys from constants above
     *
     * @var array
     */
    protected $_totals = [];

    /**
     * Set of optional descriptions for the item that may replace a total and composed of several amounts
     * Array of strings
     *
     * @var array
     */
    protected $_totalLineItemDescriptions = [];

    /**
     * Lazy initialization indicator for rendering
     *
     * @var bool
     */
    protected $_shouldRender = true;

    /**
     * Validation result for the rendered cart items
     *
     * @var bool
     */
    protected $_areItemsValid = false;

    /**
     * Validation result for the rendered totals
     *
     * @var bool
     */
    protected $_areTotalsValid = false;

    /**
     * Whether to render discount total as a line item
     * Use case: WPP
     *
     * @var bool
     */
    protected $_isDiscountAsItem = false;

    /**
     * Whether to render shipping total as a line item
     * Use case: WPS
     *
     * @var bool
     */
    protected $_isShippingAsItem = false;

    /**
     * Require instance of an order or a quote
     *
     * @param array $params
     */
    public function __construct($params = [])
    {
        $salesEntity = array_shift($params);
        if (is_object($salesEntity)
            && (($salesEntity instanceof Mage_Sales_Model_Order) || ($salesEntity instanceof Mage_Sales_Model_Quote))
        ) {
            $this->_salesEntity = $salesEntity;
        } else {
            throw new Exception('Invalid sales entity provided.');
        }
    }

    /**
     * Getter for the current sales entity
     *
     * @return Mage_Sales_Model_Order
     * @return Mage_Sales_Model_Quote
     */
    public function getSalesEntity()
    {
        return $this->_salesEntity;
    }

    /**
     * Render and get line items
     * By default returns false if the items are invalid
     *
     * @param bool $bypassValidation
     * @return array|false
     */
    public function getItems($bypassValidation = false)
    {
        $this->_render();
        if (!$bypassValidation && !$this->_areItemsValid) {
            return false;
        }
        return $this->_items;
    }

    /**
     * Render and get totals
     * If the totals are invalid for any reason, they will be merged into one amount (subtotal is utilized for it)
     * An option to subtract discount from the subtotal is available
     *
     * @param bool $mergeDiscount
     * @return array
     */
    public function getTotals($mergeDiscount = false)
    {
        $this->_render();

        // cut down totals to one total if they are invalid
        if (!$this->_areTotalsValid) {
            $totals = [
                self::TOTAL_SUBTOTAL => $this->_totals[self::TOTAL_SUBTOTAL] + $this->_totals[self::TOTAL_TAX],
            ];
            if (!$this->_isShippingAsItem) {
                $totals[self::TOTAL_SUBTOTAL] += $this->_totals[self::TOTAL_SHIPPING];
            }
            if (!$this->_isDiscountAsItem) {
                $totals[self::TOTAL_SUBTOTAL] -= $this->_totals[self::TOTAL_DISCOUNT];
            }
            return $totals;
        } elseif ($mergeDiscount) {
            $totals = $this->_totals;
            unset($totals[self::TOTAL_DISCOUNT]);
            if (!$this->_isDiscountAsItem) {
                $totals[self::TOTAL_SUBTOTAL] -= $this->_totals[self::TOTAL_DISCOUNT];
            }
            return $totals;
        }
        return $this->_totals;
    }

    /**
     * Add a line item
     *
     * @param string $name
     * @param numeric $qty
     * @param float $amount
     * @param string $identifier
     * @return Varien_Object
     */
    public function addItem($name, $qty, $amount, $identifier = null)
    {
        $this->_shouldRender = true;
        $item = new Varien_Object([
            'name'   => $name,
            'qty'    => $qty,
            'amount' => (float) $amount,
        ]);
        if ($identifier) {
            $item->setData('id', $identifier);
        }
        $this->_items[] = $item;
        return $item;
    }

    /**
     * Remove item from cart by identifier
     *
     * @param string $identifier
     * @return bool
     */
    public function removeItem($identifier)
    {
        foreach ($this->_items as $key => $item) {
            if ($item->getId() == $identifier) {
                unset($this->_items[$key]);
                return true;
            }
        }
        return false;
    }

    /**
     * Compound the specified amount with the specified total
     *
     * @param string $code
     * @param float $amount
     * @param string $lineItemDescription
     * @return $this
     */
    public function updateTotal($code, $amount, $lineItemDescription = null)
    {
        $this->_shouldRender = true;
        if (isset($this->_totals[$code])) {
            $this->_totals[$code] += $amount;
            if ($lineItemDescription) {
                $this->_totalLineItemDescriptions[$code][] = $lineItemDescription;
            }
        }
        return $this;
    }

    /**
     * Get/Set whether to render the discount total as a line item
     *
     * @param bool $setValue
     * @return bool|$this
     */
    public function isDiscountAsItem($setValue = null)
    {
        return $this->_totalAsItem('_isDiscountAsItem', $setValue);
    }

    /**
     * Get/Set whether to render the discount total as a line item
     *
     * @param bool $setValue
     * @return bool|$this
     */
    public function isShippingAsItem($setValue = null)
    {
        return $this->_totalAsItem('_isShippingAsItem', $setValue);
    }

    /**
     * (re)Render all items and totals
     */
    protected function _render()
    {
        if (!$this->_shouldRender) {
            return;
        }

        // regular items from the sales entity
        $this->_items = [];
        foreach ($this->_salesEntity->getAllItems() as $item) {
            if (!$item->getParentItem()) {
                $this->_addRegularItem($item);
            }
        }
        $lastRegularItemKey = array_key_last($this->_items);

        // regular totals
        $shippingDescription = '';
        if ($this->_salesEntity instanceof Mage_Sales_Model_Order) {
            $shippingDescription = $this->_salesEntity->getShippingDescription();
            $this->_totals = [
                self::TOTAL_SUBTOTAL => $this->_salesEntity->getBaseSubtotal(),
                self::TOTAL_TAX      => $this->_salesEntity->getBaseTaxAmount(),
                self::TOTAL_SHIPPING => $this->_salesEntity->getBaseShippingAmount(),
                self::TOTAL_DISCOUNT => abs($this->_salesEntity->getBaseDiscountAmount()),
            ];
            $this->_applyHiddenTaxWorkaround($this->_salesEntity);
        } else {
            $address = $this->_salesEntity->getIsVirtual() ?
                $this->_salesEntity->getBillingAddress() : $this->_salesEntity->getShippingAddress();
            $shippingDescription = $address->getShippingDescription();
            $this->_totals = [
                self::TOTAL_SUBTOTAL => $this->_salesEntity->getBaseSubtotal(),
                self::TOTAL_TAX      => $address->getBaseTaxAmount(),
                self::TOTAL_SHIPPING => $address->getBaseShippingAmount(),
                self::TOTAL_DISCOUNT => abs($address->getBaseDiscountAmount()),
            ];
            $this->_applyHiddenTaxWorkaround($address);
        }
        $originalDiscount = $this->_totals[self::TOTAL_DISCOUNT];

        // arbitrary items, total modifications
        Mage::dispatchEvent('paypal_prepare_line_items', ['paypal_cart' => $this]);

        // distinguish original discount among the others
        if ($originalDiscount > 0.0001 && isset($this->_totalLineItemDescriptions[self::TOTAL_DISCOUNT])) {
            $this->_totalLineItemDescriptions[self::TOTAL_DISCOUNT][] = Mage::helper('sales')->__('Discount (%s)', Mage::app()->getStore()->convertPrice($originalDiscount, true, false));
        }

        // discount, shipping as items
        if ($this->_isDiscountAsItem && $this->_totals[self::TOTAL_DISCOUNT]) {
            $this->addItem(
                Mage::helper('paypal')->__('Discount'),
                1,
                -1.00 * $this->_totals[self::TOTAL_DISCOUNT],
                $this->_renderTotalLineItemDescriptions(self::TOTAL_DISCOUNT),
            );
        }
        $shippingItemId = $this->_renderTotalLineItemDescriptions(self::TOTAL_SHIPPING, $shippingDescription);
        if ($this->_isShippingAsItem && (float) $this->_totals[self::TOTAL_SHIPPING]) {
            $this->addItem(
                Mage::helper('paypal')->__('Shipping'),
                1,
                (float) $this->_totals[self::TOTAL_SHIPPING],
                $shippingItemId,
            );
        }

        // compound non-regular items into subtotal
        foreach ($this->_items as $key => $item) {
            if ($key > $lastRegularItemKey && $item->getAmount() != 0) {
                $this->_totals[self::TOTAL_SUBTOTAL] += $item->getAmount();
            }
        }

        $this->_validate();
        // if cart items are invalid, prepare cart for transfer without line items
        if (!$this->_areItemsValid) {
            $this->removeItem($shippingItemId);
        }

        $this->_shouldRender = false;
    }

    /**
     * Merge multiple descriptions  by a total code into a string
     *
     * @param string $code
     * @param string $prepend
     * @param string $append
     * @param string $glue
     * @return string
     */
    protected function _renderTotalLineItemDescriptions($code, $prepend = '', $append = '', $glue = '; ')
    {
        $result = [];
        if ($prepend) {
            $result[] = $prepend;
        }
        if (isset($this->_totalLineItemDescriptions[$code])) {
            $result = array_merge($this->_totalLineItemDescriptions[$code]);
        }
        if ($append) {
            $result[] = $append;
        }
        return implode($glue, $result);
    }

    /**
     * Check the line items and totals according to PayPal business logic limitations
     */
    protected function _validate()
    {
        $this->_areItemsValid = true;
        $this->_areTotalsValid = false;

        $referenceAmount = $this->_salesEntity->getBaseGrandTotal();

        $itemsSubtotal = 0;
        foreach ($this->_items as $i) {
            $itemsSubtotal = $itemsSubtotal + $i['qty'] * $i['amount'];
        }
        $sum = $itemsSubtotal + $this->_totals[self::TOTAL_TAX];
        if (!$this->_isShippingAsItem) {
            $sum += $this->_totals[self::TOTAL_SHIPPING];
        }
        if (!$this->_isDiscountAsItem) {
            $sum -= $this->_totals[self::TOTAL_DISCOUNT];
        }
        /**
         * numbers are intentionally converted to strings because of possible comparison error
         * see http://php.net/float
         */
        // match sum of all the items and totals to the reference amount
        if (sprintf('%.4F', $sum) != sprintf('%.4F', $referenceAmount)) {
            $adjustment = $sum - $referenceAmount;
            $this->_totals[self::TOTAL_SUBTOTAL] = $this->_totals[self::TOTAL_SUBTOTAL] - $adjustment;
        }

        // PayPal requires to have discount less than items subtotal
        if (!$this->_isDiscountAsItem) {
            $this->_areTotalsValid = round($this->_totals[self::TOTAL_DISCOUNT], 4) < round($itemsSubtotal, 4);
        } else {
            $this->_areTotalsValid = $itemsSubtotal > 0.00001;
        }

        $this->_areItemsValid = $this->_areItemsValid && $this->_areTotalsValid;
    }

    /**
     * Check whether items are valid
     *
     * @return bool
     */
    public function areItemsValid()
    {
        return $this->_areItemsValid;
    }

    /**
     * Add a usual line item with amount and qty
     *
     * @return Varien_Object
     */
    protected function _addRegularItem(Varien_Object $salesItem)
    {
        if ($this->_salesEntity instanceof Mage_Sales_Model_Order) {
            // TODO: nominal item for order
            $qty = (int) $salesItem->getQtyOrdered();
            $amount = (float) $salesItem->getBasePrice();
        } else {
            $qty = (int) $salesItem->getTotalQty();
            $amount = $salesItem->isNominal() ? 0 : (float) $salesItem->getBaseCalculationPrice();
        }
        // workaround in case if item subtotal precision is not compatible with PayPal (.2)
        $subAggregatedLabel = '';
        if ($amount - round($amount, 2)) {
            $amount = $amount * $qty;
            $subAggregatedLabel = ' x' . $qty;
            $qty = 1;
        }

        // aggregate item price if item qty * price does not match row total
        if ($amount * $qty != $salesItem->getBaseRowTotal()) {
            $amount = (float) $salesItem->getBaseRowTotal();
            $subAggregatedLabel = ' x' . $qty;
            $qty = 1;
        }

        return $this->addItem($salesItem->getName() . $subAggregatedLabel, $qty, $amount, $salesItem->getSku());
    }

    /**
     * Get/Set for the specified variable.
     * If the value changes, the re-rendering is commenced
     *
     * @param string $var
     * @param mixed $setValue
     * @return mixed|$this
     */
    private function _totalAsItem($var, $setValue = null)
    {
        if ($setValue !== null) {
            if ($setValue != $this->$var) {
                $this->_shouldRender = true;
            }
            $this->$var = $setValue;
            return $this;
        }
        return $this->$var;
    }

    /**
     * Add "hidden" discount and shipping tax
     *
     * Go ahead, try to understand ]:->
     *
     * Tax settings for getting "discount tax":
     * - Catalog Prices = Including Tax
     * - Apply Customer Tax = After Discount
     * - Apply Discount on Prices = Including Tax
     *
     * Test case for getting "hidden shipping tax":
     * - Make sure shipping is taxable (set shipping tax class)
     * - Catalog Prices = Including Tax
     * - Shipping Prices = Including Tax
     * - Apply Customer Tax = After Discount
     * - Create a shopping cart price rule with % discount applied to the Shipping Amount
     * - run shopping cart and estimate shipping
     * - go to PayPal
     *
     * @param Mage_Core_Model_Abstract $salesEntity
     */
    private function _applyHiddenTaxWorkaround($salesEntity)
    {
        $this->_totals[self::TOTAL_TAX] += (float) $salesEntity->getBaseHiddenTaxAmount();
        $this->_totals[self::TOTAL_TAX] += (float) $salesEntity->getBaseShippingHiddenTaxAmount();
    }

    /**
     * Check whether any item has negative amount
     *
     * @return bool
     */
    public function hasNegativeItemAmount()
    {
        foreach ($this->_items as $item) {
            if ($item->getAmount() < 0) {
                return true;
            }
        }
        return false;
    }
}
