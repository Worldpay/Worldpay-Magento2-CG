<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Observer;

use \Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Exception;

class Redirect implements ObserverInterface
{
    /**
     * @var _responseFactory
     */
    protected $_responseFactory;
    /**
     * @var _url
     */
    protected $_url;

    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    protected $wplogger;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutsession;

     /**
      * Constructor
      *
      * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
      * @param \Magento\Checkout\Model\Session $checkoutsession
      * @param \Magento\Framework\App\ResponseFactory $responseFactory
      * @param \Magento\Framework\UrlInterface $url
      */

    public function __construct(
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
    /**
     * Execute
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return string
     */

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $event = $observer->getEvent();
        if ($this->checkoutsession->getAdminWpRedirecturl()) {
            $redirecturl = $this->checkoutsession->getAdminWpRedirecturl();
            $this->checkoutsession->unsAdminWpRedirecturl();
            $this->_responseFactory->create()->setRedirect($redirecturl)->sendResponse();
            $this->getResponse()->setBody();
        }
    }
}
