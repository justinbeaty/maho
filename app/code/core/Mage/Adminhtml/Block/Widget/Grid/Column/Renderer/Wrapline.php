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

class Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Wrapline extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Default max length of a line at one row
     *
     * @var int
     */
    protected $_defaultMaxLineLength = 60;

    /**
     * Renders grid column
     *
     * @return string
     */
    #[\Override]
    public function render(Varien_Object $row)
    {
        $line = parent::_getValue($row);
        $wrappedLine = '';
        $lineLength = $this->getColumn()->getData('lineLength') ?: $this->_defaultMaxLineLength;
        for ($i = 0, $n = floor(Mage::helper('core/string')->strlen($line) / $lineLength); $i <= $n; $i++) {
            $wrappedLine .= Mage::helper('core/string')->substr($line, ($lineLength * $i), $lineLength) . '<br />';
        }
        return $wrappedLine;
    }
}
