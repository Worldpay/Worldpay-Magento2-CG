<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Observer;

use Magento\Framework\Event\ObserverInterface;
use Exception;

class Redirect implements ObserverInterface {
    
    public function __construct (
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Checkout\Model\Session $checkoutsession,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\UrlInterface $url
    ) {
        $this->wplogger = $wplogger;
        $this->checkoutsession = $checkoutsession;
        $this->_responseFactory = $responseFactory;
        $this->_url = $url;
    }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->checkoutsession->getAdminWpRedirecturl()) {
            $redirecturl = $this->checkoutsession->getAdminWpRedirecturl();
            $this->checkoutsession->unsAdminWpRedirecturl();
            $this->_responseFactory->create()->setRedirect($redirecturl)->sendResponse();
            die();
        }
    }
}