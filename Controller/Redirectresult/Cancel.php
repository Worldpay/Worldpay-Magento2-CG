<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Redirectresult;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Sapient\Worldpay\Model\Payment\StateResponse as PaymentStateResponse;

/**
 * if got notification to get cancel order from worldpay then redirect to  cart page and display the notice
 */

class Cancel extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice
     * @param \Sapient\Worldpay\Model\Checkout\Service $checkoutservice
     * @param \Sapient\Worldpay\Model\Payment\Service $paymentservice
     * @param \Sapient\Worldpay\Model\Request\AuthenticationService $authenticatinservice
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
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
        if (!$this->orderservice->getAuthorisedOrder()) {
            return $this->resultRedirectFactory->create()->setPath('checkout/cart', ['_current' => true]);
        }
        $order = $this->orderservice->getAuthorisedOrder();
        $magentoorder = $order->getOrder();
        $notice = $this->_getCancellationNoticeForOrder($magentoorder);
        $this->messageManager->addNotice($notice);
        $params = $this->getRequest()->getParams();
        if ($this->authenticatinservice->requestAuthenticated($params)) {
            if (isset($params['orderKey'])) {
                $this->_applyPaymentUpdate(PaymentStateResponse::createFromCancelledResponse($params), $order);
            }
        }
        return $this->resultRedirectFactory->create()->setPath('checkout/cart', ['_current' => true]);
    }

    private function _getCancellationNoticeForOrder($order)
    {

        $incrementId = $order->getIncrementId();

        $message = $incrementId === null
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
        } catch (\Exception $e) {
            $this->wplogger->error($e->getMessage());
        }
    }
}
