<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Redirectresult;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

/**
 * Display page in iframe
 */
class Iframe extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;

    /**
     * @var \Sapient\Worldpay\Model\Checkout\Hpp\State
     */
    protected $_status;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param \Sapient\Worldpay\Model\Checkout\Hpp\State $hppstate
     * @param \Magento\Framework\UrlInterface $urlInterface
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        \Sapient\Worldpay\Model\Checkout\Hpp\State $hppstate,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        ResultFactory $resultPageFactory
    ) {
        $this->pageFactory = $pageFactory;
        $this->wplogger = $wplogger;
        $this->hppstate = $hppstate;
        $this->resultPageFactory = $resultPageFactory;
        return parent::__construct($context);
    }
 
    public function execute()
    {
        $this->_getStatus()->reset();

        $params = $this->getRequest()->getParams();

        $redirecturl = $this->_url->getBaseUrl();

        if (isset($params['status'])) {
            $currenturl = $this->_url->getCurrentUrl();
            $redirecturl = str_replace("iframe/status/", "", $currenturl);
        }
        $result = $this->resultPageFactory->create(ResultFactory::TYPE_RAW);
        $result->setHeader('Content-Type', 'text/html');
        $result->setContents('<script>window.top.location.href = "'.$redirecturl.'";</script>');
        return $result;
    }

    protected function _getStatus()
    {
        if ($this->_status === null) {
            $this->_status = $this->hppstate;
        }

        return $this->_status;
    }
}
