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

class Mage_Adminhtml_Block_Rating_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('rating_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('rating')->__('Rating Information'));
    }

    #[\Override]
    protected function _beforeToHtml()
    {
        $this->addTab('form_section', [
            'label'     => Mage::helper('rating')->__('Rating Information'),
            'title'     => Mage::helper('rating')->__('Rating Information'),
            'content'   => $this->getLayout()->createBlock('adminhtml/rating_edit_tab_form')->toHtml(),
        ])
        ;

        return parent::_beforeToHtml();
    }
}
