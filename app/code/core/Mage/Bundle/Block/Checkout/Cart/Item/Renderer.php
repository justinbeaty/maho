<?php

/**
 * Maho
 *
 * @package    Mage_Bundle
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Bundle_Block_Checkout_Cart_Item_Renderer extends Mage_Checkout_Block_Cart_Item_Renderer
{
    /**
     * Get bundled selections (slections-products collection)
     *
     * Returns array of options objects.
     * Each option object will contain array of selections objects
     *
     * @param bool $useCache
     * @return array
     */
    protected function _getBundleOptions($useCache = true)
    {
        return Mage::helper('bundle/catalog_product_configuration')->getBundleOptions($this->getItem());
    }

    /**
     * Obtain final price of selection in a bundle product
     *
     * @param Mage_Catalog_Model_Product $selectionProduct
     * @return float
     */
    protected function _getSelectionFinalPrice($selectionProduct)
    {
        $helper = Mage::helper('bundle/catalog_product_configuration');
        return $helper->getSelectionFinalPrice($this->getItem(), $selectionProduct);
    }

    /**
     * Get selection quantity
     *
     * @param int $selectionId
     * @return float
     */
    protected function _getSelectionQty($selectionId)
    {
        return Mage::helper('bundle/catalog_product_configuration')->getSelectionQty($this->getProduct(), $selectionId);
    }

    /**
     * Overloaded method for getting list of bundle options
     * Caches result in quote item, because it can be used in cart 'recent view' and on same page in cart checkout
     *
     * @return array
     */
    #[\Override]
    public function getOptionList()
    {
        return Mage::helper('bundle/catalog_product_configuration')->getOptions($this->getItem());
    }

    /**
     * Return cart item error messages
     *
     * @return array
     */
    #[\Override]
    public function getMessages()
    {
        $messages = [];
        $quoteItem = $this->getItem();

        // Add basic messages occurring during this page load
        $baseMessages = $quoteItem->getMessage(false);
        if ($baseMessages) {
            foreach ($baseMessages as $message) {
                $messages[] = [
                    'text' => $message,
                    'type' => $quoteItem->getHasError() ? 'error' : 'notice',
                ];
            }
        }

        return $messages;
    }
}
