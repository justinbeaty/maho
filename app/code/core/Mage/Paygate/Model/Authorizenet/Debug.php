<?php

/**
 * Maho
 *
 * @package    Mage_Paygate
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * @method Mage_Paygate_Model_Resource_Authorizenet_Debug _getResource()
 * @method Mage_Paygate_Model_Resource_Authorizenet_Debug getResource()
 * @method string getRequestBody()
 * @method $this setRequestBody(string $value)
 * @method string getResponseBody()
 * @method $this setResponseBody(string $value)
 * @method string getRequestSerialized()
 * @method $this setRequestSerialized(string $value)
 * @method string getResultSerialized()
 * @method $this setResultSerialized(string $value)
 * @method string getRequestDump()
 * @method $this setRequestDump(string $value)
 * @method string getResultDump()
 * @method $this setResultDump(string $value)
 */
class Mage_Paygate_Model_Authorizenet_Debug extends Mage_Core_Model_Abstract
{
    #[\Override]
    protected function _construct()
    {
        $this->_init('paygate/authorizenet_debug');
    }
}
