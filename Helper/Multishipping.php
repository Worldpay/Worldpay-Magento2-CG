<?php

/**
 * Sapient 2022
 */

namespace Sapient\Worldpay\Helper;

use Magento\Multishipping\Model\Checkout\Type\Multishipping\State;

class Multishipping
{
    /**
     * @var \Magento\Multishipping\Model\Checkout\Type\Multishipping
     */
    protected $checkout = null;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    public $urlBuilder = null;
    /**
     * Ccvault Payment details
     *
     * @var array
     */
    public $paymentDetails;
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var HistoryFactory
     */
    private $orderHistoryFactory;
    /**
     * Store general exception
     *
     * @var string
     */
    public $generalexception;

    /**
     * Constructor
     *
     * @param \Magento\Multishipping\Model\Checkout\Type\Multishipping\State $state
     * @param \Magento\Framework\Session\SessionManagerInterface $session
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Sapient\Worldpay\Model\Checkout\Type\MultishippingFactory $multishippingCheckoutFactory
     * @param \Sapient\Worldpay\Helper\Data $helper
     * @param \Sapient\Worldpay\Model\Multishipping\OrderFactory $multishippingOrderFactory
     * @param \Sapient\Worldpay\Model\Multishipping\Order $multishippingOrder
     * @param \Sapient\Worldpay\Model\ResourceModel\Multishipping\Order\Collection $multishippingOrderCollection
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Sapient\Worldpay\Model\Payment\UpdateWorldpaymentFactory $updateWorldPayPayment
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Model\Order\Status\HistoryFactory $orderHistoryFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $emailsender
     * @param \Sapient\Worldpay\Helper\CreditCardException $exceptionHelper
     * @param \Sapient\Worldpay\Helper\GeneralException $generalexception
     */
    public function __construct(
        \Magento\Multishipping\Model\Checkout\Type\Multishipping\State $state,
        \Magento\Framework\Session\SessionManagerInterface $session,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Sapient\Worldpay\Model\Checkout\Type\MultishippingFactory $multishippingCheckoutFactory,
        \Sapient\Worldpay\Helper\Data $helper,
        \Sapient\Worldpay\Model\Multishipping\OrderFactory $multishippingOrderFactory,
        \Sapient\Worldpay\Model\Multishipping\Order $multishippingOrder,
        \Sapient\Worldpay\Model\ResourceModel\Multishipping\Order\Collection $multishippingOrderCollection,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\App\Request\Http $request,
        \Sapient\Worldpay\Model\Payment\UpdateWorldpaymentFactory $updateWorldPayPayment,
        \Sapient\Worldpay\Model\Order\Service $orderservice,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Order\Status\HistoryFactory $orderHistoryFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $emailsender,
        \Sapient\Worldpay\Helper\CreditCardException $exceptionHelper,
        \Sapient\Worldpay\Helper\GeneralException $generalexception
    ) {
        $this->state = $state;
        $this->session = $session;
        $this->checkoutSession = $checkoutSession;
        $this->messageManager = $messageManager;
        $this->eventManager = $eventManager;
        $this->multishippingCheckoutFactory = $multishippingCheckoutFactory;
        $this->helper = $helper;
        $this->multishippingOrderFactory = $multishippingOrderFactory;
        $this->multishippingOrder = $multishippingOrder;
        $this->multishippingOrderCollection = $multishippingOrderCollection;
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
        $this->updateWorldPayPayment = $updateWorldPayPayment;
        $this->orderservice = $orderservice;
        $this->orderRepository = $orderRepository;
        $this->orderHistoryFactory = $orderHistoryFactory;
        $this->storeManager = $storeManager;
        $this->emailsender = $emailsender;
        $this->exceptionHelper = $exceptionHelper;
        $this->generalexception = $generalexception;
    }
    /**
     * Get multishipping checkout model
     *
     * @return \Magento\Multishipping\Model\Checkout\Type\Multishipping
     */
    protected function getCheckout()
    {
        if ($this->checkout) {
            return $this->checkout;
        }

        return $this->checkout = $this->multishippingCheckoutFactory->create();
    }

