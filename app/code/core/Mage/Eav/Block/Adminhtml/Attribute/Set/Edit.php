<?php

/**
 * Maho
 *
 * @package    Mage_Eav
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml Attribute Set Main Block
 *
 * @package     Mage_Eav
 */
class Mage_Eav_Block_Adminhtml_Attribute_Set_Edit extends Mage_Adminhtml_Block_Template
{
    protected Mage_Eav_Model_Entity_Type $entityType;
    protected Mage_Eav_Model_Entity_Attribute_Set $attributeSet;

    public function __construct()
    {
        // TODO jstranslator.xml controller
        Mage::helper('core/js')->addTranslateData([
            'All products of this set will be deleted! Type "confirm" to proceed.',
            'Cannot delete group. Please move configurable attributes to another group and try again.',
            'Cannot unassign configurable attribute',
        ], 'catalog');

        $this->entityType = Mage::registry('entity_type');
        $this->attributeSet = Mage::registry('current_attribute_set');
        $this->setTemplate('eav/attribute/set/edit.phtml');
        $this->setIsReadOnly(false);
        parent::__construct();
    }

    /**
     * Prepare Global Layout
     *
     * @return $this
     */
    #[\Override]
    protected function _prepareLayout()
    {
        $this->setChild(
            'delete_group_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')->setData([
                'id'        => 'delete-group-button',
                'label'     => Mage::helper('catalog')->__('Delete'),
                'class'     => 'delete',
                'disabled'  => true,
            ]),
        );

