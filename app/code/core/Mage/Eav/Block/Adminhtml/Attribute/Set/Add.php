<?php
/**
 * Maho
 *
 * @category   Mage
 * @package    Mage_Eav
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * @category   Mage
 * @package    Mage_Eav
 */
class Mage_Eav_Block_Adminhtml_Attribute_Set_Add extends Mage_Adminhtml_Block_Template
{
    #[\Override]
    protected function _construct()
    {
        $this->setTemplate('eav/attribute/set/add.phtml');
    }

    #[\Override]
    protected function _prepareLayout()
    {
        $this->setChild(
            'save_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData([
                    'label' => Mage::helper('eav')->__('Save Attribute Set'),
                    'onclick' => 'if (addSet.submit()) disableElements(\'save\');',
                    'class' => 'save'
                ])
        );
        $this->setChild(
            'back_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData([
                    'label' => Mage::helper('eav')->__('Back'),
                    'onclick' => Mage::helper('core/js')->getSetLocationJs($this->getUrl('*/*/')),
                    'class' => 'back'
                ])
        );

        $this->setChild(
            'setForm',
            $this->getLayout()->createBlock('eav/adminhtml_attribute_set_edit_formset')
        );
        return parent::_prepareLayout();
    }

    /**
     * @return string
     */
    protected function _getHeader()
    {
        return Mage::helper('eav')->__('Add New Attribute Set');
    }

    /**
     * @return string
     */
    protected function getSaveButtonHtml()
    {
        return $this->getChildHtml('save_button');
    }

    /**
     * @return string
     */
    protected function getBackButtonHtml()
    {
        return $this->getChildHtml('back_button');
    }

    /**
     * @return string
     */
    protected function getFormHtml()
    {
        return $this->getChildHtml('set_form');
    }

    /**
     * @return string
     */
    protected function getFormId()
    {
        return $this->getChild('set_form')->getForm()->getId();
    }
}