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

class Mage_Adminhtml_Block_System_Config_Edit extends Mage_Adminhtml_Block_Widget
{
    public const DEFAULT_SECTION_BLOCK = 'adminhtml/system_config_form';

    protected $_section;

    /**
     * Mage_Adminhtml_Block_System_Config_Edit constructor.
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('system/config/edit.phtml');

        $sectionCode = $this->getRequest()->getParam('section');
        $sections = Mage::getSingleton('adminhtml/config')->getSections();

        $this->_section = $sections->$sectionCode;

        $this->setTitle((string) $this->_section->label);
        $this->setHeaderCss((string) $this->_section->header_css);
    }

    #[\Override]
    protected function _prepareLayout()
    {
        $this->setChild(
            'save_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData([
                    'label'     => Mage::helper('adminhtml')->__('Save Config'),
                    'onclick'   => 'configForm.submit()',
                    'class' => 'save',
                ]),
        );
        return parent::_prepareLayout();
    }

    /**
     * @return string
     */
    public function getSaveButtonHtml()
    {
        return $this->getChildHtml('save_button');
    }

    /**
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('*/*/save', ['_current' => true]);
    }

    /**
     * @return $this
     */
    public function initForm()
    {
        $blockName = (string) $this->_section->frontend_model;
        if (empty($blockName)) {
            $blockName = self::DEFAULT_SECTION_BLOCK;
        }
        $this->setChild(
            'form',
            $this->getLayout()->createBlock($blockName)
                ->initForm(),
        );
        return $this;
    }
}
