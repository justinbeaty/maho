<?php

/**
 * Maho
 *
 * @package    Mage_Tag
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2020-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Tag_CustomerController extends Mage_Core_Controller_Front_Action
{
    /**
     * @return int|false
     * @throws Mage_Core_Exception
     */
    protected function _getTagId()
    {
        $tagId = (int) $this->getRequest()->getParam('tagId');
        if ($tagId) {
            $customerId = Mage::getSingleton('customer/session')->getCustomerId();
            $model = Mage::getModel('tag/tag_relation');
            $model->loadByTagCustomer(null, $tagId, $customerId);
            Mage::register('tagModel', $model);
            return $model->getTagId();
        }
        return false;
    }

    public function indexAction()
    {
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            Mage::getSingleton('customer/session')->authenticate($this);
            return;
        }

        $this->loadLayout();
        $this->_initLayoutMessages('tag/session');
        $this->_initLayoutMessages('catalog/session');

        $navigationBlock = $this->getLayout()->getBlock('customer_account_navigation');
        if ($navigationBlock) {
            $navigationBlock->setActive('tag/customer');
        }

        $block = $this->getLayout()->getBlock('customer_tags');
        if ($block) {
            $block->setRefererUrl($this->_getRefererUrl());
        }

        $this->getLayout()->getBlock('head')->setTitle(Mage::helper('tag')->__('My Tags'));
        $this->renderLayout();
    }

    public function viewAction()
    {
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            Mage::getSingleton('customer/session')->authenticate($this);
            return;
        }

        $tagId = $this->_getTagId();
        if ($tagId) {
            Mage::register('tagId', $tagId);
            $this->loadLayout();
            $this->_initLayoutMessages('tag/session');

            $navigationBlock = $this->getLayout()->getBlock('customer_account_navigation');
            if ($navigationBlock) {
                $navigationBlock->setActive('tag/customer');
            }

            $this->_initLayoutMessages('checkout/session');
            $this->getLayout()->getBlock('head')->setTitle(Mage::helper('tag')->__('My Tags'));
            $this->renderLayout();
        } else {
            $this->_forward('noRoute');
        }
    }

    public function removeAction()
    {
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            Mage::getSingleton('customer/session')->authenticate($this);
            return;
        }

        $tagId = $this->_getTagId();
        if ($tagId) {
            try {
                $model = Mage::registry('tagModel');
                $model->deactivate();
                $tag = Mage::getModel('tag/tag')->load($tagId)->aggregate();
                Mage::getSingleton('tag/session')->addSuccess(Mage::helper('tag')->__('The tag has been deleted.'));
                $this->getResponse()->setRedirect(Mage::getUrl('*/*/', [
                    self::PARAM_NAME_URL_ENCODED => Mage::helper('core')->urlEncode(Mage::getUrl('customer/account/')),
                ]));
                return;
            } catch (Exception $e) {
                Mage::getSingleton('tag/session')->addError(Mage::helper('tag')->__('Unable to remove tag. Please, try again later.'));
            }
        } else {
            $this->_forward('noRoute');
        }
    }
}
