<?php
/**
 * @copyright 2024 Sapient
 */
namespace Sapient\Worldpay\Cron;

use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\OrderFactory;
use Sapient\Worldpay\Model\Payment\StateInterface;
use Sapient\Worldpay\Model\WorldpaymentFactory;
use Sapient\Worldpay\Model\Payment\Service as PaymentService;
use Sapient\Worldpay\Model\Request\PaymentServiceRequest;
use Sapient\Worldpay\Model\Order\Service;
use Sapient\Worldpay\Helper\Data;
use Sapient\Worldpay\Logger\WorldpayLogger;
use Magento\Store\Model\StoreManagerInterface;

class PendingOrderCleanup
{
    /**
     * @var CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var WorldpaymentFactory
     */
    protected $worldpaypayment;

    /**
     * @var Service
     */
    protected $orderservice;

    /**
     * @var \Sapient\Worldpay\Model\Payment\Service
     */
    protected $paymentservice;

    /**
     * @var \Sapient\Worldpay\Model\Request\PaymentServiceRequest
     */

    protected $paymentservicerequest;
    /**
     * @var Data
     */
    private $worldpayhelper;

    /**
     * @var WorldpayLogger
     */
    protected $_logger;

    /**
     * @var $_order
     */
    private $_order;

    /**
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     *
     * @param CollectionFactory $orderCollectionFactory
     * @param OrderFactory $orderFactory
     * @param WorldpaymentFactory $worldpaypayment
     * @param PaymentService $paymentservice
     * @param PaymentServiceRequest $paymentservicerequest
     * @param Service $orderservice
     * @param Data $worldpayhelper
     * @param WorldpayLogger $wplogger
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CollectionFactory $orderCollectionFactory,
        OrderFactory $orderFactory,
        WorldpaymentFactory $worldpaypayment,
        PaymentService $paymentservice,
        PaymentServiceRequest $paymentservicerequest,
        Service $orderservice,
        Data $worldpayhelper,
        WorldpayLogger $wplogger,
        StoreManagerInterface $storeManager
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderFactory = $orderFactory;
        $this->worldpaypayment = $worldpaypayment;
        $this->paymentservice = $paymentservice;
        $this->paymentservicerequest = $paymentservicerequest;
        $this->orderservice = $orderservice;
        $this->worldpayhelper = $worldpayhelper;
        $this->_logger = $wplogger;
        $this->storeManager = $storeManager;
    }

   /**
    * Cancel pending order cron
    */

    public function execute()
    {
        $storeId = $this->storeManager->getStore()->getId();
        $orderCleanupenable = $this->worldpayhelper->getOrderCleanupEnable($storeId);
        if (!$orderCleanupenable) {
            return;
        }
        $timeDuration = $this->worldpayhelper->getOrderCleanupOption();
        $this->_logger->info('Pending order clean up cron started');
        $pendingOrderCollection = $this->getPendingOrders($timeDuration);
        foreach ($pendingOrderCollection as $order) {
            if (!empty($order->getOrderId())) {
                $this->_getPaymentDetailsXmlForOrder(
                    $this->orderservice->getByIncrementId($order->getOrderId())
                );
               $this->applyCancel($order->getOrderId());
            }
        }
        $this->_logger->info('Pending order clean up cron finished');
    }

   /**
    * Filter the collection to fetch pending orders
    *
    * @param string $timeDuration
    * @return Collection
    */
    public function getPendingOrders($timeDuration)
    {
        $collection = $this->worldpaypayment->create()
            ->getsentforAuthOrderCollection($timeDuration);
        return $collection;
    }

    /**
     * Get Payment Details Xml For Order
     *
     * @param Sapient\Worldpay\Model\Order $order
     * @return array
     */
    public function _getPaymentDetailsXmlForOrder(\Sapient\Worldpay\Model\Order $order)
    {
        $worldPayPayment = $order->getWorldPayPayment();
        if (!$worldPayPayment) {
            return false;
        }
        $interactionType = '';
        $worldPayOrder = $order->getOrder();
        $response = $this->paymentservicerequest->inquiry(
            $worldPayPayment->getMerchantId(),
            $worldPayPayment->getWorldpayOrderId(),
            $worldPayOrder->getStoreId(),
            $order->getPaymentMethodCode(),
            $worldPayPayment->getPaymentType(),
            $interactionType,
            true
        );
        $paymentService = new \SimpleXmlElement($response);
        $error = $paymentService->xpath('//error');
        if(!empty($error)){
            if ($error[0]['code'] == 5) {
                $this->applyCancel($worldPayPayment->getWorldpayOrderId());
            }
        }
    }

    /**
     * Apply
     *
     * @param string $orderId
     */
    public function applyCancel($orderId)
    {
        if (!empty($orderId)) {
            $paymentModel = $this->worldpaypayment->create()->loadByWorldpayOrderId($orderId);
            //$isRedirectOrder = $paymentModel->getPaymentModel();
            $wpPaymentStatus = $paymentModel->getPaymentStatus();
            if ($wpPaymentStatus == StateInterface::STATUS_SENT_FOR_AUTHORISATION ||
                $wpPaymentStatus == StateInterface::STATUS_WAITING_FOR_SHOPPER) {
                try {
                    $paymentModel->setData(
                        'payment_status',
                        StateInterface::STATUS_CANCELLED
                    );
                    if ($paymentModel->save()) {
                        $this->updateOrderCancel($paymentModel->getOrderId());
                    }
                    $this->_logger->info('Worldpay order id - '.$paymentModel->getWorldpayOrderId().' cancelled');

                } catch (\Exception $e) {
                    $this->_logger->info(
                        'Unable to update payment status of order id - '
                        .$paymentModel->getOrderId()
                        .' Exception message:'.$e->getMessage()
                    );
                }
            }
        }
    }

    /**
     * Cancel magento order
     *
     * @param string $orderId
     */
    public function updateOrderCancel($orderId)
    {
        try {
            $order = $this->orderFactory->create()->loadByIncrementId($orderId);
            $order->addStatusHistoryComment(
                'Order has been canceled because could not find payment'
            )
                ->setIsVisibleOnFront(true);
            $order->cancel()->save();
            $this->_logger->info('update oder: '.$orderId);
        } catch (\Exception $e) {
            $this->_logger->info('Unable to cancel order id - '.$orderId.' Exception message:'.$e->getMessage());
        }
    }
}
