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

class Mage_Adminhtml_Block_Newsletter_Template_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('newsletterTemplateGrid');
        $this->setDefaultSort('template_code');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    #[\Override]
    protected function _construct()
    {
        $this->setEmptyText(Mage::helper('newsletter')->__('No Templates Found'));
    }

    #[\Override]
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceSingleton('newsletter/template_collection')
            ->useOnlyActual();

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    #[\Override]
    protected function _prepareColumns()
    {
        $this->addColumn(
            'template_code',
            ['header' => Mage::helper('newsletter')->__('ID'), 'align' => 'center', 'index' => 'template_id'],
        );
        $this->addColumn(
            'code',
            [
                'header' => Mage::helper('newsletter')->__('Template Name'),
                'index' => 'template_code',
            ],
        );

        $this->addColumn(
            'added_at',
            [
                'header' => Mage::helper('newsletter')->__('Date Added'),
                'index' => 'added_at',
                'gmtoffset' => true,
                'type' => 'datetime',
            ],
        );

        $this->addColumn(
            'modified_at',
            [
                'header' => Mage::helper('newsletter')->__('Date Updated'),
                'index' => 'modified_at',
                'gmtoffset' => true,
                'type' => 'datetime',
            ],
        );

        $this->addColumn(
            'subject',
            [
                'header' => Mage::helper('newsletter')->__('Subject'),
                'index' => 'template_subject',
            ],
        );

        $this->addColumn(
            'sender',
            [
                'header' => Mage::helper('newsletter')->__('Sender'),
                'index' => 'template_sender_email',
                'renderer' => 'adminhtml/newsletter_template_grid_renderer_sender',
            ],
        );

        $this->addColumn(
            'type',
            [
                'header' => Mage::helper('newsletter')->__('Template Type'),
                'index' => 'template_type',
                'type' => 'options',
                'options' => [
                    Mage_Core_Model_Template::TYPE_HTML => 'html',
                    Mage_Core_Model_Template::TYPE_TEXT => 'text',
                ],
            ],
        );

        $this->addColumn(
            'action',
            [
                'type'      => 'action',
                'index'     => 'template_id',
                'no_link'   => true,
                'width'     => '170',
                'renderer'  => 'adminhtml/newsletter_template_grid_renderer_action',
            ],
        );

        return $this;
    }

    #[\Override]
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', ['id' => $row->getId()]);
    }
}
