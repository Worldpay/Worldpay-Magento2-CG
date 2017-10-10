<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Redirectresult;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
 
class Iframe extends \Magento\Framework\App\Action\Action
{
    protected $pageFactory;
    protected $_status;
    public function __construct(Context $context, PageFactory $pageFactory,
        \Sapient\Worldpay\Model\Checkout\Hpp\State $hppstate,
        \Magento\Framework\UrlInterface $urlInterface, 
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
    ) { 
        $this->pageFactory = $pageFactory;
        $this->wplogger = $wplogger;
        $this->hppstate = $hppstate;
        $this->_urlInterface = $urlInterface;
        return parent::__construct($context);
    }
 
    public function execute()
    {
        $this->_getStatus()->reset();

        $params = $this->getRequest()->getParams();

        $redirecturl = $this->_urlInterface->getBaseUrl();

        if (isset($params['status'])) {
            $currenturl = $this->_urlInterface->getCurrentUrl();
            $redirecturl = str_replace("iframe/status/", "", $currenturl);
        }

        echo '<script>window.top.location.href = "'.$redirecturl.'";</script>';
    }

    protected function _getStatus()
    {
        if (is_null($this->_status)) {
            $this->_status = $this->hppstate;
        }

        return $this->_status;
    }

    
}