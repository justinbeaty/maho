<?php

/**
 * Maho
 *
 * @package    Mage_PaypalUk
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_PaypalUk_Block_Express_Shortcut extends Mage_Paypal_Block_Express_Shortcut
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_paymentMethodCode = Mage_Paypal_Model_Config::METHOD_WPP_PE_EXPRESS;

    /**
     * Start express action
     *
     * @var string
     */
    protected $_startAction = 'paypaluk/express/start/button/1';

    /**
     * Express checkout model factory name
     *
     * @var string
     */
    protected $_checkoutType = 'paypaluk/express_checkout';

    /**
     * @param $quote
     * @return $this
     */
    #[\Override]
    protected function _getBmlShortcut($quote)
    {
        $bml = Mage::helper('payment')->getMethodInstance(Mage_Paypal_Model_Config::METHOD_WPP_PE_BML);
        $isBmlEnabled = $bml && $bml->isAvailable($quote);
        /** @var Mage_Core_Helper_Data $helper */
        $helper = $this->helper('core');
        $this->setBmlShortcutHtmlId($helper->uniqHash('ec_shortcut_bml_'))
            ->setBmlCheckoutUrl($this->getUrl('paypaluk/bml/start/button/1'))
            ->setBmlImageUrl('https://www.paypalobjects.com/webstatic/en_US/i/buttons/ppcredit-logo-medium.png')
            ->setMarketMessage('https://www.paypalobjects.com/webstatic/en_US/btn/btn_bml_text.png')
            ->setMarketMessageUrl('https://www.securecheckout.billmelater.com/paycapture-content/'
                . 'fetch?hash=AU826TU8&content=/bmlweb/ppwpsiw.html')
            ->setIsBmlEnabled($isBmlEnabled);
        return $this;
    }
}
