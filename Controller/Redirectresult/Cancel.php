<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Redirectresult;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Sapient\Worldpay\Model\Payment\StateResponse as PaymentStateResponse;
 
class Cancel extends \Magento\Framework\App\Action\Action
{
    protected $pageFactory;
    public function __construct(Context $context, PageFactory $pageFactory,
        \Sapient\Worldpay\Model\Order\Service $orderservice,
        \Sapient\Worldpay\Model\Checkout\Service $checkoutservice,
        \Sapient\Worldpay\Model\Payment\Service $paymentservice,
        \Sapient\Worldpay\Model\Request\AuthenticationService $authenticatinservice,
         \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
    ) { 
        $this->pageFactory = $pageFactory;
        $this->orderservice = $orderservice;
        $this->wplogger = $wplogger;
        $this->checkoutservice = $checkoutservice;
        $this->paymentservice = $paymentservice;
        $this->authenticatinservice = $authenticatinservice;
        return parent::__construct($context);

    }
 
    public function execute()
    {

        $this->wplogger->info('worldpay returned cancel url');
        $order = $this->orderservice->getAuthorisedOrder();
        $magentoorder = $order->getOrder();
        $notice = $this->_getCancellationNoticeForOrder($magentoorder);
        $this->messageManager->addNotice($notice);
        $params = $this->getRequest()->getParams();
        if ($this->authenticatinservice->requestAuthenticated($params)) {
            $this->_applyPaymentUpdate(PaymentStateResponse::createFromCancelledResponse($params), $order);
        }
        return $this->resultRedirectFactory->create()->setPath('checkout/cart', ['_current' => true]);
    }

    private function _getCancellationNoticeForOrder($order)
    {

        $incrementId = $order->getIncrementId();

        $message = is_null($incrementId)
            ? __('Order Cancelled')
            : __('Order #'. $incrementId.' Cancelled');

        return $message;
    }

    private function _applyPaymentUpdate($paymentState, $order)
    {
        try {
            $this->_paymentUpdate = $this->paymentservice
                        ->createPaymentUpdateFromWorldPayResponse($paymentState);
            $this->_paymentUpdate->apply($order->getPayment(), $order);
        } catch (Exception $e) {
            $this->wplogger->error($e->getMessage());
        }
    }
    
}