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
 * Renderer for URL key input
 * Allows to manage and overwrite URL Rewrites History save settings
 *
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_Block_Catalog_Form_Renderer_Attribute_Urlkey extends Mage_Adminhtml_Block_Catalog_Form_Renderer_Fieldset_Element
{
    #[\Override]
    public function getElementHtml()
    {
        $element = $this->getElement();
        if (!$element->getValue()) {
            return parent::getElementHtml();
        }
        $element->setOnkeyup("onUrlkeyChanged('" . $element->getHtmlId() . "')");
        $element->setOnchange("onUrlkeyChanged('" . $element->getHtmlId() . "')");

        $data = [
            'name' => $element->getData('name') . '_create_redirect',
            'disabled' => true,
        ];
        $hidden =  new Varien_Data_Form_Element_Hidden($data);
        $hidden->setForm($element->getForm());

        $storeId = $element->getForm()->getDataObject()->getStoreId();
        $data['html_id'] = $element->getHtmlId() . '_create_redirect';
        $data['label'] = Mage::helper('catalog')->__('Create Permanent Redirect for old URL');
        $data['value'] = $element->getValue();
        $data['checked'] = Mage::helper('catalog')->shouldSaveUrlRewritesHistory($storeId);
        $checkbox = new Varien_Data_Form_Element_Checkbox($data);
        $checkbox->setForm($element->getForm());

        return parent::getElementHtml() . '<br/>' . $hidden->getElementHtml() . $checkbox->getElementHtml() . $checkbox->getLabelHtml();
    }
}
