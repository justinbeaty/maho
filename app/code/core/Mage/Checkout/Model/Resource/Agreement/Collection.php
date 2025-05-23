<?php

/**
 * Maho
 *
 * @package    Mage_Checkout
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Checkout_Model_Resource_Agreement_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected $_map = ['fields' => [
        'agreement_id' => 'main_table.agreement_id',
    ]];

    /**
     * Is store filter with admin store
     *
     * @var bool
     */
    protected $_isStoreFilterWithAdmin   = true;

    /**
     * Initialize resource
     *
     */
    #[\Override]
    protected function _construct()
    {
        $this->_init('checkout/agreement');
    }

    /**
     * Filter collection by specified store ids
     *
     * @param int|Mage_Core_Model_Store $store
     * @return $this
     */
    public function addStoreFilter($store)
    {
        // check and prepare data
        if ($store instanceof Mage_Core_Model_Store) {
            $store = [$store->getId()];
        } elseif (is_numeric($store)) {
            $store = [$store];
        }

        $alias = 'store_table_' . implode('_', $store);
        if ($this->getFlag($alias)) {
            return $this;
        }

        $storeFilter = [$store];
        if ($this->_isStoreFilterWithAdmin) {
            $storeFilter[] = 0;
        }

        // add filter
        $this->getSelect()->join(
            [$alias => $this->getTable('checkout/agreement_store')],
            'main_table.agreement_id = ' . $alias . '.agreement_id',
            [],
        )
        ->where($alias . '.store_id IN (?)', $storeFilter)
        ->group('main_table.agreement_id');

        $this->setFlag($alias, true);

        /*
         * Allow Analytic functions usage
         */
        $this->_useAnalyticFunction = true;

        return $this;
    }

    /**
     * Make store filter using admin website or not
     *
     * @param bool $value
     * @return $this
     */
    public function setIsStoreFilterWithAdmin($value)
    {
        $this->_isStoreFilterWithAdmin = (bool) $value;
        return $this;
    }
}
