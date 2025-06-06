<?php

/**
 * Maho
 *
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Adminhtml_Block_Tax_Rate_Toolbar_Save extends Mage_Adminhtml_Block_Template
{
    /**
     * Mage_Adminhtml_Block_Tax_Rate_Toolbar_Save constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->assign('createUrl', $this->getUrl('*/tax_rate/save'));
        $this->setTemplate('tax/toolbar/rate/save.phtml');
    }

    #[\Override]
    protected function _prepareLayout()
    {
        $this->setChild(
            'backButton',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData([
                    'label'     => Mage::helper('tax')->__('Back'),
                    'onclick'   => 'window.location.href=\'' . $this->getUrl('*/*/') . '\'',
                    'class'     => 'back',
                ]),
        );

        $this->setChild(
            'resetButton',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData([
                    'label'     => Mage::helper('tax')->__('Reset'),
                    'onclick'   => 'window.location.reload()',
                ]),
        );

        $this->setChild(
            'saveButton',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData([
                    'label'     => Mage::helper('tax')->__('Save Rate'),
                    'onclick'   => 'wigetForm.submit();return false;',
                    'class'     => 'save',
                ]),
        );

        $this->setChild(
            'deleteButton',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData([
                    'label'     => Mage::helper('tax')->__('Delete Rate'),
                    'onclick'   => Mage::helper('core/js')->getDeleteConfirmJs(
                        $this->getUrl('*/*/delete', ['rate' => $this->getRequest()->getParam('rate')]),
                    ),
                    'class'     => 'delete',
                ]),
        );
        return parent::_prepareLayout();
    }

    /**
     * @return string
     */
    public function getBackButtonHtml()
    {
        return $this->getChildHtml('backButton');
    }

    /**
     * @return string
     */
    public function getResetButtonHtml()
    {
        return $this->getChildHtml('resetButton');
    }

    /**
     * @return string
     */
    public function getSaveButtonHtml()
    {
        return $this->getChildHtml('saveButton');
    }

    /**
     * @return string|void
     * @throws Exception
     */
    public function getDeleteButtonHtml()
    {
        if ((int) $this->getRequest()->getParam('rate') == 0) {
            return;
        }
        return $this->getChildHtml('deleteButton');
    }
}
