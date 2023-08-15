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
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        \Sapient\Worldpay\Model\Order\Service $orderservice,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
    ) {
        $this->pageFactory = $pageFactory;
        $this->orderservice = $orderservice;
        $this->wplogger = $wplogger;
        return parent::__construct($context);
    }
   /**
    * Execute
    *
    * @return string
    */
    public function execute()
    {
        $this->wplogger->info('worldpay returned success url');
        $this->orderservice->redirectOrderSuccess();
        $order = $this->orderservice->getAuthorisedOrder();
        $worldpaypayment = $order->getWorldPayPayment();
        $this->orderservice->removeAuthorisedOrder();
        if ($worldpaypayment->getIsMultishippingOrder()) {
            $url = 'multishipping/checkout/success';
            return $this->resultRedirectFactory->create()->setPath($url, ['_current' => true]);
        }
        return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success', ['_current' => true]);
    }
}
