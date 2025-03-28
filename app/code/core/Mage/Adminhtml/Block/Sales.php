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

/**
 * Adminhtml sales page content block
 *
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_Block_Sales extends Mage_Adminhtml_Block_Template
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('sales/index.phtml');
    }

    #[\Override]
    public function _beforeToHtml()
    {
        $this->assign('createUrl', $this->getUrl('*/sales/new'));
        $this->setChild('grid', $this->getLayout()->createBlock('adminhtml/sales_grid', 'sales.grid'));
        return parent::_beforeToHtml();
    }
}
