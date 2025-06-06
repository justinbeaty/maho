<?php

/**
 * Maho
 *
 * @package    Mage_Reports
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * @method Mage_Reports_Model_Resource_Event_Type _getResource()
 * @method Mage_Reports_Model_Resource_Event_Type getResource()
 * @method string getEventName()
 * @method $this setEventName(string $value)
 * @method int getCustomerLogin()
 * @method $this setCustomerLogin(int $value)
 */
class Mage_Reports_Model_Event_Type extends Mage_Core_Model_Abstract
{
    #[\Override]
    protected function _construct()
    {
        $this->_init('reports/event_type');
    }
}
