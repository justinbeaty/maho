<?php

/**
 * Maho
 *
 * @package    Mage_Catalog
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2020-2023 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Catalog_Model_Api2_Product_Website_Validator_Admin_Website extends Mage_Api2_Model_Resource_Validator
{
    /**
     * Validate data for website assignment to product.
     * If fails validation, then this method returns false, and
     * getErrors() will return an array of errors that explain why the
     * validation failed.
     *
     * @return bool
     */
    public function isValidDataForWebsiteAssignmentToProduct(Mage_Catalog_Model_Product $product, array $data)
    {
        // Validate website id
        if (!isset($data['website_id']) || !is_numeric($data['website_id'])) {
            $this->_addError('Invalid value for "website_id" in request.');
            return false;
        }

        // Validate website
        /** @var Mage_Core_Model_Website $website */
        $website = Mage::getModel('core/website')->load($data['website_id']);
        if (!$website->getId()) {
            $this->_addError(sprintf('Website #%d not found.', $data['website_id']));
            return false;
        }

        // Validate product to website association
        if (in_array($website->getId(), $product->getWebsiteIds())) {
            $this->_addError(sprintf(
                'Product #%d is already assigned to website #%d',
                $product->getId(),
                $website->getId(),
            ));
            return false;
        }

        // Validate "Copy To Stores" data and associations
        $this->_addErrorsIfCopyToStoresDataIsNotValid($product, $website, $data);

        return !count($this->getErrors());
    }

    /**
     * Validate "Copy To Stores" data and associations.
     *
     * @param Mage_Catalog_Model_Product $product
     * @param Mage_Core_Model_Website $website
     * @param array $data
     * @return \Mage_Catalog_Model_Api2_Product_Website_Validator_Admin_Website
     */
    protected function _addErrorsIfCopyToStoresDataIsNotValid($product, $website, $data)
    {
        if (isset($data['copy_to_stores'])) {
            foreach ($data['copy_to_stores'] as $storeData) {
                $this->_checkStoreFrom($product, $website, $storeData);
                $this->_checkStoreTo($website, $storeData);
            }
        }
        return $this;
    }

    /**
     * Check if it possible to copy from store "store_from"
     *
     * @param Mage_Catalog_Model_Product $product
     * @param Mage_Core_Model_Website $website
     * @param array $storeData
     * @return \Mage_Catalog_Model_Api2_Product_Website_Validator_Admin_Website
     */
    protected function _checkStoreFrom($product, $website, $storeData)
    {
        if (!isset($storeData['store_from']) || !is_numeric($storeData['store_from'])) {
            $this->_addError(sprintf(
                'Invalid value for "store_from" for the website with ID #%d.',
                $website->getId(),
            ));
            return $this;
        }

        // Check if the store with the specified ID (from which we will copy the information) exists
        // and if it belongs to the product being edited
        $storeFrom = Mage::getModel('core/store')->load($storeData['store_from']);
        if (!$storeFrom->getId()) {
            $this->_addError(sprintf(
                'Store not found #%d for website #%d.',
                $storeData['store_from'],
                $website->getId(),
            ));
            return $this;
        }

        if (!in_array($storeFrom->getId(), $product->getStoreIds())) {
            $this->_addError(sprintf('Store #%d from which we will copy the information does not belong'
                . ' to the product #%d being edited.', $storeFrom->getId(), $product->getId()));
        }

        return $this;
    }

    /**
     * Check if it possible to copy into store "store_to"
     *
     * @param Mage_Core_Model_Website $website
     * @param array $storeData
     * @return \Mage_Catalog_Model_Api2_Product_Website_Validator_Admin_Website
     */
    protected function _checkStoreTo($website, $storeData)
    {
        if (!isset($storeData['store_to']) || !is_numeric($storeData['store_to'])) {
            $this->_addError(sprintf(
                'Invalid value for "store_to" for the website with ID #%d.',
                $website->getId(),
            ));
            return $this;
        }

        // Check if the store with the specified ID (to which we will copy the information) exists
        // and if it belongs to the website being added
        $storeTo = Mage::getModel('core/store')->load($storeData['store_to']);
        if (!$storeTo->getId()) {
            $this->_addError(sprintf(
                'Store not found #%d for website #%d.',
                $storeData['store_to'],
                $website->getId(),
            ));
            return $this;
        }

        if (!in_array($storeTo->getId(), $website->getStoreIds())) {
            $this->_addError(sprintf('Store #%d to which we will copy the information does not belong'
                . ' to the website #%d being added.', $storeTo->getId(), $website->getId()));
        }

        return $this;
    }

    /**
     * Validate is valid association for website unassignment from product.
     * If fails validation, then this method returns false, and
     * getErrors() will return an array of errors that explain why the
     * validation failed.
     *
     * @return bool
     */
    public function isWebsiteAssignedToProduct(Mage_Core_Model_Website $website, Mage_Catalog_Model_Product $product)
    {
        if (!in_array($website->getId(), $product->getWebsiteIds())) {
            $this->_addError(sprintf(
                'Product #%d isn\'t assigned to website #%d',
                $product->getId(),
                $website->getId(),
            ));
        }
        return !count($this->getErrors());
    }
}
