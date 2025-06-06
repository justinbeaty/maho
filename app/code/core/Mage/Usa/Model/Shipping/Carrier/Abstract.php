<?php

/**
 * Maho
 *
 * @package    Mage_Usa
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2023 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

abstract class Mage_Usa_Model_Shipping_Carrier_Abstract extends Mage_Shipping_Model_Carrier_Abstract
{
    public const USA_COUNTRY_ID = 'US';
    public const PUERTORICO_COUNTRY_ID = 'PR';
    public const GUAM_COUNTRY_ID = 'GU';
    public const GUAM_REGION_CODE = 'GU';

    protected static $_quotesCache = [];

    /**
     * Flag for check carriers for activity
     *
     * @var string
     */
    protected $_activeFlag = 'active';

    /**
     * Set flag for check carriers for activity
     *
     * @param string $code
     * @return Mage_Usa_Model_Shipping_Carrier_Abstract
     */
    public function setActiveFlag($code = 'active')
    {
        $this->_activeFlag = $code;
        return $this;
    }

    /**
     * Return code of carrier
     *
     * @return string
     */
    #[\Override]
    public function getCarrierCode()
    {
        return $this->_code ?? null;
    }

    public function getTrackingInfo($tracking)
    {
        $info = [];

        $result = $this->getTracking($tracking);

        if ($result instanceof Mage_Shipping_Model_Tracking_Result) {
            if ($trackings = $result->getAllTrackings()) {
                return $trackings[0];
            }
        } elseif (is_string($result) && !empty($result)) {
            return $result;
        }

        return false;
    }

    /**
     * Check if carrier has shipping tracking option available
     * All Mage_Usa carriers have shipping tracking option available
     *
     * @return bool
     */
    #[\Override]
    public function isTrackingAvailable()
    {
        return true;
    }

    /**
     * Check if city option required
     *
     * @return bool
     */
    #[\Override]
    public function isCityRequired()
    {
        return true;
    }

    /**
     * Determine whether zip-code is required for the country of destination
     *
     * @param string|null $countryId
     * @return bool
     */
    #[\Override]
    public function isZipCodeRequired($countryId = null)
    {
        if ($countryId != null) {
            return !Mage::helper('directory')->isZipCodeOptional($countryId);
        }
        return true;
    }

    /**
     * Check if carrier has shipping label option available
     *
     * @return bool
     */
    #[\Override]
    public function isShippingLabelsAvailable()
    {
        return true;
    }

    /**
     * Return items for further shipment rate evaluation. We need to pass children of a bundle instead passing the
     * bundle itself, otherwise we may not get a rate at all (e.g. when total weight of a bundle exceeds max weight
     * despite each item by itself is not)
     *
     * @return array
     */
    public function getAllItems(Mage_Shipping_Model_Rate_Request $request)
    {
        $items = [];
        if ($request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {
                /** @var Mage_Sales_Model_Quote_Item $item */
                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    // Don't process children here - we will process (or already have processed) them below
                    continue;
                }

                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    foreach ($item->getChildren() as $child) {
                        if (!$child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                            $items[] = $child;
                        }
                    }
                } else {
                    // Ship together - count compound item as one solid
                    $items[] = $item;
                }
            }
        }
        return $items;
    }

    /**
     * Processing additional validation to check if carrier applicable.
     *
     * @return Mage_Shipping_Model_Carrier_Abstract|Mage_Shipping_Model_Rate_Result_Error|bool
     */
    #[\Override]
    public function proccessAdditionalValidation(Mage_Shipping_Model_Rate_Request $request)
    {
        //Skip by item validation if there is no items in request
        if (!count($this->getAllItems($request))) {
            return $this;
        }

        $maxAllowedWeight   = (float) $this->getConfigData('max_package_weight');
        $errorMsg           = '';
        $configErrorMsg     = $this->getConfigData('specificerrmsg');
        $defaultErrorMsg    = Mage::helper('shipping')->__('The shipping module is not available.');
        $showMethod         = $this->getConfigData('showmethod');

        foreach ($this->getAllItems($request) as $item) {
            if ($item->getProduct() && $item->getProduct()->getId()) {
                $weight         = $item->getProduct()->getWeight();
                $stockItem      = $item->getProduct()->getStockItem();
                $doValidation   = true;

                if ($stockItem->getIsQtyDecimal() && $stockItem->getIsDecimalDivided()) {
                    if ($stockItem->getEnableQtyIncrements() && $stockItem->getQtyIncrements()) {
                        $weight = $weight * $stockItem->getQtyIncrements();
                    } else {
                        $doValidation = false;
                    }
                } elseif ($stockItem->getIsQtyDecimal() && !$stockItem->getIsDecimalDivided()) {
                    $weight = $weight * $item->getQty();
                }

                if ($doValidation && $weight > $maxAllowedWeight) {
                    $errorMsg = $configErrorMsg ?: $defaultErrorMsg;
                    break;
                }
            }
        }

        if (!$errorMsg && !$request->getDestPostcode() && $this->isZipCodeRequired($request->getDestCountryId())) {
            $errorMsg = Mage::helper('shipping')->__('This shipping method is not available, please specify ZIP-code');
        }

        if ($errorMsg && $showMethod) {
            $error = Mage::getModel('shipping/rate_result_error');
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage($errorMsg);
            return $error;
        } elseif ($errorMsg) {
            return false;
        }
        return $this;
    }

    /**
     * Returns cache key for some request to carrier quotes service
     *
     * @param string|array $requestParams
     * @return int
     */
    protected function _getQuotesCacheKey($requestParams)
    {
        if (is_array($requestParams)) {
            $requestParams = implode(
                ',',
                array_merge(
                    [$this->getCarrierCode()],
                    array_keys($requestParams),
                    $requestParams,
                ),
            );
        }
        return crc32($requestParams);
    }

    /**
     * Checks whether some request to rates have already been done, so we have cache for it
     * Used to reduce number of same requests done to carrier service during one session
     *
     * Returns cached response or null
     *
     * @param string|array $requestParams
     * @return null|string
     */
    protected function _getCachedQuotes($requestParams)
    {
        $key = $this->_getQuotesCacheKey($requestParams);
        return self::$_quotesCache[$key] ?? null;
    }

    /**
     * Sets received carrier quotes to cache
     *
     * @param string|array $requestParams
     * @param string $response
     * @return Mage_Usa_Model_Shipping_Carrier_Abstract
     */
    protected function _setCachedQuotes($requestParams, $response)
    {
        $key = $this->_getQuotesCacheKey($requestParams);
        self::$_quotesCache[$key] = $response;
        return $this;
    }

    /**
     * Prepare service name. Strip tags and entities from name
     *
     * @param string|object $name  service name or object with implemented __toString() method
     * @return string              prepared service name
     */
    protected function _prepareServiceName($name)
    {
        $name = html_entity_decode((string) $name);
        $name = strip_tags(preg_replace('#&\w+;#', '', $name));
        return trim($name);
    }

    /**
     * Prepare shipment request.
     * Validate and correct request information
     *
     *
     */
    protected function _prepareShipmentRequest(Varien_Object $request)
    {
        $phonePattern = '/[\s\_\-\(\)]+/';
        $phoneNumber = $request->getShipperContactPhoneNumber();
        $phoneNumber = preg_replace($phonePattern, '', $phoneNumber);
        $request->setShipperContactPhoneNumber($phoneNumber);
        $phoneNumber = $request->getRecipientContactPhoneNumber();
        $phoneNumber = preg_replace($phonePattern, '', $phoneNumber);
        $request->setRecipientContactPhoneNumber($phoneNumber);
    }

    /**
     * Do request to shipment
     *
     * @return Varien_Object
     */
    #[\Override]
    public function requestToShipment(Mage_Shipping_Model_Shipment_Request $request)
    {
        $packages = $request->getPackages();
        if (!is_array($packages) || !$packages) {
            Mage::throwException(Mage::helper('usa')->__('No packages for request'));
        }
        if ($request->getStoreId() != null) {
            $this->setStore($request->getStoreId());
        }
        $data = [];
        foreach ($packages as $packageId => $package) {
            $request->setPackageId($packageId);
            $request->setPackagingType($package['params']['container']);
            $request->setPackageWeight($package['params']['weight']);
            $request->setPackageParams(new Varien_Object($package['params']));
            $request->setPackageItems($package['items']);
            $result = $this->_doShipmentRequest($request);

            if ($result->hasErrors()) {
                $this->rollBack($data);
                break;
            } else {
                $data[] = [
                    'tracking_number' => $result->getTrackingNumber(),
                    'label_content'   => $result->getShippingLabelContent(),
                ];
            }
            if (!isset($isFirstRequest)) {
                $request->setMasterTrackingId($result->getTrackingNumber());
                $isFirstRequest = false;
            }
        }

        $response = new Varien_Object([
            'info'   => $data,
        ]);
        if ($result->getErrors()) {
            $response->setErrors($result->getErrors());
        }
        return $response;
    }

    /**
     * Do request to RMA shipment
     *
     * @param $request
     * @return Varien_Object
     */
    #[\Override]
    public function returnOfShipment($request)
    {
        $request->setIsReturn(true);
        $packages = $request->getPackages();
        if (!is_array($packages) || !$packages) {
            Mage::throwException(Mage::helper('usa')->__('No packages for request'));
        }
        if ($request->getStoreId() != null) {
            $this->setStore($request->getStoreId());
        }
        $data = [];
        foreach ($packages as $packageId => $package) {
            $request->setPackageId($packageId);
            $request->setPackagingType($package['params']['container']);
            $request->setPackageWeight($package['params']['weight']);
            $request->setPackageParams(new Varien_Object($package['params']));
            $request->setPackageItems($package['items']);
            $result = $this->_doShipmentRequest($request);

            if ($result->hasErrors()) {
                $this->rollBack($data);
                break;
            } else {
                $data[] = [
                    'tracking_number' => $result->getTrackingNumber(),
                    'label_content'   => $result->getShippingLabelContent(),
                ];
            }
            if (!isset($isFirstRequest)) {
                $request->setMasterTrackingId($result->getTrackingNumber());
                $isFirstRequest = false;
            }
        }

        $response = new Varien_Object([
            'info'   => $data,
        ]);
        if ($result->getErrors()) {
            $response->setErrors($result->getErrors());
        }
        return $response;
    }

    /**
     * For multi package shipments. Delete requested shipments if the current shipment
     * request is failed
     *
     * @todo implement rollback logic
     * @param array $data
     * @return bool
     */
    public function rollBack($data)
    {
        return true;
    }

    /**
     * Do shipment request to carrier web service, obtain Print Shipping Labels and process errors in response
     *
     * @return Varien_Object
     */
    abstract protected function _doShipmentRequest(Varien_Object $request);

    /**
     * Check is Country U.S. Possessions and Trust Territories
     *
     * @param string $countyId
     * @return bool
     */
    protected function _isUSCountry($countyId)
    {
        return match ($countyId) {
            // Samoa American, Guam, Northern Mariana Islands, Palau, Puerto Rico, Virgin Islands US, United States
            'AS', 'GU', 'MP', 'PW', 'PR', 'VI', 'US' => true,
            default => false,
        };
    }

    /**
     * Check is Canada
     *
     * @param string $countryId
     * @return bool
     */
    protected function _isCanada($countryId)
    {
        return $countryId == 'CA';
    }

    /**
     * Check whether girth is allowed for the carrier
     *
     * @param null|string $countyDest
     * @return bool
     */
    public function isGirthAllowed($countyDest = null)
    {
        return false;
    }
}
