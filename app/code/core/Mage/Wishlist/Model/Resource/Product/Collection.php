<?php

/**
 * Maho
 *
 * @package    Mage_Wishlist
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024-2025 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Wishlist Product collection
 * Deprecated because after Magento 1.4.2.0 it's impossible
 * to use product collection in wishlist
 *
 * @package    Mage_Wishlist
 * @deprecated after 1.4.2.0
 */
class Mage_Wishlist_Model_Resource_Product_Collection extends Mage_Catalog_Model_Resource_Product_Collection
{
    /**
     * Add days in wishlist filter of product collection
     *
     * @var bool
     */
    protected $_addDaysInWishlist  = false;

    /**
     * Wishlist item table alias
     * @var string
     */
    protected $_wishlistItemTableAlias         = 't_wi';

    /**
     * Get add days in wishlist filter of product collection flag
     *
     * @return bool
     */
    public function getDaysInWishlist()
    {
        return $this->_addDaysInWishlist;
    }

    /**
     * Set add days in wishlist filter of product collection flag
     *
     * @param bool $flag
     * @return $this
     */
    public function setDaysInWishlist($flag)
    {
        $this->_addDaysInWishlist = (bool) $flag;
        return $this;
    }

    /**
     * Add wishlist filter to collection
     *
     * @return $this
     */
    public function addWishlistFilter(Mage_Wishlist_Model_Wishlist $wishlist)
    {
        $this->joinTable(
            [$this->_wishlistItemTableAlias => 'wishlist/item'],
            'product_id=entity_id',
            [
                'product_id'                => 'product_id',
                'wishlist_item_description' => 'description',
                'item_store_id'             => 'store_id',
                'added_at'                  => 'added_at',
                'wishlist_id'               => 'wishlist_id',
                'wishlist_item_id'          => 'wishlist_item_id',
            ],
            [
                'wishlist_id'               => $wishlist->getId(),
                'store_id'                  => ['in' => $wishlist->getSharedStoreIds()],
            ],
        );

        $this->_productLimitationFilters['store_table']  = $this->_wishlistItemTableAlias;

        $this->setFlag('url_data_object', true);
        $this->setFlag('do_not_use_category_id', true);

        return $this;
    }

    /**
     * Add wishlist sort order
     *
     * @param string $attribute
     * @param string $dir
     * @return $this
     */
    public function addWishListSortOrder($attribute = 'added_at', $dir = 'desc')
    {
        $this->setOrder($attribute, $dir);
        return $this;
    }

    /**
     * Reset sort order
     *
     * @return $this
     */
    public function resetSortOrder()
    {
        $this->getSelect()->reset(Zend_Db_Select::ORDER);
        return $this;
    }

    /**
     * Add store data
     *
     * @return $this
     */
    public function addStoreData()
    {
        return $this;
    }

    /**
     * Calculate days in wishlist
     *
     * @return $this
     */
    protected function calculateDaysInWishlist(): self
    {
        if ($this->_addDaysInWishlist !== true) {
            return $this;
        }

        foreach ($this->getItems() as $item) {
            $addedAt = new DateTimeImmutable($item->getAddedAt());
            $now = new DateTimeImmutable('now');
            $item->setDaysInWishlist($now->diff($addedAt)->format('%a'));
        }

        return $this;
    }

    /**
     * Rewrite retrieve attribute field name for wishlist attributes
     */
    #[\Override]
    protected function _getAttributeFieldName($attributeCode)
    {
        if ($attributeCode === 'days_in_wishlist') {
            return $this->_joinFields[$attributeCode]['field'];
        }
        return parent::_getAttributeFieldName($attributeCode);
    }

    /**
     * Prevent loading collection because after Magento 1.4.2.0 it's impossible
     * to use product collection in wishlist
     *
     * @param bool $printQuery
     * @param bool $logQuery
     * @return Mage_Wishlist_Model_Resource_Product_Collection
     */
    #[\Override]
    public function load($printQuery = false, $logQuery = false)
    {
        return $this;
    }

    /**
     * After load processing
     *
     * @return $this
     */
    #[\Override]
    protected function _afterLoad()
    {
        parent::_afterLoad();
        $this->calculateDaysInWishlist();
        return $this;
    }
}
