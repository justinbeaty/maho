<?php

/**
 * Maho
 *
 * @package    Mage_Downloadable
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2020-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Downloadable links purchased model
 *
 * @package    Mage_Downloadable
 *
 * @method Mage_Downloadable_Model_Resource_Link_Purchased _getResource()
 * @method Mage_Downloadable_Model_Resource_Link_Purchased getResource()
 * @method int getOrderId()
 * @method $this setOrderId(int $value)
 * @method string getOrderIncrementId()
 * @method $this setOrderIncrementId(string $value)
 * @method int getOrderItemId()
 * @method $this setOrderItemId(int $value)
 * @method string getCreatedAt()
 * @method $this setCreatedAt(string $value)
 * @method string getUpdatedAt()
 * @method $this setUpdatedAt(string $value)
 * @method int getCustomerId()
 * @method $this setCustomerId(int $value)
 * @method string getProductName()
 * @method $this setProductName(string $value)
 * @method string getProductSku()
 * @method $this setProductSku(string $value)
 * @method string getLinkSectionTitle()
 * @method $this setLinkSectionTitle(string $value)
 * @method $this setPurchasedItems(Mage_Downloadable_Model_Resource_Link_Purchased_Item_Collection $value)
 */
class Mage_Downloadable_Model_Link_Purchased extends Mage_Core_Model_Abstract
{
    #[\Override]
    protected function _construct()
    {
        $this->_init('downloadable/link_purchased');
        parent::_construct();
    }

    /**
     * Check order id
     */
    #[\Override]
    public function _beforeSave()
    {
        if ($this->getOrderId() == null) {
            throw new Exception(
                Mage::helper('downloadable')->__('Order id cannot be null'),
            );
        }
        return parent::_beforeSave();
    }
}
