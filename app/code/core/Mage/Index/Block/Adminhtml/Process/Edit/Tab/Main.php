<?php

/**
 * Maho
 *
 * @package    Mage_Index
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2020-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Index_Block_Adminhtml_Process_Edit_Tab_Main extends Mage_Adminhtml_Block_Widget_Form implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    #[\Override]
    protected function _prepareForm()
    {
        $model = Mage::registry('current_index_process');
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('index_process_');
        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => Mage::helper('index')->__('General'), 'class' => 'fieldset-wide'],
        );

        $fieldset->addField('process_id', 'hidden', ['name' => 'process', 'value' => $model->getId()]);

        $fieldset->addField('name', 'note', [
            'label' => Mage::helper('index')->__('Index Name'),
            'title' => Mage::helper('index')->__('Index Name'),
            'text'  => '<strong>' . $model->getIndexer()->getName() . '</strong>',
        ]);

        $fieldset->addField('description', 'note', [
            'label' => Mage::helper('index')->__('Index Description'),
            'title' => Mage::helper('index')->__('Index Description'),
            'text'  => $model->getIndexer()->getDescription(),
        ]);

        $fieldset->addField('mode', 'select', [
            'label' => Mage::helper('index')->__('Index Mode'),
            'title' => Mage::helper('index')->__('Index Mode'),
            'name'  => 'mode',
            'value' => $model->getMode(),
            'values' => $model->getModesOptions(),
        ]);

        //$form->setValues($model->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    #[\Override]
    public function getTabLabel()
    {
        return Mage::helper('index')->__('Process Information');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    #[\Override]
    public function getTabTitle()
    {
        return Mage::helper('index')->__('Process Information');
    }

    /**
     * Returns status flag about this tab can be showen or not
     *
     * @return true
     */
    #[\Override]
    public function canShowTab()
    {
        return true;
    }

    /**
     * Returns status flag about this tab hidden or not
     *
     * @return false
     */
    #[\Override]
    public function isHidden()
    {
        return false;
    }

    /**
     * Check permission for passed action
     *
     * @param string $action
     * @return bool
     */
    protected function _isAllowedAction($action)
    {
        return true;
    }
}
