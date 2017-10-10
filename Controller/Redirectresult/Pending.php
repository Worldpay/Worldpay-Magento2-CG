<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Redirectresult;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Sapient\Worldpay\Model\Payment\StateResponse as PaymentStateResponse;
 
class Pending extends \Magento\Framework\App\Action\Action
{
    protected $pageFactory;
    public function __construct(Context $context, PageFactory $pageFactory,
        \Sapient\Worldpay\Model\Order\Service $orderservice,
        \Sapient\Worldpay\Model\Checkout\Service $checkoutservice,
        \Sapient\Worldpay\Model\Payment\Service $paymentservice,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
    ) { 
        $this->pageFactory = $pageFactory;
        $this->wplogger = $wplogger;
        $this->orderservice = $orderservice;
        $this->checkoutservice = $checkoutservice;
        $this->paymentservice = $paymentservice;
        return parent::__construct($context);

    }
 
    public function execute()
    {
        $this->wplogger->info('worldpay returned pending url');
        $order = $this->orderservice->getAuthorisedOrder();
        $magentoorder = $order->getOrder();
        $params = $this->getRequest()->getParams();
        try{
            if ($params) {
                $this->_applyPaymentUpdate(PaymentStateResponse::createFromPendingResponse($params), $order);
            }
        } catch (Exception $e) {
            $this->checkoutservice->clearSession();
            $this->orderservice->removeAuthorisedOrder();
            $this->wplogger->error($e->getMessage());
            if ($e->getMessage() == 'invalid state transition') {
                 return $this->pageFactory->create();
            } else {
                 return $this->resultRedirectFactory->create()->setPath('checkout/cart', ['_current' => true]);
            }
        }
        $this->checkoutservice->clearSession();
        $this->orderservice->removeAuthorisedOrder();
        return $this->pageFactory->create(); 
    }

    private function _applyPaymentUpdate($paymentState, $order)
    {
        try {
             $this->_paymentUpdate = $this->paymentservice
                        ->createPaymentUpdateFromWorldPayResponse($paymentState);
            $this->_paymentUpdate->apply($order->getPayment(), $order);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}