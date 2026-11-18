<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Redirectresult;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Sapient\Worldpay\Model\Order\Service as OrderService;
use Sapient\Worldpay\Model\Payment\Service as PaymentService;
use Sapient\Worldpay\Logger\WorldpayLogger;
use Sapient\Worldpay\Helper\Data as WorldpayHelper;

class Success extends \Magento\Framework\App\Action\Action
{
    protected PageFactory $pageFactory;

    protected OrderService $orderService;

    protected WorldpayLogger $wplogger;

    protected WorldpayHelper $worldpayHelper;

    protected PaymentService $paymentservice;

    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        OrderService $orderService,
        WorldpayLogger $wplogger,
        WorldpayHelper $worldpayHelper,
        PaymentService $paymentservice
    ) {
        $this->pageFactory = $pageFactory;
        $this->orderService = $orderService;
        $this->wplogger = $wplogger;
        $this->worldpayHelper = $worldpayHelper;
        $this->paymentservice = $paymentservice;
        return parent::__construct($context);
    }

    public function execute(): Redirect
    {
        $request = $this->getRequest();
        $getParams = $request->getParams();

        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->worldpayHelper->isWorldPayEnable()) {
            $resultRedirect->setPath('noroute');
            return $resultRedirect;
        }
        $this->wplogger->info('worldpay returned success url');
        $this->orderService->redirectOrderSuccess();
        $order = $this->orderService->getAuthorisedOrder();

        if (!$order || !$order->getOrder()) {
            $this->wplogger->info('Order not found in session - likely a duplicate redirect');
            return $resultRedirect->setPath('checkout/onepage/success', ['_current' => true]);
        }

        $worldpayPayment = $order->getWorldPayPayment();

        try {
            $this->paymentservice->updatePaymentDccData($worldpayPayment, $getParams);
        } catch (\Exception $e) {
            $this->wplogger->error($e->getMessage());
        }

        $this->orderService->removeAuthorisedOrder();
        if ($worldpayPayment->getIsMultishippingOrder()) {
            $url = 'multishipping/checkout/success';
            return $resultRedirect->setPath($url, ['_current' => true]);
        }
        return $resultRedirect->setPath('checkout/onepage/success', ['_current' => true]);
    }
}
