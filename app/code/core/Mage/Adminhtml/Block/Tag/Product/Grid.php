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
 * Child Of Mage_Adminhtml_Block_Tag_Product
 *
 * @package    Mage_Adminhtml
 *
 * @method Mage_Tag_Model_Resource_Product_Collection getCollection()
 */
class Mage_Adminhtml_Block_Tag_Product_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Mage_Adminhtml_Block_Tag_Product_Grid constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('tag_product_grid' . Mage::registry('current_tag')->getId());
        $this->setDefaultSort('name');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);
    }

    /**
     * Retrieves Grid Url
     *
     * @return string
     */
    #[\Override]
    public function getGridUrl()
    {
        return $this->getUrl('*/*/product', ['_current' => true]);
    }

    #[\Override]
    protected function _prepareCollection()
    {
        $tagId = Mage::registry('current_tag')->getId();
        $storeId = Mage::registry('current_tag')->getStoreId();
        $collection = Mage::getModel('tag/tag')
            ->getEntityCollection()
            ->addTagFilter($tagId)
            ->addCustomerFilter(['null' => false])
            ->addStoreFilter($storeId)
            ->addPopularity($tagId);

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    #[\Override]
    protected function _prepareColumns()
    {
        $this->addColumn('product_id', [
            'header'        => Mage::helper('tag')->__('ID'),
            'width'         => '50px',
            'align'         => 'right',
            'index'         => 'entity_id',
        ]);

        $this->addColumn('name', [
            'header'    => Mage::helper('tag')->__('Product Name'),
            'index'     => 'name',
        ]);

        $this->addColumn('popularity', [
            'header'        => Mage::helper('tag')->__('# of Uses'),
            'width'         => '50px',
            'index'         => 'popularity',
            'type'          => 'number',
        ]);

        $this->addColumn('sku', [
            'header'    => Mage::helper('tag')->__('SKU'),
            'filter'    => false,
            'sortable'  => false,
            'width'     => 50,
            'align'     => 'right',
            'index'     => 'sku',
        ]);

        return parent::_prepareColumns();
    }

    /**
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
     * @return $this
     */
    #[\Override]
    protected function _addColumnFilterToCollection($column)
    {
        if ($column->getIndex() === 'popularity') {
            $this->getCollection()->addPopularityFilter($column->getFilter()->getCondition());
            return $this;
        }

        return parent::_addColumnFilterToCollection($column);
    }

    #[\Override]
    public function getRowUrl($row)
    {
        return $this->getUrl('*/catalog_product/edit', ['id' => $row->getProductId()]);
    }
}
