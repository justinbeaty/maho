<?php
/**
 * Maho
 *
 * @category   Mage
 * @package    Mage_Eav
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Block to render select attribute
 *
 * @category   Mage
 * @package    Mage_Eav
 */
class Mage_Eav_Block_Widget_Multiselect extends Mage_Eav_Block_Widget_Abstract
{
    #[\Override]
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('eav/widget/multiselect.phtml');
    }

    public function getFieldParams(): string
    {
        return 'multiple';
    }

    public function getOptions(): array
    {
        return $this->getAttribute()->getSource()->getAllOptions(false);
    }
}