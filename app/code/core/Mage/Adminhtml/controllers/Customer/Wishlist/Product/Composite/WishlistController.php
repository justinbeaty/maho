<?php

/**
 * Maho
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2024 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Catalog composite product configuration controller
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_Customer_Wishlist_Product_Composite_WishlistController extends Mage_Adminhtml_Controller_Action
{
    /**
     * ACL resource
     * @see Mage_Adminhtml_Controller_Action::_isAllowed()
     */
    public const ADMIN_RESOURCE = 'customer/manage';

    /**
    * Wishlist we're working with
    *
    * @var Mage_Wishlist_Model_Wishlist
    */
    protected $_wishlist = null;

    /**
     * Wishlist item we're working with
     *
     * @var Mage_Wishlist_Model_Wishlist
     */
    protected $_wishlistItem = null;

    /**
     * Loads wishlist and wishlist item
     *
     * @return $this
     */
    protected function _initData()
    {
        $wishlistItemId = (int) $this->getRequest()->getParam('id');
        if (!$wishlistItemId) {
            Mage::throwException($this->__('No wishlist item id defined.'));
        }

        /** @var Mage_Wishlist_Model_Item $wishlistItem */
        $wishlistItem = Mage::getModel('wishlist/item')
            ->loadWithOptions($wishlistItemId);

        if (!$wishlistItem->getWishlistId()) {
            Mage::throwException($this->__('Wishlist item is not loaded.'));
        }

        $this->_wishlist = Mage::getModel('wishlist/wishlist')
            ->load($wishlistItem->getWishlistId());

        $this->_wishlistItem = $wishlistItem;

        return $this;
    }

    /**
     * Ajax handler to response configuration fieldset of composite product in customer's wishlist
     *
     * @return $this
     */
    public function configureAction()
    {
        try {
            $this->_initData();

            $configureResult = new Varien_Object([
                'ok'                  => true,
                'product_id'          => $this->_wishlistItem->getProductId(),
                'buy_request'         => $this->_wishlistItem->getBuyRequest(),
                'current_store_id'    => $this->_wishlistItem->getStoreId(),
                'current_customer_id' => $this->_wishlist->getCustomerId(),
            ]);

            // During order creation in the backend admin has ability to add any products to order
            Mage::helper('catalog/product')->setSkipSaleableCheck(true);

            // Render page
            Mage::helper('adminhtml/catalog_product_composite')->renderConfigureResult($this, $configureResult);

        } catch (Mage_Core_Exception $e) {
            $this->getResponse()->setBodyJson(['error' => true, 'message' => $e->getMessage()]);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->getResponse()->setBodyJson(['error' => true, 'message' => $this->__('Internal Error')]);
        }

        return $this;
    }

    /**
     * Ajax handler for submitted configuration for wishlist item
     *
     * @return false
     */
    public function updateAction()
    {
        try {
            $this->_initData();

            $buyRequest = new Varien_Object($this->getRequest()->getParams());
            /*
            $buyRequest = new Varien_Object($this->getRequest()->getPost());
            $buyRequest->unsFormKey();

            $buyRequest->setProduct($this->_wishlistItem->getProduct()->getId());
            $buyRequest->setRelatedProduct('');
            */

            $this->_wishlist
                ->updateItem($this->_wishlistItem->getId(), $buyRequest)
                ->save();

            // Mage::helper('wishlist')->calculate();

            $this->getResponse()->setBodyJson(['ok' => true]);

        } catch (Mage_Core_Exception $e) {
            $this->getResponse()->setBodyJson(['error' => true, 'message' => $e->getMessage()]);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->getResponse()->setBodyJson(['error' => true, 'message' => $this->__('Internal Error')]);
        }

        return false;
    }
}
