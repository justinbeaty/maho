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

class Mage_Adminhtml_Block_Cms_Page_Grid_Renderer_Action extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    #[\Override]
    public function render(Varien_Object $row)
    {
        Mage::dispatchEvent('adminhtml_cms_page_grid_renderer_action_before_render', ['row' => $row]);
        if ($row->getPreviewUrl()) {
            $href = $row->getPreviewUrl();
        } else {
            $urlModel = Mage::getModel('core/url')->setStore($row->getData('_first_store_id'));
            $href = $urlModel->getDirectUrl(
                $row->getIdentifier(),
                [
                    '_current' => false,
                    '_query'   => '___store=' . $row->getStoreCode(),
                ],
            );
        }
        return '<a href="' . $href . '" target="_blank">' . $this->__('Preview') . '</a>';
    }
}
