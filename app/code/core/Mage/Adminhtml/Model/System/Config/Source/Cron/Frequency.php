<?php

/**
 * Maho
 *
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Adminhtml_Model_System_Config_Source_Cron_Frequency
{
    protected static $_options;

    public const CRON_DAILY    = 'D';
    public const CRON_WEEKLY   = 'W';
    public const CRON_MONTHLY  = 'M';

    public function toOptionArray()
    {
        if (!self::$_options) {
            self::$_options = [
                [
                    'label' => Mage::helper('cron')->__('Daily'),
                    'value' => self::CRON_DAILY,
                ],
                [
                    'label' => Mage::helper('cron')->__('Weekly'),
                    'value' => self::CRON_WEEKLY,
                ],
                [
                    'label' => Mage::helper('cron')->__('Monthly'),
                    'value' => self::CRON_MONTHLY,
                ],
            ];
        }
        return self::$_options;
    }
}
