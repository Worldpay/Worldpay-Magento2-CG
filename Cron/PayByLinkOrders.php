<?php
/**
 * @copyright 2023 Sapient
 */
namespace Sapient\Worldpay\Cron;

use \Magento\Framework\App\ObjectManager;
use \Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;
use Exception;

/**
 * Model for Pay by link orders based on configuration set by admin
 */
class PayByLinkOrders
{

    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    protected $_logger;
    /**
     * @var _paymentUpdate
     */
    private $_paymentUpdate;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Sapient\Worldpay\Model\PayByLink\Order
     */
    protected $pblOrder;

    /**
     * @var \Sapient\Worldpay\Helper\Data
     */
    protected $worldpayhelper;
    
    /**
     * @var \Sapient\Worldpay\Model\Payment\Service
     */
    protected $paymentservice;

    /**
     * @var \Sapient\Worldpay\Model\Order\Service
     */
    protected $orderservice;

    /**
     * @var \Sapient\Worldpay\Model\Payment\WpResponse
     */
    protected $wpresponse;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $pricingHelper;

    /**
     * @var \Magento\Customer\Model\Address\Config
     */
    protected $_addressConfig;

    /**
     * @var \Sapient\Worldpay\Helper\SendPayByLinkEmail
     */
    protected $payByLinkEmail;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Sapient\Worldpay\Model\ResourceModel\Multishipping\Order\Collection
     */
    protected $wpMultishippingCollection;

    /**
     * @var \Sapient\Worldpay\Model\Payment\MultishippingStateResponse
     */
    protected $multishippingStateResponse;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $orderItemsDetails;
    /**
     * Constructor
     *
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Helper\Data $worldpayhelper
     * @param \Sapient\Worldpay\Model\Payment\Service $paymentservice
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice
     * @param \Sapient\Worldpay\Model\Payment\WpResponse $wpresponse
     * @param \Magento\Store\Model\StoreManagerInterface $_storeManager
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
     * @param \Magento\Customer\Model\Address\Config $addressConfig
     * @param \Sapient\Worldpay\Helper\SendPayByLinkEmail $payByLinkEmail
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Sapient\Worldpay\Model\ResourceModel\Multishipping\Order\Collection $wpMultishippingCollection
     * @param \Sapient\Worldpay\Model\Payment\MultishippingStateResponse $multishippingStateResponse
     * @param \Magento\Sales\Model\Order $orderItemsDetails
     * @param \Sapient\Worldpay\Model\PayByLink\Order $pblOrder
     */
    public function __construct(
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Helper\Data $worldpayhelper,
        \Sapient\Worldpay\Model\Payment\Service $paymentservice,
        \Sapient\Worldpay\Model\Order\Service $orderservice,
        \Sapient\Worldpay\Model\Payment\WpResponse $wpresponse,
        \Magento\Store\Model\StoreManagerInterface $_storeManager,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Magento\Customer\Model\Address\Config $addressConfig,
        \Sapient\Worldpay\Helper\SendPayByLinkEmail $payByLinkEmail,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Sapient\Worldpay\Model\ResourceModel\Multishipping\Order\Collection $wpMultishippingCollection,
        \Sapient\Worldpay\Model\Payment\MultishippingStateResponse $multishippingStateResponse,
        \Magento\Sales\Model\Order $orderItemsDetails,
        \Sapient\Worldpay\Model\PayByLink\Order $pblOrder
    ) {
        $this->_logger = $wplogger;
        $this->worldpayhelper = $worldpayhelper;
        $this->paymentservice = $paymentservice;
        $this->orderservice = $orderservice;
        $this->wpresponse = $wpresponse;
        $this->_storeManager = $_storeManager;
        $this->pricingHelper = $pricingHelper;
        $this->_addressConfig = $addressConfig;
        $this->payByLinkEmail = $payByLinkEmail;
        $this->quoteRepository = $quoteRepository;
        $this->wpMultishippingCollection = $wpMultishippingCollection;
        $this->multishippingStateResponse = $multishippingStateResponse;
        $this->orderItemsDetails = $orderItemsDetails;
        $this->pblOrder = $pblOrder;
    }