    /**
     * Get final redirect url
     *
     * @param int $quoteId
     * @param string $cc_type
     * @return array
     */
    protected function getFinalRedirectUrl($quoteId, $cc_type)
    {
        $checkout = $this->getCheckout();
        $quote = $this->checkoutSession->getQuote();
        $paymentMethod = $quote->getPayment()->getMethod();
        $response = [];
        $response['method'] = $paymentMethod;
        $response['cc_type'] = $cc_type;
        $this->checkoutSession->unsMultishippingIssue();
        if ($cc_type != 'SAMSUNGPAY-SSL') {
            $this->checkoutSession->unsMultishippingOrderCode();
        }
        if (empty($quoteId)) {
            $response['status'] = 'error';
            $error = 'Could not place order: Your checkout session has expired.';
            $url = $this->getUrl('checkout/cart');
            $response['message'] = $error;
            $response['redirect'] = $url;
        }
        if ($this->session->getAddressErrors()) {
            $response['status'] = 'error';
            $errors = $this->session->getAddressErrors();
            $error = implode('', $errors);
            $response['message'] = $error;
            $this->state->setActiveStep(State::STEP_BILLING);
            $this->session->unsAddressErrors();
        } else {
            $response['status'] = 'success';
            $storeId = $quote->getStoreId();
            $isIframe = $this->helper->isIframeIntegration($storeId);
            $integrationMode = $this->helper->getIntegrationModelByPaymentMethodCode(
                $paymentMethod,
                $storeId
            );
            // Redirect to the success page
            $this->state->setCompleteStep(State::STEP_OVERVIEW);
            $this->state->setActiveStep(State::STEP_SUCCESS);
            if ($cc_type == 'ACH_DIRECT_DEBIT-SSL') {
                $url = $this->getUrl('worldpay/savedcard/Multishippingredirect');
                $response['redirect'] = $url;
            } elseif ($cc_type == 'APPLEPAY-SSL') {
                $url = $this->getUrl('multishipping/checkout/success');
                $response['redirect'] = $url;
            } elseif ($cc_type == 'PAYWITHGOOGLE-SSL') {
                $url = $this->getUrl('worldpay/savedcard/Multishippingredirect');
                $response['redirect'] = $url;
            } elseif ($integrationMode == 'redirect' && !$isIframe) {
                $url = $this->getUrl('worldpay/redirectresult/redirect');
                $response['redirect'] = $url;
            } elseif ($integrationMode == 'direct' && $cc_type == 'savedcard') {
                $url = $this->getUrl('multishipping/checkout/success');
                if ($this->checkoutSession->getauthenticatedOrderId()) {
                    $url = $this->getUrl('worldpay/savedcard/Multishippingredirect');
                }
                $response['redirect'] = $url;
            } elseif ($cc_type == 'savedcard' && !$isIframe) {
                $url = $this->getUrl('multishipping/checkout/success');
                if ($this->checkoutSession->getauthenticatedOrderId()) {
                    $url = $this->getUrl('worldpay/savedcard/Multishippingredirect');
                }
                $response['redirect'] = $url;
            } elseif ($integrationMode == 'direct' && $cc_type != 'savedcard') {
                $url = $this->getUrl('multishipping/checkout/success');
                if ($this->checkoutSession->getauthenticatedOrderId()) {
                    $url = $this->getUrl('worldpay/savedcard/Multishippingredirect');
                }
                $response['redirect'] = $url;
            } elseif ($paymentMethod == 'worldpay_apm') {
                $url = $this->getUrl('worldpay/redirectresult/redirect');
                $response['redirect'] = $url;
            }
        }
        return $response;
    }

    /**
     * Get multishipping collection by quote id
     *
     * @param int $quoteId
     * @param int $orderId
     */
    public function getMultishippingCollections($quoteId, $orderId)
    {
        $multishippingCollections = $this->multishippingOrderCollection->getCollectionByQuoteId($quoteId, $orderId);
        return $multishippingCollections;
    }