        $this->setChild(
            'rename_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')->setData([
                'id'        => 'rename-group-button',
                'label'     => Mage::helper('catalog')->__('Rename'),
                'disabled'  => true,
            ]),
        );

        $this->setChild(
            'add_group_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')->setData([
                'id'        => 'add-group-button',
                'label'     => Mage::helper('catalog')->__('Add'),
                'class'     => 'add',
            ]),
        );

        $this->setChild(
            'back_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')->setData([
                'label'     => Mage::helper('catalog')->__('Back'),
                'onclick'   => Mage::helper('core/js')->getSetLocationJs($this->getUrl('*/*/')),
                'class'     => 'back',
            ]),
        );

        $this->setChild(
            'reset_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')->setData([
                'label'     => Mage::helper('catalog')->__('Reset'),
                'onclick'   => 'window.location.reload()',
            ]),
        );

        $this->setChild(
            'save_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')->setData([
                'id'        => 'save-button',
                'label'     => Mage::helper('catalog')->__('Save Attribute Set'),
                'class'     => 'save',
            ]),
        );

        $this->setChild(
            'delete_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')->setData([
                'id'        => 'delete-button',
                'label'     => Mage::helper('catalog')->__('Delete Attribute Set'),
                'class'     => 'delete',
            ]),
        );

        return parent::_prepareLayout();
    }

    /**
     * Retrieve Attribute Set Group Tree HTML
     *
     * @return string
     * @deprecated
     */
    public function getGroupTreeHtml()
    {
        return '';
    }

    /**
     * Retrieve Attribute Set Edit Form HTML
     *
     * @return string
     */
    public function getSetFormHtml()
    {
        if ($this->getIsReadOnly()) {
            $this->getChild('set_form')->setIsReadOnly(true);
        }
        return $this->getChildHtml('set_form');
    }

    /**
     * Retrieve Block Header Text
     *
     * @return string
     */
    protected function _getHeader()
    {
        return Mage::helper('eav')->__(
            "Manage %s Attribute Set '%s'",
            Mage::helper('eav')->formatTypeCode($this->entityType->getEntityTypeCode()),
            $this->_getAttributeSet()->getAttributeSetName(),
        );
    }

    /**
     * Retrieve Attribute Set Save URL
     *
     * @return string
     * @deprecated use self::getSaveUrl()
     */
    public function getMoveUrl()
    {
        return $this->getSaveUrl();
    }

    /**
     * Retrieve Attribute Set Save URL
     */
    public function getSaveUrl(): string
    {
        return $this->getUrl('*/*/save', ['id' => $this->_getSetId()]);
    }

    /**
     * Retrieve Attribute Set Delete URL
     */
    public function getDeleteUrl(): string
    {
        return $this->getUrl('*/*/delete', ['id' => $this->_getSetId()]);
    }

    /**
     * Load all attributes including info about all sets they belong to
     */
    protected function getEntityAttributeCollection(): Mage_Eav_Model_Resource_Entity_Attribute_Collection
    {
        // Get global/eav_attributes/$entityType/$attributeCode/hidden config.xml nodes
        $hiddenAttributes = Mage::helper('eav')->getHiddenAttributes($this->entityType->getEntityTypeCode());

        /** @var Mage_Eav_Model_Resource_Entity_Attribute_Collection $collection */
        $collection = Mage::getResourceModel($this->entityType->getEntityAttributeCollection());
        $collection->setEntityTypeFilter($this->entityType->getEntityTypeId())
            ->setOrder('attribute_code', 'asc')
            ->setNotCodeFilter($hiddenAttributes)
            ->addVisibleFilter()
            ->addSetInfo(true)
            ->load();

        return $collection;
    }

    /**
     * Retrieve Attribute Set Group Tree
     */
    protected function getGroupTree(): array
    {
        $items = [];
        $setId = $this->_getSetId();

        /** @var Mage_Eav_Model_Resource_Entity_Attribute_Group_Collection $groups */
        $groups = Mage::getModel('eav/entity_attribute_group')
            ->getResourceCollection()
            ->setAttributeSetFilter($setId)
            ->setSortOrder()
            ->load();

        /** @var Mage_Eav_Model_Entity_Attribute_Group $group */
        foreach ($groups as $group) {
            $item = [
                'text'      => $group->getAttributeGroupName(),
                'id'        => $group->getAttributeGroupId(),
                'type'      => 'folder',
                'allowDrop' => true,
                'allowDrag' => true,
                'children'  => [],
            ];

            /** @var Mage_Eav_Model_Entity_Attribute $attribute */
            foreach ($this->getEntityAttributeCollection()->getItems() as $attribute) {
                $attributeGroupId = $attribute['attribute_set_info'][$setId]['group_id'] ?? null;
                if ($attributeGroupId !== $group->getId()) {
                    continue;
                }

                $isUserDefined  = (bool) $attribute->getIsUserDefined();
                $icon = $isUserDefined ? 'leaf' : 'system-leaf';

                $item['children'][] = [
                    'text'            => $attribute->getAttributeCode(),
                    'id'              => $attribute->getAttributeId(),
                    'cls'             => $icon,
                    'allowDrop'       => false,
                    'allowDrag'       => true,
                    'selectable'      => false,
                    'is_user_defined' => $isUserDefined,
                    'entity_id'       => $attribute->getEntityAttributeId(),
                    'sort'            => $attribute['attribute_set_info'][$setId]['sort'],
                ];
            }

            // Sort attributes by sort key
            array_multisort(array_column($item['children'], 'sort'), $item['children']);

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Retrieve Unused in Attribute Set Attribute Tree
     */
    protected function getAttributeTree(): array
    {
        $items = [];
        $setId = $this->_getSetId();

        /** @var Mage_Eav_Model_Entity_Attribute $attribute */
        foreach ($this->getEntityAttributeCollection()->getItems() as $sort => $attribute) {
            if (!isset($attribute['attribute_set_info'][$setId])) {
                $items[] = [
                    'text'            => $attribute->getAttributeCode(),
                    'id'              => $attribute->getAttributeId(),
                    'cls'             => 'leaf',
                    'allowDrop'       => false,
                    'allowDrag'       => true,
                    'leaf'            => true,
                    'is_user_defined' => $attribute->getIsUserDefined(),
                    'entity_id'       => $attribute->getEntityId(),
                    'sort'            => $sort,
                ];
            }
        }

        if (count($items) === 0) {
            $items[] = [
                'text'                => Mage::helper('eav')->__('Empty'),
                'id'                  => 'empty',
                'cls'                 => 'folder',
                'allowDrop'           => false,
                'allowDrag'           => false,
            ];
        }

        return $items;
    }

    /**
     * Retrieve Attribute Set Group Tree as JSON format
     *
     * @return string
     */
    public function getGroupTreeJson()
    {
        return Mage::helper('core')->jsonEncode($this->getGroupTree());
    }

    /**
     * Retrieve Unused in Attribute Set Attribute Tree as JSON
     *
     * @return string
     */
    public function getAttributeTreeJson()
    {
        return Mage::helper('core')->jsonEncode($this->getAttributeTree());
    }

    /**
     * Retrieve Back Button HTML
     *
     * @return string
     */
    public function getBackButtonHtml()
    {
        return $this->getChildHtml('back_button');
    }

    /**
     * Retrieve Reset Button HTML
     *
     * @return string
     */
    public function getResetButtonHtml()
    {
        if ($this->getIsReadOnly()) {
            return '';
        }
        return $this->getChildHtml('reset_button');
    }

    /**
     * Retrieve Save Button HTML
     *
     * @return string
     */
    public function getSaveButtonHtml()
    {
        if ($this->getIsReadOnly()) {
            return '';
        }
        return $this->getChildHtml('save_button');
    }

    /**
     * Retrieve Delete Button HTML
     *
     * @return string
     */
    public function getDeleteButtonHtml()
    {
        if ($this->getIsCurrentSetDefault() || $this->getIsReadOnly()) {
            return '';
        }
        return $this->getChildHtml('delete_button');
    }

    /**
     * Retrieve Delete Group Button HTML
     *
     * @return string
     */
    public function getDeleteGroupButton()
    {
        return $this->getChildHtml('delete_group_button');
    }

    /**
     * Retrieve Add New Group Button HTML
     *
     * @return string
     */
    public function getAddGroupButton()
    {
        return $this->getChildHtml('add_group_button');
    }

    /**
     * Retrieve Rename Button HTML
     *
     * @return string
     */
    public function getRenameButton()
    {
        return $this->getChildHtml('rename_button');
    }

    /**
     * Retrieve current Attribute Set object
     *
     * @return Mage_Eav_Model_Entity_Attribute_Set
     */
    protected function _getAttributeSet()
    {
        return $this->attributeSet;
    }

    /**
     * Retrieve current attribute set Id
     *
     * @return int
     */
    protected function _getSetId()
    {
        return $this->_getAttributeSet()->getId();
    }

    /**
     * Check Current Attribute Set is a default
     *
     * @return bool
     */
    public function getIsCurrentSetDefault()
    {
        $isDefault = $this->getData('is_current_set_default');
        if (is_null($isDefault)) {
            $defaultSetId = $this->entityType->getDefaultAttributeSetId();
            $isDefault = $this->_getSetId() == $defaultSetId;
            $this->setData('is_current_set_default', $isDefault);
        }
        return $isDefault;
    }

    #[\Override]
    protected function _toHtml()
    {
        $entityTypeCode = $this->entityType->getEntityTypeCode();
        Mage::dispatchEvent("adminhtml_{$entityTypeCode}_attribute_set_main_html_before", ['block' => $this]);
        return parent::_toHtml();
    }
}
