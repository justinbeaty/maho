<?php

/**
 * Maho
 *
 * @category   Mage
 * @package    Mage_Wishlist
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2023 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Wishlist model
 *
 * @category   Mage
 * @package    Mage_Wishlist
 *
 * @method Mage_Wishlist_Model_Resource_Wishlist _getResource()
 * @method Mage_Wishlist_Model_Resource_Wishlist getResource()
 * @method Mage_Wishlist_Model_Resource_Wishlist_Collection getCollection()
 *
 * @method int getShared()
 * @method $this setShared(int $value)
 * @method string getSharingCode()
 * @method $this setSharingCode(string $value)
 * @method string getUpdatedAt()
 * @method $this setUpdatedAt(string $value)
 * @method string getVisibility()
 */
class Mage_Wishlist_Model_Wishlist extends Mage_Core_Model_Abstract
{
    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'wishlist';
    /**
     * Wishlist item collection
     *
     * @var Mage_Wishlist_Model_Resource_Item_Collection|null
     */
    protected $_itemCollection = null;

    /**
     * Store filter for wishlist
     *
     * @var Mage_Core_Model_Store|null
     */
    protected $_store = null;

    /**
     * Shared store ids (website stores)
     *
     * @var array|null
     */
    protected $_storeIds = null;

    /**
     * Entity cache tag
     *
     * @var string
     */
    protected $_cacheTag = 'wishlist';

    /**
     * Initialize resource model
     */
    #[\Override]
    protected function _construct()
    {
        $this->_init('wishlist/wishlist');
    }

    /**
     * Load wishlist by customer
     *
     * @param mixed $customer
     * @param bool $create Create wishlist if don't exists
     * @return $this
     */
    public function loadByCustomer($customer, $create = false)
    {
        if ($customer instanceof Mage_Customer_Model_Customer) {
            $customer = $customer->getId();
        }

        $customer = (int) $customer;
        $customerIdFieldName = $this->_getResource()->getCustomerIdFieldName();
        $this->_getResource()->load($this, $customer, $customerIdFieldName);
        if (!$this->getId() && $create) {
            $this->setCustomerId($customer);
            $this->setSharingCode($this->_getSharingRandomCode());
            $this->save();
        }

        return $this;
    }

    /**
     * Retrieve wishlist name
     *
     * @return string
     */
    public function getName()
    {
        $name = $this->_getData('name');
        if (!strlen($name)) {
            return Mage::helper('wishlist')->getDefaultWishlistName();
        }
        return $name;
    }

    /**
     * Set random sharing code
     *
     * @return $this
     */
    public function generateSharingCode()
    {
        $this->setSharingCode($this->_getSharingRandomCode());
        return $this;
    }

    /**
     * Load by sharing code
     *
     * @param string $code
     * @return $this
     */
    public function loadByCode($code)
    {
        $this->_getResource()->load($this, $code, 'sharing_code');
        if (!$this->getShared()) {
            $this->setId(null);
        }

        return $this;
    }

    /**
     * Retrieve sharing code (random string)
     *
     * @return string
     */
    protected function _getSharingRandomCode()
    {
        return Mage::helper('core')->uniqHash();
    }

