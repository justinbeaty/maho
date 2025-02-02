<?php

/**
 * Maho
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Obtain all wishlists contents for specified client
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_Block_Customer_Edit_Tab_Wishlists extends Mage_Adminhtml_Block_Template
{
    /**
     * Add shopping wishlist grid of each website
     *
     * @return $this
     */
    #[\Override]
    protected function _prepareLayout()
    {
        $sharedWebsiteIds = Mage::registry('current_customer')->getSharedWebsiteIds();
        $isShared = count($sharedWebsiteIds) > 1;
        foreach ($sharedWebsiteIds as $websiteId) {
            $blockName = 'customer_wishlist_' . $websiteId;
            $block = $this->getLayout()->createBlock(
                'adminhtml/customer_edit_tab_wishlist',
                $blockName,
                ['website_id' => $websiteId],
            );
            if ($isShared) {
                $block->setWishlistHeader($this->__('Wishlist from %s', Mage::app()->getWebsite($websiteId)->getName()));
            }
            $this->setChild($blockName, $block);
        }
        return parent::_prepareLayout();
    }

    /**
     * Just get child blocks html
     *
     * @return string
     */
    #[\Override]
    protected function _toHtml()
    {
        Mage::dispatchEvent('adminhtml_block_html_before', ['block' => $this]);
        return $this->getChildHtml();
    }
}
