<?php

/**
 * Maho
 *
 * @package    Mage_Paypal
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Paypal_Model_Api_ProcessableException extends Mage_Core_Exception
{
    /**
     * Error code returned by PayPal
     */
    public const API_INTERNAL_ERROR = 10001;
    public const API_UNABLE_PROCESS_PAYMENT_ERROR_CODE = 10417;
    public const API_MAX_PAYMENT_ATTEMPTS_EXCEEDED     = 10416;
    public const API_UNABLE_TRANSACTION_COMPLETE       = 10486;
    public const API_TRANSACTION_EXPIRED               = 10411;
    public const API_DO_EXPRESS_CHECKOUT_FAIL          = 10422;
    public const API_COUNTRY_FILTER_DECLINE            = 10537;
    public const API_MAXIMUM_AMOUNT_FILTER_DECLINE     = 10538;
    public const API_OTHER_FILTER_DECLINE              = 10539;

    /**
     * Get error message which can be displayed to website user
     *
     * @return string
     */
    public function getUserMessage()
    {
        $message = match ($this->getCode()) {
            self::API_INTERNAL_ERROR, self::API_UNABLE_PROCESS_PAYMENT_ERROR_CODE => Mage::helper('paypal')->__("I'm sorry - but we were not able to process your payment. Please try another payment method or contact us so we can assist you."),
            self::API_COUNTRY_FILTER_DECLINE, self::API_MAXIMUM_AMOUNT_FILTER_DECLINE, self::API_OTHER_FILTER_DECLINE => Mage::helper('paypal')->__("I'm sorry - but we are not able to complete your transaction. Please contact us so we can assist you."),
            default => $this->getMessage(),
        };

        return $message;
    }
}