    /**
     * Get the list of orders to be expired or resend
     */
    public function execute()
    {
        $this->_logger->info('Pay by link orders executed on - '.date('Y-m-d H:i:s'));
        $curDate = date("Y-m-d H:i:s");
        $expiryTime = $this->worldpayhelper->getPayByLinkExpiryTime();
        $isResendEnable = $this->worldpayhelper->isPayByLinkResendEnable();
        $resendExpiryTime = '';
        if ($isResendEnable) {
            $resendExpiryTime = $this->worldpayhelper->calculatePblResendExpiryTime($expiryTime);
        }
        $orderIds = $this->pblOrder->getPayByLinkOrderIds($curDate, $expiryTime, $resendExpiryTime);
        if (!empty($orderIds)) {
            foreach ($orderIds as $order) {
                $createdAt = $order['created_at'];
                $currentDate = date('Y-m-d H:i:s');
                $interval = $this->worldpayhelper->findPblOrderIntervalTime($currentDate, $createdAt);
                $orderIncrementId = $order['increment_id'];
                $order = $this->orderservice->getByIncrementId($orderIncrementId);
                $magentoorder = $order->getOrder();
                $worldPayPayment = $order->getWorldPayPayment();
                $orderCode = $worldPayPayment->getWorldpayOrderId();
                $merchantCode = $worldPayPayment->getMerchantId();
                $quoteId = $order->getQuoteId();
                $quoteObj = $this->quoteRepository->get($quoteId);
                if (empty($resendExpiryTime) || $interval == $resendExpiryTime) {
                    /* Cancel Order */
                    $this->_logger->info('Pay by link expired. Cancelling the order.');
                    if ($quoteObj->getIsMultiShipping()) {
                        $this->cancelMultiShippingOrders(
                            $quoteObj,
                            $orderCode,
                            $merchantCode
                        );
                    } else {
                        $this->_applyPaymentUpdate(
                            $this->wpresponse->createFromPblCancelledResponse($orderCode, $merchantCode),
                            $magentoorder
                        );
                    }
                } else {
                    /* Resend Mail */
                    $this->_logger->info('Pay by link resend mail.');
                    $pblOrderCodeUrl = 'worldpay/paybylink/process?orderkey='.$orderCode;
                    $paybylink_url = $this->_storeManager->getStore()
                              ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB).$pblOrderCodeUrl;
                    $grandTotal = $this->getFormatGrandTotal($magentoorder);
                    $address = $magentoorder->getShippingAddress();
                    if (empty($address)) {
                        $address = $magentoorder->getBillingAddress();
                    }
                    $formatedAddress = $this->getFormatAddressByCode($address->getData());
                    $customerEmail = $magentoorder->getCustomerEmail();
                    $csName = $magentoorder->getCustomerFirstName().' '.$magentoorder->getCustomerLastName();
                    $payByLinkParams['paybylink_url'] = $paybylink_url;
                    $payByLinkParams['orderId'] = $magentoorder->getIncrementId();
                    $payByLinkParams['order_total'] = $grandTotal;
                    $payByLinkParams['formated_shipping'] = $formatedAddress;
                    $payByLinkParams['customerName'] = $csName;
                    $payByLinkParams['customerEmail'] = $customerEmail;
                    $payByLinkParams['is_resend'] = true;
                    $payByLinkParams['is_multishipping'] = false;
                    if ($quoteObj->getIsMultiShipping()) {
                        $payByLinkParams['is_multishipping'] = true;
                    }
                    $this->payByLinkEmail->sendPayBylinkEmail($payByLinkParams);
                }
            }
        }
        return $this;
    }
    /**
     * Cancel Multishipping Orders
     *
     * @param object $quoteObj
     * @param string $orderCode
     * @param string $merchantCode
     */
    private function cancelMultiShippingOrders($quoteObj, $orderCode, $merchantCode)
    {
        $multiShippingOrders =  $this->wpMultishippingCollection->getMultishippingOrderIds($quoteObj->getId());
        if (count($multiShippingOrders) > 0) {
            foreach ($multiShippingOrders as $orderId) {
                $orderObj = $this->orderItemsDetails->loadByIncrementId($orderId);
                $this->_applyPaymentUpdate(
                    $this->multishippingStateResponse->createCancelledResponse(
                        $orderCode,
                        $merchantCode
                    ),
                    $orderObj
                );
            }
        }
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
            $this->_logger->error($e->getMessage());
        }
    }
    /**
     * Format Grand Total
     *
     * @param \Magento\Sales\Model\Order $mageOrder
     * @return string
     */
    public function getFormatGrandTotal($mageOrder)
    {
        if ($mageOrder->getGrandTotal()) {
            $formattedTotal = $this->pricingHelper->currency($mageOrder->getGrandTotal(), true, false);
            return $formattedTotal;
        }
    }
    /**
     * Format Shipping Address
     *
     * @param array $address
     * @return array
     */

    public function getFormatAddressByCode($address)
    {
        $renderer = $this->_addressConfig->getFormatByCode('html')->getRenderer();
        return $renderer->renderArray($address);
    }
}
