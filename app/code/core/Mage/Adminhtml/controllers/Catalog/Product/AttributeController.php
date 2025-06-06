<?php

/**
 * Maho
 *
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2021-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Adminhtml_Catalog_Product_AttributeController extends Mage_Adminhtml_Controller_Action
{
    protected $_entityTypeId;

    /**
     * ACL resource
     * @see Mage_Adminhtml_Controller_Action::_isAllowed()
     */
    public const ADMIN_RESOURCE = 'catalog/attributes/attributes';

    /**
         * List of tags from setting
         */
    public const XML_PATH_ALLOWED_TAGS = 'system/catalog/frontend/allowed_html_tags_list';

    /**
     * Get list of allowed text formatted as array
     *
     * @return array
     */
    protected function _getAllowedTags()
    {
        return explode(',', Mage::getStoreConfig(self::XML_PATH_ALLOWED_TAGS));
    }

    #[\Override]
    public function preDispatch()
    {
        $this->_setForcedFormKeyActions('delete');
        parent::preDispatch();
        $this->_entityTypeId = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        return $this;
    }

    protected function _initAction()
    {
        $this->_title($this->__('Catalog'))
             ->_title($this->__('Attributes'))
             ->_title($this->__('Manage Attributes'));

        if ($this->getRequest()->getParam('popup')) {
            $this->loadLayout('popup');
        } else {
            $this->loadLayout()
                ->_setActiveMenu('catalog/attributes/attributes')
                ->_addBreadcrumb(Mage::helper('catalog')->__('Catalog'), Mage::helper('catalog')->__('Catalog'))
                ->_addBreadcrumb(
                    Mage::helper('catalog')->__('Manage Product Attributes'),
                    Mage::helper('catalog')->__('Manage Product Attributes'),
                )
            ;
        }
        return $this;
    }

    public function indexAction()
    {
        $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('adminhtml/catalog_product_attribute'))
            ->renderLayout();
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function editAction()
    {
        $id = $this->getRequest()->getParam('attribute_id');
        $model = Mage::getModel('catalog/resource_eav_attribute')
            ->setEntityTypeId($this->_entityTypeId);
        if ($id) {
            $model->load($id);

            if (!$model->getId()) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('catalog')->__('This attribute no longer exists'),
                );
                $this->_redirect('*/*/');
                return;
            }

            // entity type check
            if ($model->getEntityTypeId() != $this->_entityTypeId) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('catalog')->__('This attribute cannot be edited.'),
                );
                $this->_redirect('*/*/');
                return;
            }
        }

        // set entered data if was error when we do save
        $data = Mage::getSingleton('adminhtml/session')->getAttributeData(true);
        if (!empty($data)) {
            $model->addData($data);
        }

        Mage::register('entity_attribute', $model);

        $this->_initAction();

        $this->_title($id ? $model->getName() : $this->__('New Attribute'));

        $item = $id ? Mage::helper('catalog')->__('Edit Product Attribute')
                    : Mage::helper('catalog')->__('New Product Attribute');

        $this->_addBreadcrumb($item, $item);

        $this->getLayout()->getBlock('attribute_edit_js')
            ->setIsPopup((bool) $this->getRequest()->getParam('popup'));

        $this->renderLayout();
    }

    public function validateAction()
    {
        $response = new Varien_Object();
        $response->setError(false);

        $attributeCode  = $this->getRequest()->getParam('attribute_code');
        $attributeId    = $this->getRequest()->getParam('attribute_id');
        $attribute = Mage::getModel('catalog/resource_eav_attribute')
            ->loadByCode($this->_entityTypeId, $attributeCode);

        if ($attribute->getId() && !$attributeId) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('catalog')->__('Attribute with the same code already exists'),
            );
            $this->_initLayoutMessages('adminhtml/session');
            $response->setError(true);
            $response->setMessage($this->getLayout()->getMessagesBlock()->getGroupedHtml());
        }

        $this->getResponse()->setBody($response->toJson());
    }

    /**
     * Filter post data
     *
     * @param array $data
     * @return array
     */
    protected function _filterPostData($data)
    {
        if ($data) {
            /** @var Mage_Catalog_Helper_Data $helperCatalog */
            $helperCatalog = Mage::helper('catalog');
            //labels
            $data['frontend_label'] = (array) $data['frontend_label'];
            foreach ($data['frontend_label'] as & $value) {
                if ($value) {
                    $value = $helperCatalog->stripTags($value);
                }
            }

            if (!empty($data['option']) && !empty($data['option']['value']) && is_array($data['option']['value'])) {
                $allowableTags = isset($data['is_html_allowed_on_front']) && $data['is_html_allowed_on_front']
                    ? sprintf('<%s>', implode('><', $this->_getAllowedTags())) : null;
                foreach ($data['option']['value'] as $key => $values) {
                    foreach ($values as $storeId => $storeLabel) {
                        $data['option']['value'][$key][$storeId]
                            = $helperCatalog->stripTags($storeLabel, $allowableTags);
                    }
                }
            }
        }
        return $data;
    }

    public function saveAction()
    {
        $data = $this->getRequest()->getPost();
        if ($data) {
            /** @var Mage_Admin_Model_Session $session */
            $session = Mage::getSingleton('adminhtml/session');

            $redirectBack   = $this->getRequest()->getParam('back', false);
            /** @var Mage_Catalog_Model_Entity_Attribute $model */
            $model = Mage::getModel('catalog/resource_eav_attribute');
            /** @var Mage_Catalog_Helper_Product $helper */
            $helper = Mage::helper('catalog/product');

            $id = $this->getRequest()->getParam('attribute_id');

            //validate attribute_code
            if (isset($data['attribute_code'])) {
                $validatorAttrCode = new Zend_Validate_Regex(['pattern' => '/^(?!event$)[a-z][a-z_0-9]{1,254}$/']);
                if (!$validatorAttrCode->isValid($data['attribute_code'])) {
                    $session->addError(
                        Mage::helper('catalog')->__('Attribute code is invalid. Please use only letters (a-z), numbers (0-9) or underscore(_) in this field, first character should be a letter. Do not use "event" for an attribute code.'),
                    );
                    $this->_redirect('*/*/edit', ['attribute_id' => $id, '_current' => true]);
                    return;
                }
            }

            //validate frontend_input
            if (isset($data['frontend_input'])) {
                /** @var Mage_Eav_Model_Adminhtml_System_Config_Source_Inputtype_Validator $validatorInputType */
                $validatorInputType = Mage::getModel('eav/adminhtml_system_config_source_inputtype_validator');
                if (!$validatorInputType->isValid($data['frontend_input'])) {
                    foreach ($validatorInputType->getMessages() as $message) {
                        $session->addError($message);
                    }
                    $this->_redirect('*/*/edit', ['attribute_id' => $id, '_current' => true]);
                    return;
                }
            }

            if ($id) {
                $model->load($id);

                if (!$model->getId()) {
                    $session->addError(
                        Mage::helper('catalog')->__('This Attribute no longer exists'),
                    );
                    $this->_redirect('*/*/');
                    return;
                }

                // entity type check
                if ($model->getEntityTypeId() != $this->_entityTypeId) {
                    $session->addError(
                        Mage::helper('catalog')->__('This attribute cannot be updated.'),
                    );
                    $session->setAttributeData($data);
                    $this->_redirect('*/*/');
                    return;
                }

                $data['backend_model'] = $model->getBackendModel();
                $data['attribute_code'] = $model->getAttributeCode();
                $data['is_user_defined'] = $model->getIsUserDefined();
                $data['frontend_input'] = $model->getFrontendInput();
            } else {
                /**
                * @todo add to helper and specify all relations for properties
                */
                $data['source_model'] = $helper->getAttributeSourceModelByInputType($data['frontend_input']);
                $data['backend_model'] = $helper->getAttributeBackendModelByInputType($data['frontend_input']);
            }

            if (!isset($data['is_configurable'])) {
                $data['is_configurable'] = 0;
            }
            if (!isset($data['is_filterable'])) {
                $data['is_filterable'] = 0;
            }
            if (!isset($data['is_filterable_in_search'])) {
                $data['is_filterable_in_search'] = 0;
            }

            if (!$model->getBackendType() && (is_null($model->getIsUserDefined()) || $model->getIsUserDefined() != 0)) {
                $data['backend_type'] = $model->getBackendTypeByInput($data['frontend_input']);
            }

            $defaultValueField = $model->getDefaultValueByInput($data['frontend_input']);
            if ($defaultValueField) {
                $data['default_value'] = $this->getRequest()->getParam($defaultValueField);
            }

            if (!isset($data['apply_to'])) {
                $data['apply_to'] = [];
            }

            if ($model) {
                $data['entity_type_id'] = $model->getEntityTypeId();
            }

            //filter
            $data = $this->_filterPostData($data);
            $model->addData($data);

            if (!$id) {
                $data['entity_type_id'] = $this->_entityTypeId;
                $model->setEntityTypeId($this->_entityTypeId);
                $model->setIsUserDefined(1);
            }

            if ($this->getRequest()->getParam('set') && $this->getRequest()->getParam('group')) {
                // For creating product attribute on product page we need specify attribute set and group
                $model->setAttributeSetId($this->getRequest()->getParam('set'));
                $model->setAttributeGroupId($this->getRequest()->getParam('group'));
            }

            try {
                $model->save();
                $session->addSuccess(
                    Mage::helper('catalog')->__('The product attribute has been saved.'),
                );

                /**
                 * Clear translation cache because attribute labels are stored in translation
                 */
                Mage::app()->cleanCache([Mage_Core_Model_Translate::CACHE_TAG]);
                $session->setAttributeData(false);
                if ($this->getRequest()->getParam('popup')) {
                    $this->_redirect('adminhtml/catalog_product/addAttribute', [
                        'id'       => $this->getRequest()->getParam('product'),
                        'attribute' => $model->getId(),
                        '_current' => true,
                    ]);
                } elseif ($redirectBack) {
                    $this->_redirect('*/*/edit', ['attribute_id' => $model->getId(),'_current' => true]);
                } else {
                    $this->_redirect('*/*/', []);
                }
                return;
            } catch (Exception $e) {
                $session->addError($e->getMessage());
                $session->setAttributeData($data);
                $this->_redirect('*/*/edit', ['attribute_id' => $id, '_current' => true]);
                return;
            }
        }
        $this->_redirect('*/*/');
    }

    public function deleteAction()
    {
        if ($id = $this->getRequest()->getParam('attribute_id')) {
            $model = Mage::getModel('catalog/resource_eav_attribute');

            // entity type check
            $model->load($id);
            if ($model->getEntityTypeId() != $this->_entityTypeId || !$model->getIsUserDefined()) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('catalog')->__('This attribute cannot be deleted.'),
                );
                $this->_redirect('*/*/');
                return;
            }

            try {
                $model->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('catalog')->__('The product attribute has been deleted.'),
                );
                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', ['attribute_id' => $this->getRequest()->getParam('attribute_id')]);
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('catalog')->__('Unable to find an attribute to delete.'),
        );
        $this->_redirect('*/*/');
    }
}
