<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Redirectresult;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
 
 /**
  * remove authorized order from card and Redirect to success page
  */
class Success extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;

     /**
      * @var \Sapient\Worldpay\Model\Order\Service
      */
    protected $orderservice;

     /**
      * @var \Sapient\Worldpay\Logger\WorldpayLogger
      */
    protected $wplogger;

    /**
     * @var \Sapient\Worldpay\Helper\Data
     */
    protected $worldpayHelper;
    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Helper\Data $worldpayHelper
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        \Sapient\Worldpay\Model\Order\Service $orderservice,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Helper\Data $worldpayHelper,
    ) {
        $this->pageFactory = $pageFactory;
        $this->orderservice = $orderservice;
        $this->wplogger = $wplogger;
        $this->worldpayHelper = $worldpayHelper;
        return parent::__construct($context);
    }
   /**
    * Execute
    *
    * @return string
    */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->worldpayHelper->isWorldPayEnable()) {
            $resultRedirect->setPath('noroute');
            return $resultRedirect;
        }
        $this->wplogger->info('worldpay returned success url');
        $this->orderservice->redirectOrderSuccess();
        $order = $this->orderservice->getAuthorisedOrder();
        $worldpaypayment = $order->getWorldPayPayment();
        $this->orderservice->removeAuthorisedOrder();
        if ($worldpaypayment->getIsMultishippingOrder()) {
            $url = 'multishipping/checkout/success';
            return $resultRedirect->setPath($url, ['_current' => true]);
        }
        return $resultRedirect->setPath('checkout/onepage/success', ['_current' => true]);
    }
}
