<?php

/**
 * Maho
 *
 * @package    Mage_Usa
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

abstract class Mage_Usa_Model_Shipping_Carrier_Dhl_Abstract extends Mage_Usa_Model_Shipping_Carrier_Abstract
{
    /**
     * Response condition code for service is unavailable at the requested date
     */
    public const CONDITION_CODE_SERVICE_DATE_UNAVAILABLE = 1003;

    /**
     * Count of days to look forward if day is not unavailable
     */
    public const UNAVAILABLE_DATE_LOOK_FORWARD = 5;

    /**
     * Date format for request
     */
    public const REQUEST_DATE_FORMAT = 'Y-m-d';

    /**
     * Get shipping date
     *
     * @param bool $domestic
     * @return string
     */
    protected function _getShipDate($domestic = true)
    {
        return $this->_determineShippingDay(
            $this->getConfigData($domestic ? 'shipment_days' : 'intl_shipment_days'),
            date(self::REQUEST_DATE_FORMAT),
        );
    }

    /**
     * Determine shipping day according to configuration settings
     *
     * @param array $shippingDays
     * @param string $date
     * @return string
     */
    protected function _determineShippingDay($shippingDays, $date)
    {
        if (empty($shippingDays)) {
            return $date;
        }

        $shippingDays = explode(',', $shippingDays);

        $i = 0;
        $weekday = date('D', strtotime($date));
        while (!in_array($weekday, $shippingDays) && $i < 10) {
            $i++;
            $weekday = date('D', strtotime("$date +$i day"));
        }

        return date(self::REQUEST_DATE_FORMAT, strtotime("$date +$i day"));
    }
}
