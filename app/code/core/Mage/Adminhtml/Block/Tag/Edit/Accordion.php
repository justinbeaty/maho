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

class Mage_Adminhtml_Block_Tag_Edit_Accordion extends Mage_Adminhtml_Block_Widget_Accordion
{
    /**
     * Add products and customers accordion to layout
     *
     */
    #[\Override]
    protected function _prepareLayout()
    {
        if (is_null(Mage::registry('current_tag')->getId())) {
            return $this;
        }

        $tagModel = Mage::registry('current_tag');

        $this->setId('tag_customer_and_product_accordion');

        $this->addItem('tag_customer', [
            'title'         => Mage::helper('tag')->__('Customers Submitted this Tag'),
            'ajax'          => true,
            'content_url'   => $this->getUrl('*/*/customer', ['ret' => 'all', 'tag_id' => $tagModel->getId(), 'store' => $tagModel->getStoreId()]),
        ]);

        $this->addItem('tag_product', [
            'title'         => Mage::helper('tag')->__('Products Tagged by Customers'),
            'ajax'          => true,
            'content_url'   => $this->getUrl('*/*/product', ['ret' => 'all', 'tag_id' => $tagModel->getId(), 'store' => $tagModel->getStoreId()]),
        ]);
        return parent::_prepareLayout();
    }
}
