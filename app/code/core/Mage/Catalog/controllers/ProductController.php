<?php

/**
 * Maho
 *
 * @package    Mage_Catalog
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2025 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Catalog_ProductController extends Mage_Core_Controller_Front_Action
{
    /**
     * Current applied design settings
     *
     * @deprecated after 1.4.2.0-beta1
     * @var array
     */
    protected $_designProductSettingsApplied = [];

    /**
     * Initialize requested product object
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function _initProduct()
    {
        $categoryId = (int) $this->getRequest()->getParam('category', false);
        $productId  = (int) $this->getRequest()->getParam('id');

        $params = new Varien_Object();
        $params->setCategoryId($categoryId);

        return Mage::helper('catalog/product')->initProduct($productId, $this, $params);
    }

    /**
     * Recursively apply custom design settings to product if it's container
     * category custom_use_for_products option is set to 1.
     * If not or product shows not in category - applies product's internal settings
     *
     * @deprecated after 1.4.2.0-beta1, functionality moved to Mage_Catalog_Model_Design
     * @param Mage_Catalog_Model_Category|Mage_Catalog_Model_Product $object
     * @param Mage_Core_Model_Layout_Update $update
     */
    protected function _applyCustomDesignSettings($object, $update)
    {
        if ($object instanceof Mage_Catalog_Model_Category) {
            // lookup the proper category recursively
            if ($object->getCustomUseParentSettings()) {
                $parentCategory = $object->getParentCategory();
                if ($parentCategory && $parentCategory->getId() && $parentCategory->getLevel() > 1) {
                    $this->_applyCustomDesignSettings($parentCategory, $update);
                }
                return;
            }

            // don't apply to the product
            if (!$object->getCustomApplyToProducts()) {
                return;
            }
        }

        if ($this->_designProductSettingsApplied) {
            return;
        }

        $date = $object->getCustomDesignDate();
        if (array_key_exists('from', $date) && array_key_exists('to', $date)
            && Mage::app()->getLocale()->isStoreDateInInterval(null, $date['from'], $date['to'])
        ) {
            if ($object->getPageLayout()) {
                $this->_designProductSettingsApplied['layout'] = $object->getPageLayout();
            }
            $this->_designProductSettingsApplied['update'] = $object->getCustomLayoutUpdate();
        }
    }

    public function viewAction()
    {
        // Get initial data from request
        $categoryId = (int) $this->getRequest()->getParam('category', false);
        $productId  = (int) $this->getRequest()->getParam('id');
        $specifyOptions = $this->getRequest()->getParam('options');

        // Prepare helper and params
        $viewHelper = Mage::helper('catalog/product_view');

        $params = new Varien_Object();
        $params->setCategoryId($categoryId);
        $params->setSpecifyOptions($specifyOptions);

        // Render page
        try {
            $viewHelper->prepareAndRender($productId, $this, $params);
            $this->getResponse()
                ->setHeader('Pragma', 'public', true)
                ->setHeader('Cache-Control', 'private max-age=60', true);
        } catch (Exception $e) {
            if ($e->getCode() == $viewHelper->ERR_NO_PRODUCT_LOADED) {
                if (isset($_GET['store'])  && !$this->getResponse()->isRedirect()) {
                    $this->_redirect('');
                } elseif (!$this->getResponse()->isRedirect()) {
                    $this->_forward('noRoute');
                }
            } elseif (Mage::getIsDeveloperMode()) {
                Mage::printException($e);
            } else {
                Mage::logException($e);
                $this->_forward('noRoute');
            }
        }
    }

    /**
     * View product gallery action
     */
    public function galleryAction()
    {
        if (!$this->_initProduct()) {
            if (isset($_GET['store']) && !$this->getResponse()->isRedirect()) {
                $this->_redirect('');
            } elseif (!$this->getResponse()->isRedirect()) {
                $this->_forward('noRoute');
            }
            return;
        }
        $this->loadLayout();
        $this->renderLayout();
    }
}