    /**
     * Place multishipping order
     *
     * @param int $quoteId
     * @param string $cc_type
     * @return array
     */
    public function placeMultishippingOrder($quoteId, $cc_type)
    {
        $checkout = $this->getCheckout();

        if (empty($quoteId)) {
            return $this->getFinalRedirectUrl($quoteId, $cc_type);
        }

        if (!$checkout->validateMinimumAmount()) {
            $error = $checkout->getMinimumAmountError();
            return $this->getUrl('multishipping/checkout/billing');
        }

        $quote = $this->helper->loadQuoteById($quoteId);

        $results = $checkout->createOrders();
        $orders = $results['orders'];
        $errors = $results['exceptionList'];
        $successful = $failed = [];
        foreach ($orders as $order) {
            if (isset($errors[$order->getIncrementId()])) {
                $failed[] = $order;
            } else {
                $successful[] = $order;
            }
        }
        $checkout->setResultsPageData($quote, $successful, $failed, $errors);
        $addressErrors = $checkout->getAddressErrors($quote, $successful, $failed, $errors);
        return $this->getFinalRedirectUrl($quoteId, $cc_type);
    }
    /**
     * Get url
     *
     * @param string $path
     * @param mixed|null $additionalParams
     * @return string
     */
    public function getUrl($path, $additionalParams = [])
    {
        $params = ['_secure' => $this->request->isSecure()];
        return $this->urlBuilder->getUrl($path, $params + $additionalParams);
    }
    /**
     * Insert Quote and Order Id in Worldpay multishipping table
     *
     * @param Order $mageOrder
     * @param string $orderCode
     * @param bool $isOrg
     */
    public function _createWorldpayMultishipping($mageOrder, $orderCode, $isOrg = false)
    {
        $model = $this->multishippingOrderFactory->create();
        if ($isOrg) {
            $model->setIsOrigOrderId(true);
        }
        $model->setQuoteId($mageOrder->getQuoteId());
        $model->setOrderId($mageOrder->getIncrementId());
        $model->setWorldpayOrderId($orderCode);
        $model->save();
    }
    
    /**
     * Get worldpay Order Id in Worldpay multishipping table
     *
     * @param string $orderCode
     * @return array
     */
    public function getOrgWorldpayId($orderCode)
    {
        return $this->multishippingOrderCollection->getOriginalWorldpayOrderId($orderCode);
    }

    /**
     * Copy Worldpay payment
     *
     * @param int $orgOrderId
     * @param string $orderCode
     * @param mixed|null $type
     */
    public function _copyWorldPayPayment($orgOrderId, $orderCode, $type = null)
    {
        $this->updateWorldPayPayment->create()->copyWorldpayPaymentForMultiShipping($orgOrderId, $orderCode, $type);
    }
    /**
     * Get multishipping orders
     *
     * @param int $orderId
     * @param int $quoteId
     */
    public function getMultishippingOrders($orderId, $quoteId)
    {
        return $this->multishippingOrderCollection->getCollectionByOrderAndQuoteId($orderId, $quoteId);
    }
    