    /**
     * Set date of last update for wishlist
     *
     * @return $this
     */
    #[\Override]
    protected function _beforeSave()
    {
        parent::_beforeSave();
        $this->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate());
        return $this;
    }

    /**
     * Save related items
     *
     * @return $this
     */
    #[\Override]
    protected function _afterSave()
    {
        parent::_afterSave();

        if ($this->_itemCollection !== null) {
            Mage::log('save');
            $this->getItemCollection()->save();
        }
        return $this;
    }

    /**
     * Add catalog product object data to wishlist
     *
     * @param   int $qty
     * @param   bool $forciblySetQty
     * @return  Mage_Wishlist_Model_Item
     */
    protected function _addCatalogProduct(Mage_Catalog_Model_Product $product, $qty = 1)
    {
        $newItem = false;
        $item = $this->getItemByProduct($product);
        if (!$item) {
            $item = Mage::getModel('wishlist/item');
            $item
                ->setWishlist($this)
                ->setProductId($product->getId())
                ->setWishlistId($this->getId())
                ->setAddedAt(Varien_Date::now())
                ->setStoreId($product->hasWishlistStoreId() ? $product->getWishlistStoreId() : $this->getStore()->getId())
                ->setOptions($product->getCustomOptions())
                ->setProduct($product)
                ->setQty($qty);

            if (Mage::app()->getStore()->isAdmin()) {
                $item->setStoreId($this->getStore()->getId());
            } else {
                $item->setStoreId(Mage::app()->getStore()->getId());
            }
            $newItem = true;
        }

        /**
         * We can't modify existing child items
         */
        if ($item->getId() && $product->getParentProductId()) {
            return $item;
        }

        $item->setOptions($product->getCustomOptions())
            ->setProduct($product);

        // Add only item that is not in quote already (there can be other new or already saved item
        if ($newItem) {
            Mage::log('is new');
            //$this->addItem($item);
        }

        $item->save();

        return $item;
    }


    protected function _addCatalogProduct2(Mage_Catalog_Model_Product $product, $qty = 1, $forciblySetQty = false)
    {
        $item = null;
        foreach ($this->getItemCollection() as $wishlistItem) {
            if ($wishlistItem->representProduct($product)) {
                $item = $wishlistItem;
                break;
            }
        }
        /*
        $item = array_find(
            $this->getItemCollection(),
            fn ($wishlistItem) => $wishlistItem->representProduct($product),
        );
        */

        if ($item === null) {
            //Mage::log('is new');
            $item = Mage::getModel('wishlist/item')
                ->setProductId($product->getId())
                ->setWishlistId($this->getId())
                ->setAddedAt(Varien_Date::now())
                ->setStoreId($product->hasWishlistStoreId() ? $product->getWishlistStoreId() : $this->getStore()->getId())
                ->setOptions($product->getCustomOptions())
                ->setProduct($product)
                ->setQty($qty)
                ->save();

            Mage::dispatchEvent('wishlist_item_add_after', ['wishlist' => $this]);

            $this->addItem($item);

        } else {
            //Mage::log('is old');
            if ($forciblySetQty) {
                $item->setQty((int) $qty);
            } else {
                $item->setQty($item->getQty() + (int) $qty);
            }
            $item->save();
        }

        //$this->addItem($item);

        return $item;
    }

    /**
     * Retrieve wishlist item collection
     *
     * @return Mage_Wishlist_Model_Resource_Item_Collection
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getItemCollection()
    {
        if (is_null($this->_itemCollection)) {
            $this->_itemCollection =  Mage::getResourceModel('wishlist/item_collection')
                ->addWishlistFilter($this)
                ->setVisibilityFilter();

            if (Mage::app()->getStore()->isAdmin()) {
                $customer = Mage::getModel('customer/customer')->load($this->getCustomerId());
                $this->_itemCollection
                    ->setWebsiteId($customer->getWebsiteId())
                    ->setCustomerGroupId($customer->getGroupId())
                    ->addStoreFilter($this->getSharedStoreIds(false));
            } else {
                $this->_itemCollection
                    ->addStoreFilter($this->getSharedStoreIds());
            }
        }

        return $this->_itemCollection;
    }

    /**
     * Retrieve wishlist item collection
     *
     * @param int $itemId
     * @return Mage_Wishlist_Model_Item|false
     */
    public function getItem($itemId)
    {
        if (!$itemId) {
            return false;
        }
        return $this->getItemCollection()->getItemById($itemId);
    }

    /**
     * Retrieve quote items array
     *
     * @return Mage_Sales_Model_Quote_Item[]
     */
    public function getAllItems()
    {
        $items = [];
        foreach ($this->getItemCollection() as $item) {
            if (!$item->isDeleted()) {
                $items[] =  $item;
            }
        }
        return $items;
    }

    /**
     * Retrieve quote item by product id
     *
     * @param   Mage_Catalog_Model_Product $product
     * @return  Mage_Sales_Model_Quote_Item|false
     */
    public function getItemByProduct($product)
    {
        foreach ($this->getAllItems() as $item) {
            if ($item->representProduct($product)) {
                return $item;
            }
        }
        return false;
    }



    /**
     * Retrieve Product collection
     *
     * @deprecated after 1.4.2.0
     * @see Mage_Wishlist_Model_Wishlist::getItemCollection()
     *
     * @return Mage_Wishlist_Model_Resource_Product_Collection
     */
    public function getProductCollection()
    {
        $collection = $this->getData('product_collection');
        if (is_null($collection)) {
            $collection = Mage::getResourceModel('wishlist/product_collection');
            $this->setData('product_collection', $collection);
        }
        return $collection;
    }

    /**
     * Adding item to wishlist
     *
     * @return  $this
     */
    public function addItem(Mage_Wishlist_Model_Item $item)
    {
        $item->setWishlist($this);
        if (!$item->getId()) {
            $this->getItemCollection()->addItem($item);
            Mage::dispatchEvent('wishlist_add_item', ['item' => $item]);
        }
        return $this;
    }

    /**
     * Adds new product to wishlist.
     * Returns new item or string on error.
     *
     * @param int|Mage_Catalog_Model_Product $product
     * @param mixed $buyRequest
     * @param bool $forciblySetQty
     * @return Mage_Wishlist_Model_Item|string
     */
    public function addNewItem($product, $buyRequest = null, $forciblySetQty = false)
    {
        if (is_array($buyRequest)) {
            $buyRequest = new Varien_Object($buyRequest);
        } elseif (is_string($buyRequest)) {
            $buyRequest = new Varien_Object(unserialize($buyRequest, ['allowed_classes' => false]));
        } elseif (!$buyRequest instanceof Varien_Object) {
            $buyRequest = new Varien_Object();
        }

        // Always load product, to ensure:
        //   a) we have new instance and do not interfere with other products in wishlist
        //   b) product has full set of attributes

        if ($product instanceof Mage_Catalog_Model_Product) {
            $productId = $product->getId();
            // Maybe force some store by wishlist internal properties
            //$storeId = $product->getWishlistStoreId() ?? $product->getStoreId();
            $storeId = $product->hasWishlistStoreId() ? $product->getWishlistStoreId() : $product->getStoreId();
        } else {
            $productId = (int) $product;
            if ($buyRequest->getStoreId()) {
                $storeId = $buyRequest->getStoreId();
            } else {
                $storeId = Mage::app()->getStore()->getId();
            }
        }

        /** @var Mage_Catalog_Model_Product $product */
        $product = Mage::getModel('catalog/product')
            ->setStoreId($storeId)
            ->load($productId);

        $buyRequest->unsId();
        $buyRequest->setProduct($product->getId());
        $buyRequest->setRelatedProducts('');
        $buyRequest->setWebsiteId('1');

        $cartCandidates = $product->getTypeInstance(true)
            ->processConfiguration($buyRequest, $product);


        Mage::log("load product {$product->getStore()->getId()} {$product->getId()}");
        Mage::log($buyRequest->debug());

        // Error message
        if (is_string($cartCandidates)) {
            return $cartCandidates;
        }

        // Ensure array if prepare process return one object
        if (!is_array($cartCandidates)) {
            $cartCandidates = [$cartCandidates];
        }

        $errors = [];
        $items = [];

        foreach ($cartCandidates as $candidate) {
            if ($candidate->getParentProductId()) {
                continue;
            }
            //Mage::log($candidate->debug());
            $candidate->setWishlistStoreId($storeId);

            $qty = max($candidate->getQty(), 1);
            $item = $this->_addCatalogProduct($candidate, $qty, $forciblySetQty);
            $items[] = $item;

            //Mage::log($item->debug());
            // Collect errors instead of throwing first one
            if ($item->getHasError()) {
                $errors[] = $item->getMessage();
            }
        }

        if (!empty($errors)) {
            Mage::throwException(implode("\n", array_unique($errors)));
        }

        Mage::dispatchEvent('wishlist_product_add_after', ['items' => $items]);

        return $item;
    }

    /**
     * Set customer id
     *
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId($customerId)
    {
        return $this->setData($this->_getResource()->getCustomerIdFieldName(), $customerId);
    }

    /**
     * Retrieve customer id
     *
     * @return int
     */
    public function getCustomerId()
    {
        return $this->getData($this->_getResource()->getCustomerIdFieldName());
    }

    /**
     * Retrieve data for save
     *
     * @return array
     */
    public function getDataForSave()
    {
        $data = [];
        $data[$this->_getResource()->getCustomerIdFieldName()] = $this->getCustomerId();
        $data['shared']      = (int) $this->getShared();
        $data['sharing_code'] = $this->getSharingCode();
        return $data;
    }

    /**
     * Retrieve shared store ids for current website or all stores if $current is false
     *
     * @param bool $current Use current website or not
     * @return array
     */
    public function getSharedStoreIds($current = true)
    {
        if (is_null($this->_storeIds) || !is_array($this->_storeIds)) {
            if ($current) {
                $this->_storeIds = $this->getStore()->getWebsite()->getStoreIds();
            } else {
                $storeIds = [];
                $stores = Mage::app()->getStores();
                foreach ($stores as $store) {
                    $storeIds[] = $store->getId();
                }
                $this->_storeIds = $storeIds;
            }
        }
        return $this->_storeIds;
    }

    /**
     * Set shared store ids
     *
     * @param array $storeIds
     * @return $this
     */
    public function setSharedStoreIds($storeIds)
    {
        $this->_storeIds = (array) $storeIds;
        return $this;
    }

    /**
     * Retrieve wishlist store object
     *
     * @return Mage_Core_Model_Store
     */
    public function getStore()
    {
        if (is_null($this->_store)) {
            $this->setStore(Mage::app()->getStore());
        }
        return $this->_store;
    }

    /**
     * Set wishlist store
     *
     * @param Mage_Core_Model_Store $store
     * @return $this
     */
    public function setStore($store)
    {
        $this->_store = $store;
        return $this;
    }

    /**
     * Retrieve wishlist items count
     *
     * @return int
     */
    public function getItemsCount()
    {
        return $this->getItemCollection()->getSize();
    }

    /**
     * Retrieve wishlist has salable item(s)
     *
     * @return bool
     */
    public function isSalable()
    {
        foreach ($this->getItemCollection() as $item) {
            if ($item->getProduct()->getIsSalable()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check customer is owner this wishlist
     *
     * @param int $customerId
     * @return bool
     */
    public function isOwner($customerId)
    {
        return $customerId == $this->getCustomerId();
    }

    /**
     * Update wishlist Item and set data from request
     *
     * $params sets how current item configuration must be taken into account and additional options.
     * It's passed to Mage_Catalog_Helper_Product->addParamsToBuyRequest() to compose resulting buyRequest.
     *
     * Basically it can hold
     * - 'current_config', Varien_Object or array - current buyRequest that configures product in this item,
     *   used to restore currently attached files
     * - 'files_prefix': string[a-z0-9_] - prefix that was added at frontend to names of file options (file inputs), so they won't
     *   intersect with other submitted options
     *
     * For more options see Mage_Catalog_Helper_Product->addParamsToBuyRequest()
     *
     * @param int|Mage_Wishlist_Model_Item $itemId
     * @param Varien_Object $buyRequest
     * @param null|array|Varien_Object $params
     * @return $this
     *
     * @see Mage_Catalog_Helper_Product::addParamsToBuyRequest()
     */
    public function updateItem($itemId, $buyRequest, $params = null)
    {
        $item = $itemId instanceof Mage_Wishlist_Model_Item ? $itemId : $this->getItem((int) $itemId);
        if (!$item) {
            Mage::throwException(Mage::helper('wishlist')->__('Cannot specify wishlist item.'));
        }

        /*
        //We need to create new clear product instance with same $productId
        //to set new option values from $buyRequest
        $product = Mage::getModel('catalog/product')
            ->setStoreId($item->getStoreId())
            ->load($item->getProduct()->getId());
        //*/

        $product = $item->getProduct();
        if (!$product->getId()) {
            Mage::throwException(Mage::helper('checkout')->__('The product does not exist.'));
        }

        if (is_array($params)) {
            $params = new Varien_Object($params);
        } elseif (!$params instanceof Varien_Object) {
            $params = new Varien_Object();
        }

        $params->setCurrentConfig($item->getBuyRequest());
        $buyRequest = Mage::helper('catalog/product')->addParamsToBuyRequest($buyRequest, $params);

        $product->setWishlistStoreId($item->getStoreId());

        /*
          $isForceSetQuantity = array_any(
          $this->getItemCollection(),
          fn ($wishlistItem) => $wishlistItem->getProductId() == $product->getId()
          && $wishlistItem->representProduct($product)
          && $wishlistItem->getId() != $item->getId(),
          );
        */

        $isForceSetQuantity = true;
        $wishlistItems = $this->getItemCollection();
        /** @var Mage_Wishlist_Model_Item $wishlistItem */
        foreach ($wishlistItems as $wishlistItem) {
            if ($wishlistItem->getProductId() == $product->getId()
                && $wishlistItem->representProduct($product)
                && $wishlistItem->getId() != $item->getId()
            ) {
                // We do not add new wishlist item, but updating the existing one
                $isForceSetQuantity = false;
            }
        }

        $resultItem = $this->addNewItem($product, $buyRequest, $isForceSetQuantity);

        // Error message
        if (is_string($resultItem)) {
            Mage::throwException(Mage::helper('checkout')->__($resultItem));
        }

        if ($resultItem->getParentItem()) {
            //$resultItem = $resultItem->getParentItem();
        }
        //Mage::log('FFFFFFFFFF ' . $resultItem->getId().' != '.$item->getId());

        if ($resultItem->getId() != $item->getId()) {
            //Mage::log('delete');

            if ($resultItem->getDescription() != $item->getDescription()) {
                $resultItem->setDescription($item->getDescription())->save();
            }
            $item->isDeleted(true);
            $this->setDataChanges(true);
        } else {
            $resultItem->setQty($buyRequest->getQty());
            $resultItem->setOrigData('qty', 0);

        }
        //Mage::log($resultItem->debug());

        return $this;
    }

    #[\Override]
    public function save()
    {
        $this->_hasDataChanges = true;
        return parent::save();
    }
}
