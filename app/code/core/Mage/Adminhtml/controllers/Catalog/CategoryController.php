<?php
/**
 * Maho
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Catalog category controller
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_Catalog_CategoryController extends Mage_Adminhtml_Controller_Action
{
    /**
     * ACL resource
     * @see Mage_Adminhtml_Controller_Action::_isAllowed()
     */
    public const ADMIN_RESOURCE = 'catalog/categories';

    /**
     * Initialize requested category and put it into registry.
     * Root category can be returned, if inappropriate store/category is specified
     *
     * @param bool $getRootInstead
     * @return Mage_Catalog_Model_Category|false
     */
    protected function _initCategory($getRootInstead = false)
    {
        $this->_title($this->__('Catalog'))
             ->_title($this->__('Categories'))
             ->_title($this->__('Manage Categories'));

        $categoryId = (int) $this->getRequest()->getParam('id', false);
        $storeId    = (int) $this->getRequest()->getParam('store');
        $category = Mage::getModel('catalog/category');
        $category->setStoreId($storeId);

        if ($categoryId) {
            $category->load($categoryId);
            if ($storeId) {
                $rootId = Mage::app()->getStore($storeId)->getRootCategoryId();
                if (!in_array($rootId, $category->getPathIds())) {
                    // load root category instead wrong one
                    if ($getRootInstead) {
                        $category->load($rootId);
                    } else {
                        $this->_redirect('*/*/', ['_current' => true, 'id' => null]);
                        return false;
                    }
                }
            }
        }

        if ($activeTabId = (string) $this->getRequest()->getParam('active_tab_id')) {
            Mage::getSingleton('admin/session')->setActiveTabId($activeTabId);
        }

        Mage::register('category', $category);
        Mage::register('current_category', $category);
        Mage::getSingleton('cms/wysiwyg_config')->setStoreId($this->getRequest()->getParam('store'));
        return $category;
    }
    /**
     * Catalog categories index action
     */
    public function indexAction()
    {
        $this->_forward('edit');
    }

    /**
     * Add new category form
     */
    public function addAction()
    {
        Mage::getSingleton('admin/session')->unsActiveTabId();
        $this->_forward('edit');
    }

    /**
     * Edit category page
     */
    public function editAction()
    {
        $params['_current'] = true;
        $redirect = false;

        $storeId = (int) $this->getRequest()->getParam('store');
        $parentId = (int) $this->getRequest()->getParam('parent');
        $prevStoreId = Mage::getSingleton('admin/session')
            ->getLastViewedStore(true);

        if (!empty($prevStoreId) && !$this->getRequest()->getQuery('isAjax')) {
            $params['store'] = $prevStoreId;
            $redirect = true;
        }

        $categoryId = (int) $this->getRequest()->getParam('id');
        $prevCategoryId = Mage::getSingleton('admin/session')
            ->getLastEditedCategory(true);

        if ($prevCategoryId
            && !$this->getRequest()->getQuery('isAjax')
            && !$this->getRequest()->getParam('clear')
        ) {
            $this->getRequest()->setParam('id', $prevCategoryId);
        }

        if ($redirect) {
            $this->_redirect('*/*/edit', $params);
            return;
        }

        if ($storeId && !$categoryId && !$parentId) {
            $store = Mage::app()->getStore($storeId);
            $prevCategoryId = (int) $store->getRootCategoryId();
            $this->getRequest()->setParam('id', $prevCategoryId);
        }

        if (!($category = $this->_initCategory(true))) {
            return;
        }

        $this->loadLayout();
        $this->_title($category->getId() ? $category->getName() : $this->__('New Category'));

        /**
         * Check if we have data in session (if duering category save was exceprion)
         */
        $data = Mage::getSingleton('adminhtml/session')->getCategoryData(true);
        if (isset($data['general'])) {
            $category->addData($data['general']);
        }

        /**
         * Build response for ajax request
         */
        if ($this->getRequest()->getQuery('isAjax')) {
            $eventResponse = new Varien_Object([
                'content' => $this->getLayout()->getBlock('category.edit')->getFormHtml(),
                'messages' => $this->getLayout()->getMessagesBlock()->getGroupedHtml(),
            ]);

            Mage::dispatchEvent('category_prepare_ajax_response', [
                'response' => $eventResponse,
                'controller' => $this
            ]);

            $this->_prepareDataJSON($eventResponse->getData());
            return;
        }

        $this->_setActiveMenu('catalog/categories');

        $this->_addBreadcrumb(
            Mage::helper('catalog')->__('Manage Catalog Categories'),
            Mage::helper('catalog')->__('Manage Categories')
        );

        $block = $this->getLayout()->getBlock('catalog.wysiwyg.js');
        if ($block) {
            $block->setStoreId($storeId);
        }

        $this->renderLayout();
    }

    /**
     * WYSIWYG editor action for ajax request
     */
    public function wysiwygAction()
    {
        $elementId = $this->getRequest()->getParam('element_id', md5(microtime()));
        $storeId = $this->getRequest()->getParam('store_id', 0);
        $storeMediaUrl = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);

        $content = $this->getLayout()->createBlock('adminhtml/catalog_helper_form_wysiwyg_content', '', [
            'editor_element_id' => $elementId,
            'store_id'          => $storeId,
            'store_media_url'   => $storeMediaUrl,
        ]);

        $this->getResponse()->setBody($content->toHtml());
    }

    /**
     * Get tree node (Ajax version)
     */
    public function categoriesJsonAction()
    {
        $recursionLevel = $this->getRequest()->getParam('expand_all') ? 0 : null;

        if ($categoryId = (int) $this->getRequest()->getPost('id')) {
            $this->getRequest()->setParam('id', $categoryId);

            if (!$category = $this->_initCategory()) {
                return;
            }

            $this->getResponse()->setHeader('Content-type', 'application/json', true);
            $this->getResponse()->setBody(
                $this->getLayout()->createBlock('adminhtml/catalog_category_tree')
                    ->setRecursionLevel($recursionLevel)
                    ->getTreeJson($category)
            );
        }
    }

    /**
     * Category save
     */
    public function saveAction()
    {
        if (!$category = $this->_initCategory()) {
            return;
        }

        $storeId = $this->getRequest()->getParam('store');

        if ($data = $this->getRequest()->getPost()) {
            if (isset($data['general']['path'])) {
                unset($data['general']['path']);
            }
            $category->addData($data['general']);
            if (!$category->getId()) {
                $parentId = $this->getRequest()->getParam('parent');
                if (!$parentId) {
                    if ($storeId) {
                        $parentId = Mage::app()->getStore($storeId)->getRootCategoryId();
                    } else {
                        $parentId = Mage_Catalog_Model_Category::TREE_ROOT_ID;
                    }
                }
                $parentCategory = Mage::getModel('catalog/category')->load($parentId);
                $category->setPath($parentCategory->getPath());
            }

            /**
             * Check "Use Default Value" checkboxes values
             */
            if ($useDefaults = $this->getRequest()->getPost('use_default')) {
                foreach ($useDefaults as $attributeCode) {
                    $category->setData($attributeCode, false);
                }
            }

            /**
             * Process "Use Config Settings" checkboxes
             */
            if ($useConfig = $this->getRequest()->getPost('use_config')) {
                foreach ($useConfig as $attributeCode) {
                    $category->setData($attributeCode, null);
                }
            }

            /**
             * Create Permanent Redirect for old URL key
             */
            if ($category->getId() && isset($data['general']['url_key_create_redirect'])) {
                // && $category->getOrigData('url_key') != $category->getData('url_key')
                $category->setData('save_rewrites_history', (bool)$data['general']['url_key_create_redirect']);
            }

            $category->setAttributeSetId($category->getDefaultAttributeSetId());

            if (isset($data['category_products']) &&
                !$category->getProductsReadonly()
            ) {
                $products = Mage::helper('core/string')->parseQueryStr($data['category_products']);
                $category->setPostedProducts($products);
            }

            Mage::dispatchEvent('catalog_category_prepare_save', [
                'category' => $category,
                'request' => $this->getRequest()
            ]);

            /**
             * Proceed with $_POST['use_config']
             * set into category model for processing through validation
             */
            $category->setData('use_post_data_config', $this->getRequest()->getPost('use_config'));

            try {

                $validate = $category->validate();
                if ($validate !== true) {
                    foreach ($validate as $code => $error) {
                        if ($error === true) {
                            $attributeLabel = $category->getResource()->getAttribute($code)->getFrontend()->getLabel();
                            Mage::throwException(Mage::helper('catalog')->__('Attribute "%s" is required.', $attributeLabel));
                        } else {
                            Mage::throwException($error);
                        }
                    }
                }

                /**
                 * Unset $_POST['use_config'] before save
                 */
                $category->unsetData('use_post_data_config');
                $category->save();

                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('catalog')->__('The category has been saved.'));
                $this->_prepareDataJSON([
                    'success' => true,
                    'category_id' => $category->getId(),
                ]);

            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->setCategoryData($data);
                $this->_prepareDataJSON([
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Move category action
     */
    public function moveAction()
    {
        $category = $this->_initCategory();
        if (!$category) {
            $this->_prepareDataJSON([
                'error' => Mage::helper('catalog')->__('Category move error'),
            ]);
            return;
        }

        // New parent category identifier
        $parentNodeId   = $this->getRequest()->getPost('pid', false);

        // Category id after which we have put our category
        $prevNodeId     = $this->getRequest()->getPost('aid', false);

        $category->setData('save_rewrites_history', Mage::helper('catalog')->shouldSaveUrlRewritesHistory());
        try {
            $category->move($parentNodeId, $prevNodeId);
            $this->_prepareDataJSON([
                'success' => true,
            ]);
        } catch (Mage_Core_Exception $e) {
            $this->_prepareDataJSON([
                'error' => $e->getMessage(),
            ]);
        } catch (Exception $e) {
            $this->_prepareDataJSON([
                'error' => Mage::helper('catalog')->__('Category move error %s', $e),
            ]);
            Mage::logException($e);
        }
    }

    /**
     * Delete category action
     */
    public function deleteAction()
    {
        if ($id = (int) $this->getRequest()->getParam('id')) {
            try {
                $category = Mage::getModel('catalog/category')->load($id);
                Mage::dispatchEvent('catalog_controller_category_delete', ['category' => $category]);

                $category->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('catalog')->__('The category has been deleted.')
                );

                $this->_prepareDataJSON([
                    'success' => true,
                    'category_id' => $category->getId(),
                    'parent_id' => $category->getParentId(),
                ]);
                return;

            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->getResponse()->setRedirect($this->getUrl('*/*/edit', ['_current' => true]));
                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('catalog')->__('An error occurred while trying to delete the category.')
                );
                $this->getResponse()->setRedirect($this->getUrl('*/*/edit', ['_current' => true]));
                return;
            }
        }
        $this->getResponse()->setRedirect($this->getUrl('*/*/', ['_current' => true, 'id' => null]));
    }

    /**
     * Grid Action
     * Display list of products related to current category
     */
    public function gridAction()
    {
        if (!$category = $this->_initCategory(true)) {
            return;
        }
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('adminhtml/catalog_category_tab_product', 'category.product.grid')
                ->toHtml()
        );
    }

    /**
     * Tree Action
     * Retrieve category tree
     */
    public function treeAction()
    {
        $storeId = (int) $this->getRequest()->getParam('store');
        $categoryId = (int) $this->getRequest()->getParam('id');
        $recursionLevel = $this->getRequest()->getParam('expand_all') ? 0 : null;

        if ($storeId) {
            if (!$categoryId) {
                $store = Mage::app()->getStore($storeId);
                $rootId = $store->getRootCategoryId();
                $this->getRequest()->setParam('id', $rootId);
            }
        }

        $category = $this->_initCategory(true);

        /** @var Mage_Adminhtml_Block_Catalog_Category_Tree $block */
        $block = $this->getLayout()->createBlock('adminhtml/catalog_category_tree');
        $block->setRecursionLevel($recursionLevel);

        $root = $block->getRoot();
        $this->_prepareDataJSON([
            'data' => $block->getTree(),
            'parameters' => [
                'text'        => $block->buildNodeName($root),
                'draggable'   => false,
                'allowDrop'   => ($root->getIsVisible()) ? true : false,
                'id'          => (int) $root->getId(),
                'store_id'    => (int) $block->getStore()->getId(),
                'category_id' => (int) $category->getId(),
                'root_visible' => (int) $root->getIsVisible(),
                'can_add_root' => (int) $block->canAddRootCategory(),
                'expanded'    => $recursionLevel === 0,
            ]]);
    }

    /**
    * Build response for refresh input element 'path' in form
    */
    public function refreshPathAction()
    {
        if ($id = (int) $this->getRequest()->getParam('id')) {
            $category = Mage::getModel('catalog/category')->load($id);
            $this->_prepareDataJSON([
                'id' => $id,
                'path' => $category->getPath(),
            ]);
        }
    }

    /**
     * Controller pre-dispatch method
     *
     * @return Mage_Adminhtml_Controller_Action
     */
    #[\Override]
    public function preDispatch()
    {
        $this->_setForcedFormKeyActions('delete');
        return parent::preDispatch();
    }

    /**
     * Prepare JSON formatted data for response to client
     *
     * @param mixed $response
     * @return Zend_Controller_Response_Abstract
     */
    protected function _prepareDataJSON($response)
    {
        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
    }
}
