<?php

/**
 * Maho
 *
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Adminhtml_Newsletter_SubscriberController extends Mage_Adminhtml_Controller_Action
{
    /**
     * ACL resource
     * @see Mage_Adminhtml_Controller_Action::_isAllowed()
     */
    public const ADMIN_RESOURCE = 'newsletter/subscriber';

    public function indexAction()
    {
        $this->_title($this->__('Newsletter'))->_title($this->__('Newsletter Subscribers'));

        if ($this->getRequest()->getParam('ajax')) {
            $this->_forward('grid');
            return;
        }

        $this->loadLayout();

        $this->_setActiveMenu('newsletter/subscriber');

        $this->_addBreadcrumb(Mage::helper('newsletter')->__('Newsletter'), Mage::helper('newsletter')->__('Newsletter'));
        $this->_addBreadcrumb(Mage::helper('newsletter')->__('Subscribers'), Mage::helper('newsletter')->__('Subscribers'));

        $this->_addContent(
            $this->getLayout()->createBlock('adminhtml/newsletter_subscriber', 'subscriber'),
        );

        $this->renderLayout();
    }

    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('adminhtml/newsletter_subscriber_grid')->toHtml(),
        );
    }

    /**
     * Export subscribers grid to CSV format
     */
    public function exportCsvAction()
    {
        $grid = $this->getLayout()->createBlock('adminhtml/newsletter_subscriber_grid');
        $this->_prepareDownloadResponse(...$grid->getCsvFile('subscribers.csv', -1));
    }

    /**
     * Export subscribers grid to XML format
     */
    public function exportXmlAction()
    {
        $grid = $this->getLayout()->createBlock('adminhtml/newsletter_subscriber_grid');
        $this->_prepareDownloadResponse(...$grid->getExcelFile('subscribers.xml', -1));
    }

    /**
     * Prepare file download response
     *
     * @todo remove in 1.3
     * @deprecated please use $this->_prepareDownloadResponse()
     * @see Mage_Adminhtml_Controller_Action::_prepareDownloadResponse()
     * @param string $fileName
     * @param string $content
     * @param string $contentType
     */
    protected function _sendUploadResponse($fileName, $content, $contentType = 'application/octet-stream')
    {
        $this->_prepareDownloadResponse($fileName, $content, $contentType);
    }

    public function massUnsubscribeAction()
    {
        $subscribersIds = $this->getRequest()->getParam('subscriber');
        if (!is_array($subscribersIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('newsletter')->__('Please select subscriber(s)'));
        } else {
            try {
                foreach ($subscribersIds as $subscriberId) {
                    $subscriber = Mage::getModel('newsletter/subscriber')->load($subscriberId);
                    $subscriber->unsubscribe();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) were updated', count($subscribersIds)),
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }

    public function massDeleteAction()
    {
        $subscribersIds = $this->getRequest()->getParam('subscriber');
        if (!is_array($subscribersIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('newsletter')->__('Please select subscriber(s)'));
        } else {
            try {
                foreach ($subscribersIds as $subscriberId) {
                    $subscriber = Mage::getModel('newsletter/subscriber')->load($subscriberId);
                    $subscriber->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) were deleted', count($subscribersIds)),
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }
}
