<?php

/**
 * Maho
 *
 * @package    Mage_Api2
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2023 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

abstract class Mage_Api2_Model_Route_Abstract extends Zend_Controller_Router_Route
{
    /**
     * Names for Zend_Controller_Router_Route::__construct params
     */
    public const PARAM_ROUTE      = 'route';
    public const PARAM_DEFAULTS   = 'defaults';
    public const PARAM_REQS       = 'reqs';
    public const PARAM_TRANSLATOR = 'translator';
    public const PARAM_LOCALE     = 'locale';

    /**
     * Default values of parent::__construct() params
     *
     * @var array
     */
    protected $_paramsDefaultValues = [
        self::PARAM_ROUTE      => null,
        self::PARAM_DEFAULTS   => [],
        self::PARAM_REQS       => [],
        self::PARAM_TRANSLATOR => null,
        self::PARAM_LOCALE     => null,
    ];

    /**
     * Process construct param and call parent::__construct() with params
     */
    public function __construct(array $arguments)
    {
        parent::__construct(
            $this->_getArgumentValue(self::PARAM_ROUTE, $arguments),
            $this->_getArgumentValue(self::PARAM_DEFAULTS, $arguments),
            $this->_getArgumentValue(self::PARAM_REQS, $arguments),
            $this->_getArgumentValue(self::PARAM_TRANSLATOR, $arguments),
            $this->_getArgumentValue(self::PARAM_LOCALE, $arguments),
        );
    }

    /**
     * Retrieve argument value
     *
     * @param string $name argument name
     * @return mixed
     */
    protected function _getArgumentValue($name, array $arguments)
    {
        return $arguments[$name] ?? $this->_paramsDefaultValues[$name];
    }

    /**
     * Matches a Request with parts defined by a map. Assigns and
     * returns an array of variables on a successful match.
     *
     * @param Mage_Api2_Model_Request $request
     * @param bool $partial Partial path matching
     * @return array|bool An array of assigned values or a boolean false on a mismatch
     */
    #[\Override]
    public function match($request, $partial = false)
    {
        return parent::match(ltrim($request->getPathInfo(), $this->_urlDelimiter), $partial);
    }
}
