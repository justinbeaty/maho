<?php

/**
 * Maho
 *
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2025 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Adminhtml_Block_Catalog_Form extends Mage_Adminhtml_Block_Widget_Form
{
    #[\Override]
    protected function _prepareLayout()
    {
        $renderer = $this->getLayout()->createBlock('adminhtml/widget_form_renderer_element');
        if ($renderer instanceof Varien_Data_Form_Element_Renderer_Interface) {
            Varien_Data_Form::setElementRenderer($renderer);
        }

        $renderer = $this->getLayout()->createBlock('adminhtml/widget_form_renderer_fieldset');
        if ($renderer instanceof Varien_Data_Form_Element_Renderer_Interface) {
            Varien_Data_Form::setFieldsetRenderer($renderer);
        }

        $renderer = $this->getLayout()->createBlock('adminhtml/catalog_form_renderer_fieldset_element');
        if ($renderer instanceof Varien_Data_Form_Element_Renderer_Interface) {
            Varien_Data_Form::setFieldsetElementRenderer($renderer);
        }

        return $this;
    }
}