    /**
     * Check if existing order was 3DS
     *
     * @return bool
     */
    public function is3dsOrder()
    {
        if ($this->checkoutSession->get3Ds2Params() || $this->checkoutSession->get3DSecureParams()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Add transaction
     *
     * @param Payment $payment
     * @param Order $order
     * @return bool
     */
    public function _addTransaction($payment, $order)
    {
        $payment->setTransactionId(time());
        $payment->setIsTransactionClosed(1);
        $magentoorder = $order->getOrder();
        $amount = $this->formatAmount($magentoorder->getBaseTotalDue());
        $transaction = $payment->addTransaction('authorization', null, false, null);
        $transaction->save();
        $order = $this->orderRepository->get($order->getId());
        if ($order->canComment()) {
            $history = $this->orderHistoryFactory->create()
                ->setStatus($order->getStatus()) // Update status when passing $comment parameter
                ->setEntityName(\Magento\Sales\Model\Order::ENTITY) // Set the entity name for order
                ->setComment(
                    __('Authorized amount of %1.', $amount)
                ); // Set your comment
            $history->setIsCustomerNotified(false) // Enable Notify your customers via email
                ->setIsVisibleOnFront(true); // Enable order comment visible on sales order details

            $order->addStatusHistory($history); // Add your comment to order
            $this->orderRepository->save($order);
        }
    }
    /**
     * Modify the authroised status for ms orders
     *
     * @param Order $order
     */
    public function authrorisedMultishippingOrders($order)
    {
        $quote_id = $order->getQuoteId();
        $inc_id = $order->getIncrementId();
        $msOrders = $this->getMultishippingOrders($inc_id, $quote_id);
        if ($msOrders->count() > 0) {
            foreach ($msOrders as $msOrder) {
                $order_id = $msOrder->getOrderId();
                $other_order = $this->orderservice->getByIncrementId($order_id);
                $type = true;
                $this->_copyWorldPayPayment($inc_id, $order_id, $type);
                $this->_addTransaction($other_order->getPayment(), $other_order);
            }
            $order = $this->orderservice->getByIncrementId($inc_id);
        }
    }
    /**
     * Modify the cancel status for ms orders
     *
     * @param Order $order
     */
    public function cancelMultishippingOrders($order)
    {
        $quote_id = $order->getQuoteId();
        $inc_id = $order->getIncrementId();
        $msOrders = $this->getMultishippingOrders($inc_id, $quote_id);
        if ($msOrders->count() > 0) {
            foreach ($msOrders as $msOrder) {
                $order_id = $msOrder->getOrderId();
                $other_order = $this->orderservice->getByIncrementId($order_id);
                $type = true;
                $this->_copyWorldPayPayment($inc_id, $order_id, $type);
                $other_order->cancel();
            }
            $order = $this->orderservice->getByIncrementId($inc_id);
        }
    }
    /**
     * Modify the pending status for ms orders
     *
     * @param Order $order
     */
    public function pendingMultishippingOrders($order)
    {
        $quote_id = $order->getQuoteId();
        $inc_id = $order->getIncrementId();
        $msOrders = $this->getMultishippingOrders($inc_id, $quote_id);
        if ($msOrders->count() > 0) {
            foreach ($msOrders as $msOrder) {
                $order_id = $msOrder->getOrderId();
                $other_order = $this->orderservice->getByIncrementId($order_id);
                $type = true;
                $this->_copyWorldPayPayment($inc_id, $order_id, $type);
                $other_order->pendingPayment();
            }
        }
    }
    /**
     * Modify the status for ms orders
     *
     * @param Order $order
     */
    public function defaultUpdateMultishippingOrders($order)
    {
        $quote_id = $order->getQuoteId();
        $inc_id = $order->getIncrementId();
        $msOrders = $this->getMultishippingOrders($inc_id, $quote_id);
        if ($msOrders->count() > 0) {
            foreach ($msOrders as $msOrder) {
                $order_id = $msOrder->getOrderId();
                $other_order = $this->orderservice->getByIncrementId($order_id);
                $type = true;
                $this->_copyWorldPayPayment($inc_id, $order_id, $type);
            }
        }
    }
    /**
     * Format amount
     *
     * @param float $amount
     * @param int|null $storeId
     * @return string
     */
    public function formatAmount($amount, $storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getStoreId();
        }
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
        return $this->storeManager->getWebsite($websiteId)->getBaseCurrency()->formatPrecision(
            $amount,
            2
        );
    }
    /**
     * Get Store Id
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }
    /**
     * Check if integration mode is redirect
     *
     * @param \Sapient\Worldpay\Model\Order $order
     * @return bool
     */
    public function _isRedirectIntegrationMode(\Sapient\Worldpay\Model\Order $order)
    {
        return $this->helper->getIntegrationModelByPaymentMethodCode(
            $order->getPaymentMethodCode(),
            $order->getStoreId()
        )
            === \Sapient\Worldpay\Model\PaymentMethods\AbstractMethod::REDIRECT_MODEL;
    }
    /**
     * Convert worldpay amount to magento amount
     *
     * @param float $amount
     * @return int
     */
    protected function _amountAsInt($amount)
    {
        return round($amount, 2, PHP_ROUND_HALF_EVEN) / pow(10, 2);
    }
    /**
     * Set multishipping issue session
     *
     * @param string $orderCode
     */
    public function setMultishippingIssue($orderCode)
    {
        $this->checkoutSession->setMultishippingIssue(true);
        $this->setMultishippingOrderCode($orderCode);
    }

    /**
     * Set multishipping order code session
     *
     * @param string $orderCode
     */
    public function setMultishippingOrderCode($orderCode)
    {
        if (empty($this->checkoutSession->getMultishippingOrderCode())) {
            $this->checkoutSession->setMultishippingOrderCode($orderCode);
        }
    }
    
    /**
     * Check if multishipping has issue
     */
    public function checkIsMultishippingIssue()
    {
        if ($this->checkoutSession->getMultishippingIssue()) {
            throw new \Magento\Framework\Exception\LocalizedException(__(''));
        }
    }
    /**
     * Get Quote Data's
     *
     * @param int|null $quoteId
     * @return Quote
     */
    public function getQuote($quoteId = null)
    {
        $quote = $this->_checkoutSession->getQuote();
        return $quote;
    }
    /**
     *  Check if Quote is Multishipping
     *
     * @param Quote $quote
     * @return bool
     */
    public function isMultiShipping($quote = null)
    {
        if (empty($quote)) {
            $quote = $this->getQuote();
        }

        if (empty($quote)) {
            return false;
        }

        return (bool)$quote->getIsMultiShipping();
    }
    /**
     * Perform multishipping messaging option
     *
     * @param Order $order
     * @param string $type
     */
    public function performMultishippingMessage($order, $type)
    {
        $worldpaypayment = $order->getWorldPayPayment();
        if ($worldpaypayment->getIsMultishippingOrder()) {
            $order_inc_id = $order->getIncrementId();
            $order_quote_id = $order->getQuoteId();
            $multishippingOrders = $this->getMultishippingOrders($order_inc_id, $order_quote_id);
            if (!empty($multishippingOrders)) {
                foreach ($multishippingOrders as $multishippingOrder) {
                    $order_id = $multishippingOrder->getOrderId();
                    $order = $this->orderservice->getByIncrementId($order_id);
                    $magentoorder = $order->getOrder();
                    $notice = $this->_getNoticeForOrder($magentoorder, $type);
                    $this->messageManager->addNotice($notice);
                    if ($type == 'failure') {
                        $this->emailsender->authorisedEmailSend($magentoorder, false);
                    }
                }
            }
        }
    }
    /**
     * Get Cancellation / Error / Failure Notice For Order
     *
     * @param array $order
     * @param string $type
     * @return string
     */
    private function _getNoticeForOrder($order, $type)
    {
        $incrementId = $order->getIncrementId();
        if ($type == 'cancel') {
            $message = $incrementId === null
            ? __('Order Cancelled')
            : __('Order #'. $incrementId.' Cancelled');
        } elseif ($type == 'error') {
            $message = __('Order #'.$incrementId.$this->exceptionHelper->getConfigValue('CCAM7'));
        } elseif ($type == 'failure') {
            $message = __('Order #'.$incrementId.' Failed');
        }
        return $message;
    }

    /**
     * Get order code from auth session
     */
    public function getOrderCodeFromSession()
    {
        return $this->checkoutSession->getMultishippingOrderCode();
    }
    /**
     * Retrieve merchant detail value from config
     *
     * @param Order $order
     * @param int $paymenttype
     * @param null|string|bool|int $scope
     * @return float|null
     */
    public function getConfigValue($order, $paymenttype, $scope = null)
    {
        $storeid = $order->getStoreId();
        $store = $this->storeManager->getStore($storeid)->getCode();
        return $this->generalexception->getConfigValue($paymenttype, $store, $scope);
    }
}
