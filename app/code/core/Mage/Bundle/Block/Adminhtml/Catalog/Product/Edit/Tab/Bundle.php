<?php

/**
 * Maho
 *
 * @package    Mage_Bundle
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Bundle_Block_Adminhtml_Catalog_Product_Edit_Tab_Bundle extends Mage_Adminhtml_Block_Widget implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    protected $_product = null;
    public function __construct()
    {
        parent::__construct();
        $this->setSkipGenerateContent(true);
        $this->setTemplate('bundle/product/edit/bundle.phtml');
    }

    /**
     * @return string
     */
    public function getTabUrl()
    {
        return $this->getUrl('*/bundle_product_edit/form', ['_current' => true]);
    }

    /**
     * @return string
     */
    public function getTabClass()
    {
        return 'ajax';
    }

    #[\Override]
    protected function _prepareLayout()
    {
        $this->setChild(
            'add_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData([
                    'label' => Mage::helper('bundle')->__('Add New Option'),
                    'class' => 'add',
                    'id'    => 'add_new_option',
                    'on_click' => 'bOption.add()',
                ]),
        );

        $this->setChild(
            'options_box',
            $this->getLayout()->createBlock(
                'bundle/adminhtml_catalog_product_edit_tab_bundle_option',
                'adminhtml.catalog.product.edit.tab.bundle.option',
            ),
        );

        return parent::_prepareLayout();
    }

    /**
     * Check block readonly
     *
     * @return bool
     */
    public function isReadonly()
    {
        return $this->getProduct()->getCompositeReadonly();
    }

    /**
     * @return string
     */
    public function getAddButtonHtml()
    {
        return $this->getChildHtml('add_button');
    }

    /**
     * @return string
     */
    public function getOptionsBoxHtml()
    {
        return $this->getChildHtml('options_box');
    }

    /**
     * @return string
     */
    public function getFieldSuffix()
    {
        return 'product';
    }

    /**
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        return Mage::registry('product');
    }

    /**
     * @return string
     */
    #[\Override]
    public function getTabLabel()
    {
        return Mage::helper('bundle')->__('Bundle Items');
    }

    /**
     * @return string
     */
    #[\Override]
    public function getTabTitle()
    {
        return Mage::helper('bundle')->__('Bundle Items');
    }

    /**
     * @return bool
     */
    #[\Override]
    public function canShowTab()
    {
        return true;
    }

    /**
     * @return bool
     */
    #[\Override]
    public function isHidden()
    {
        return false;
    }
}
