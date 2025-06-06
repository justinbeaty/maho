<?php

/**
 * Maho
 *
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Adminhtml_Model_System_Config_Backend_Customer_Password_Link_Expirationperiod extends Mage_Core_Model_Config_Data
{
    /**
     * Validate expiration period value before saving
     *
     * @return $this
     */
    #[\Override]
    protected function _beforeSave()
    {
        parent::_beforeSave();
        $resetPasswordLinkExpirationPeriod = (int) $this->getValue();

        if ($resetPasswordLinkExpirationPeriod < 1) {
            $resetPasswordLinkExpirationPeriod = (int) $this->getOldValue();
        }
        $this->setValue((string) $resetPasswordLinkExpirationPeriod);
        return $this;
    }
}
