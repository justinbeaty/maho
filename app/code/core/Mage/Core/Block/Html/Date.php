<?php

/**
 * Maho
 *
 * @package    Mage_Core
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2017-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024-2025 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * HTML select element block
 *
 * @package    Mage_Core
 *
 * @method string getClass()
 * @method $this setClass(string $value)
 * @method string getExtraParams()
 * @method $this setExtraParams(string $value)
 * @method string getFormat()
 * @method $this setFormat(string $value)
 * @method string getName()
 * @method $this setName(string $value)
 * @method string getTime()
 * @method $this setTime(string $value)
 * @method $this setTitle(string $value)
 * @method string getValue()
 * @method $this setValue(string $value)
 * @method string getYearsRange()
 * @method $this setYearsRange(string $value)
 */
class Mage_Core_Block_Html_Date extends Mage_Core_Block_Template
{
    /**
     * @return string
     */
    #[\Override]
    protected function _toHtml()
    {
        $setupObj = [
            'inputField' => (string) $this->getId(),
            'ifFormat'   => (string) Varien_Date::convertZendToStrftime($this->getFormat(), true, (bool) $this->getTime()),
            'showsTime'  => (bool) $this->getTime(),
        ];
        if ($calendarYearsRange = $this->getYearsRange()) {
            $setupObj['range'] = $calendarYearsRange;
        }
        $setupObj = Mage::helper('core')->jsonEncode($setupObj);

        return <<<HTML
            <input type="text" name="{$this->getName()}" id="{$this->getId()}" value="{$this->escapeHtml($this->getValue())}" class="{$this->getClass()}" {$this->getExtraParams()} />
            <script>Calendar.setup({$setupObj});</script>
        HTML;
    }

    /**
     * @param null $index deprecated
     * @return string
     */
    public function getEscapedValue($index = null)
    {
        if ($this->getFormat() && $this->getValue()) {
            return strftime($this->getFormat(), strtotime($this->getValue()));
        }

        return htmlspecialchars($this->getValue());
    }

    /**
     * @return string
     */
    public function getHtml()
    {
        return $this->toHtml();
    }
}
