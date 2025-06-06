<?php

/**
 * Maho
 *
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Adminhtml_Block_Report_Tag_Popular_Detail_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('tag_grid');
    }

    /**
     * Prepare collection for grid
     *
     * @return $this
     */
    #[\Override]
    protected function _prepareCollection()
    {
        /** @var Mage_Reports_Model_Resource_Tag_Customer_Collection $collection */
        $collection = Mage::getResourceModel('reports/tag_customer_collection');
        $collection->addStatusFilter(Mage::getModel('tag/tag')->getApprovedStatus())
            ->addTagFilter($this->getRequest()->getParam('id'))
            ->addProductToSelect();

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Form columns for the grid
     *
     * @return $this
     */
    #[\Override]
    protected function _prepareColumns()
    {
        $this->addColumn('firstname', [
            'header'    => Mage::helper('reports')->__('First Name'),
            'index'     => 'firstname',
        ]);

        $this->addColumn('lastname', [
            'header'    => Mage::helper('reports')->__('Last Name'),
            'index'     => 'lastname',
        ]);

        $this->addColumn('product', [
            'header'    => Mage::helper('reports')->__('Product Name'),
            'index'     => 'product_name',
        ]);

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('added_in', [
                'header'    => Mage::helper('reports')->__('Submitted In'),
                'index'     => 'added_in',
                'type'      => 'store',
            ]);
        }

        $this->setFilterVisibility(false);

        $this->addExportType('*/*/exportTagDetailCsv', Mage::helper('reports')->__('CSV'));
        $this->addExportType('*/*/exportTagDetailExcel', Mage::helper('reports')->__('Excel XML'));

        return parent::_prepareColumns();
    }
}
