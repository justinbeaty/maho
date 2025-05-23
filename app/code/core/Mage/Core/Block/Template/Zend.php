<?php

/**
 * Maho
 *
 * @package    Mage_Core
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2020-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Core_Block_Template_Zend extends Mage_Core_Block_Template
{
    /**
     * @var Zend_View
     */
    protected $_view = null;

    /**
     * Class constructor. Base html block
     */
    #[\Override]
    public function _construct()
    {
        parent::_construct();
        $this->_view = new Zend_View();
    }

    /**
     * @param array|string $key
     * @param array|string|null $value
     * @return $this|Mage_Core_Block_Template
     * @throws Zend_View_Exception
     */
    #[\Override]
    public function assign($key, $value = null)
    {
        if (is_array($key) && is_null($value)) {
            foreach ($key as $k => $v) {
                $this->assign($k, $v);
            }
        } elseif (!is_null($value)) {
            $this->_view->assign($key, $value);
        }
        return $this;
    }

    /**
     * @param string $dir
     * @return $this
     */
    #[\Override]
    public function setScriptPath($dir)
    {
        $this->_view->setScriptPath($dir . DS);
        return $this;
    }

    /**
     * @param string $fileName
     * @return string
     */
    #[\Override]
    public function fetchView($fileName)
    {
        return $this->_view->render($fileName);
    }
}
