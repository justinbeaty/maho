<?php

/**
 * Maho
 *
 * @package    Mage_Core
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2020-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * @method array getLiParams()
 * @method $this setLiParams(array $value)
 * @method array getAParams()
 * @method $this setAParams(array $value)
 * @method string getInnerText()
 * @method $this setInnerText(string $value)
 * @method string getAfterText()
 * @method $this setAfterText(string $value)
 */
class Mage_Core_Block_Text extends Mage_Core_Block_Abstract
{
    /**
     * @param string $text
     * @return $this
     */
    public function setText($text)
    {
        $this->setData('text', $text);
        return $this;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->getData('text');
    }

    /**
     * @param string $text
     * @param bool $before
     */
    public function addText($text, $before = false)
    {
        if ($before) {
            $this->setText($text . $this->getText());
        } else {
            $this->setText($this->getText() . $text);
        }
    }

    /**
     * @return string
     */
    #[\Override]
    protected function _toHtml()
    {
        if (!$this->_beforeToHtml()) {
            return '';
        }

        return $this->getText();
    }
}
