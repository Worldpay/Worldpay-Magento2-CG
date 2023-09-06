<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Paybylink;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
 
 /**
  * remove authorized order from card and Redirect to success page
  */
class Process extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Sapient\Worldpay\Model\PaymentMethods\PayByLink
     */
    protected $paybylink;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;
    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var \Sapient\Worldpay\Model\Order\Service
     */
    protected $orderservice;

    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    protected $wplogger;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $orderItemsDetails;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $worldpayhelper;

    /**
     * @var \Sapient\Worldpay\Model\Payment\Service
     */
    protected $paymentservice;

    /**
     * @var \Sapient\Worldpay\Model\Request\AuthenticationService
     */
    protected $authenticatinservice;

    /**
     * @var \Sapient\Worldpay\Model\Payment\WpResponse
     */
    protected $wpresponse;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Sapient\Worldpay\Model\ResourceModel\Multishipping\Order\Collection
     */
    protected $wpMultishippingCollection;

    /**
     * @var \Sapient\Worldpay\Model\Payment\MultishippingStateResponse
     */
    protected $multishippingStateResponse;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Sapient\Worldpay\Model\PaymentMethods\PayByLink $paybylink
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Magento\Framework\Controller\Result\Redirect $resultRedirectFactory
     * @param \Magento\Sales\Model\Order $orderItemsDetails
     * @param \Sapient\Worldpay\Helper\Data $worldpayhelper
     * @param \Sapient\Worldpay\Model\Payment\Service $paymentservice
     * @param \Sapient\Worldpay\Model\Request\AuthenticationService $authenticatinservice
     * @param \Sapient\Worldpay\Model\Payment\WpResponse $wpresponse
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Sapient\Worldpay\Model\ResourceModel\Multishipping\Order\Collection $wpMultishippingCollection
     * @param \Sapient\Worldpay\Model\Payment\MultishippingStateResponse $multishippingStateResponse
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\RequestInterface $request,
        \Sapient\Worldpay\Model\Order\Service $orderservice,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Sapient\Worldpay\Model\PaymentMethods\PayByLink $paybylink,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Framework\Controller\Result\Redirect $resultRedirectFactory,
        \Magento\Sales\Model\Order $orderItemsDetails,
        \Sapient\Worldpay\Helper\Data $worldpayhelper,
        \Sapient\Worldpay\Model\Payment\Service $paymentservice,
        \Sapient\Worldpay\Model\Request\AuthenticationService $authenticatinservice,
        \Sapient\Worldpay\Model\Payment\WpResponse $wpresponse,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Sapient\Worldpay\Model\ResourceModel\Multishipping\Order\Collection $wpMultishippingCollection,
        \Sapient\Worldpay\Model\Payment\MultishippingStateResponse $multishippingStateResponse
    ) {
        $this->request = $request;
        $this->orderservice = $orderservice;
        $this->quoteFactory = $quoteFactory;
        $this->paybylink = $paybylink;
        $this->wplogger = $wplogger;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->orderItemsDetails = $orderItemsDetails;
        $this->worldpayhelper = $worldpayhelper;
        $this->paymentservice = $paymentservice;
        $this->authenticatinservice = $authenticatinservice;
        $this->wpresponse = $wpresponse;
        $this->_checkoutSession = $checkoutSession;
        $this->wpMultishippingCollection = $wpMultishippingCollection;
        $this->multishippingStateResponse = $multishippingStateResponse;

        return parent::__construct($context);
    }

    /**
     * Order Process action
     *
     * @return string
     */
    public function execute()
    {
        $this->wplogger->info('Pay by link Process controller executed.');
        $orderCode = $this->request->getParam('orderkey');
        if(empty($orderCode)){
           return $this->resultRedirectFactory->create()->setPath('checkout/cart', ['_current' => true]);
        }
        $orderIncrementId = current(explode('-', $orderCode));
        $orderInfo = $this->orderItemsDetails->loadByIncrementId($orderIncrementId);
        if ($orderInfo->getId()) {
            if (strtolower($orderInfo->getStatus()) == 'canceled') {
                $this->messageManager->addNotice(__('Order not found or cancelled previously'));
                return $this->resultRedirectFactory->create()->setPath('checkout/cart', ['_current' => true]);
            }

            $order = $this->orderservice->getByIncrementId($orderIncrementId);
            $magentoorder = $order->getOrder();
            $quoteId  = $magentoorder->getQuoteId();
            $quote  = $this->getPaybylinkquote($quoteId);
            /* Start Expiry Code */
            $currentDate = date("Y-m-d H:i:s");
            $orderDate = $orderInfo->getCreatedAt();
            $interval = $this->worldpayhelper->findPblOrderIntervalTime($currentDate, $orderDate);
            $expiryTime = $this->worldpayhelper->getPayByLinkExpiryTime();
            $isResendEnable = $this->worldpayhelper->isPayByLinkResendEnable();
            if ($isResendEnable) {
                $expiryTime = $this->worldpayhelper->calculatePblResendExpiryTime($expiryTime);
            }
            if ($interval >= $expiryTime) {
                $this->wplogger->info('Pay by link expired. Cancelling the order.');
                $this->_checkoutSession->setauthenticatedOrderId($order->getIncrementId());
                $worldPayPayment = $order->getWorldPayPayment();
                $merchantCode = $worldPayPayment->getMerchantId();
                if ($quote->getIsMultiShipping()) {
                    $multiShippingOrders =  $this->wpMultishippingCollection->getMultishippingOrderIds($quoteId);
                    if (count($multiShippingOrders) > 0) {
                        foreach ($multiShippingOrders as $orderId) {
                            $orderObj = $this->orderItemsDetails->loadByIncrementId($orderId);
                            $notice = $this->_getCancellationNoticeForOrder($orderObj);
                            $this->messageManager->addNotice($notice);
                            $this->_applyPaymentUpdate(
                                $this->multishippingStateResponse->createCancelledResponse(
                                    $orderCode,
                                    $merchantCode
                                ),
                                $orderObj
                            );
                        }
                    }
                } else {
                    $notice = $this->_getCancellationNoticeForOrder($order);
                    $this->messageManager->addNotice($notice);
                    $this->_applyPaymentUpdate(
                        $this->wpresponse->createFromPblCancelledResponse($orderCode, $merchantCode),
                        $orderInfo
                    );
                }
                return $this->resultRedirectFactory->create()->setPath('checkout/cart', ['_current' => true]);
            }
            /* End Expiry Code */
            $payment = $magentoorder->getPayment();
            $paymentDetails = [];
            $paymentDetails['additional_data']['cc_type'] = 'ALL';
            $paymentDetails['method'] = 'worldpay_paybylink';
            $authorisationService = $this->paybylink->getAuthorisationService($quote->getStoreId());
            $hppUrl = $authorisationService->authorizeRegenaretPayment(
                $magentoorder,
                $quote,
                $orderCode,
                $quote->getStoreId(),
                $paymentDetails,
                $payment
            );
            if (!empty($hppUrl['payment'])) {
                $this->orderservice->removeAuthorisedOrder();
                return $this->resultRedirectFactory->create()->setPath('checkout/cart', ['_current' => false]);
            }
            return $this->_setredirectpaybylinkhpp($hppUrl);
        } else {
            $this->wplogger->info('Order not found.Redirecting to checkout cart page');
            return $this->resultRedirectFactory->create()->setPath('checkout/cart', ['_current' => true]);
        }
    }

    /**
     * Get Cancellation NoticeFor Order
     *
     * @param array $order
     * @return string
     */
    private function _getCancellationNoticeForOrder($order)
    {

        $incrementId = $order->getIncrementId();
        $message = $incrementId === null
           ? __('Order Cancelled')
           : __('Order #'. $incrementId.' Cancelled');

        return $message;
    }

    /**
     * Apply Payment Update
     *
     * @param string $paymentState
     * @param array $order
     * @return string
     */
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

    /**
     * Get pay by link order
     *
     * @param string $orderKey
     * @return string
     */
    private function _getPaybylinkorder($orderKey)
    {
        return $this->orderservice->loadByIncrementId($orderKey);
    }

    /**
     * Get Pay by link quote
     *
     * @param int $quoteId
     * @return \Magento\Quote\Model\QuoteFactory
     */
    protected function getPaybylinkquote($quoteId)
    {
        return $this->quoteFactory->create()->load($quoteId);
    }
    
    /**
     * Set redirect pay by link hpp
     *
     * @param string $redirectLink
     * @return string
     */
    private function _setredirectpaybylinkhpp($redirectLink)
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($redirectLink);
        return $resultRedirect;
    }
}
