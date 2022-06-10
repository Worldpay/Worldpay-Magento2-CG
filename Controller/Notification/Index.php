<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Notification;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Exception;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;

    /**
     * @var string
     */
    protected $_rawBody;
    /**
     * [$fileDriver description]
     * @var [type]
     */
    protected $fileDriver;
    /**
     * @var \Sapient\Worldpay\Model\HistoryNotificationFactory
     */
    protected $historyNotification;
    
    /**
     * @var \Sapient\Worldpay\Model\PaymentMethods\PaymentOperations
     */
    private $abstractMethod;
    /**
     * @var RESPONSE_OK
     */
    public const RESPONSE_OK = '[OK]';
    /**
     * @var RESPONSE_FAILED
     */
    public const RESPONSE_FAILED = '[FAILED]';
    
    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Model\Payment\Service $paymentservice
     * @param \Sapient\Worldpay\Model\Token\WorldpayToken $worldpaytoken
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice
     * @param \Sapient\Worldpay\Model\PaymentMethods\PaymentOperations $abstractMethod
     * @param \Sapient\Worldpay\Model\HistoryNotificationFactory $historyNotification
     * @param \Magento\Framework\Filesystem\DriverInterface $fileDriver
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Payment\Service $paymentservice,
        \Sapient\Worldpay\Model\Token\WorldpayToken $worldpaytoken,
        \Sapient\Worldpay\Model\Order\Service $orderservice,
        \Sapient\Worldpay\Model\PaymentMethods\PaymentOperations $abstractMethod,
        \Sapient\Worldpay\Model\HistoryNotificationFactory $historyNotification,
        \Magento\Framework\Filesystem\DriverInterface $fileDriver
    ) {
        parent::__construct($context);
        $this->wplogger = $wplogger;
        $this->paymentservice = $paymentservice;
        $this->orderservice = $orderservice;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->historyNotification = $historyNotification;
        $this->worldpaytoken = $worldpaytoken;
        $this->abstractMethod = $abstractMethod;
        $this->fileDriver = $fileDriver;
    }

    /**
     * Execute action
     *
     * @return string
     * @throws \Exception
     */
    public function execute()
    {
        $this->wplogger->info('notification index url hit');
        try {
            $xmlRequest = simplexml_load_string($this->_getRawBody());

            if ($xmlRequest instanceof \SimpleXMLElement) {
                $this->updateNotification($xmlRequest);
                $this->_createPaymentUpdate($xmlRequest);
                $this->_loadOrder();
                $this->_tryToApplyPaymentUpdate();
                $this->_updateOrderStatus();
                $this->_applyTokenUpdate($xmlRequest);
                return $this->_returnOk();
            } else {

                $this->wplogger->error('Not a valid xml');
            }
        } catch (Exception $e) {
            $this->wplogger->error($e->getMessage());
            if ($e->getMessage() == 'invalid state transition' || $e->getMessage() == 'same state'
                    || $e->getMessage() == 'Notification received for Partial Captutre') {
                return $this->_returnOk();
            } else {
                return $this->_returnFailure();
            }
        }
    }

    /**
     * Return the raw body of the request
     *
     * @return false|string Raw body, or false if not present
     */
    public function _getRawBody()
    {
        if (null === $this->_rawBody) {
            $body = $this->fileDriver->fileGetContents('php://input');

            if (strlen(trim($body)) > 0) {
                $this->_rawBody = $body;
            } else {
                $this->_rawBody = false;
            }
        }
        return $this->_rawBody;
    }

    /**
     * Create payment update
     *
     * @param SimpleXMLElement $xmlRequest
     */
    private function _createPaymentUpdate($xmlRequest)
    {
        $this->wplogger->info('########## Received notification ##########');
        $this->wplogger->info($this->_getRawBody());
        $this->paymentservice->getPaymentUpdateXmlForNotification($this->_getRawBody());
        $this->_paymentUpdate = $this->paymentservice
            ->createPaymentUpdateFromWorldPayXml($xmlRequest);

        $this->_logNotification();
    }

    /**
     * Log notification
     */
    private function _logNotification()
    {
//        $this->wplogger->info('########## Received notification ##########');
//        $this->wplogger->info($this->_getRawBody());
        $this->wplogger->info('########## Payment update of type: ' .
                get_class($this->_paymentUpdate). ' created ##########');
    }

    /**
     * Get order code
     */
    private function _loadOrder()
    {
        $orderCode = $this->_paymentUpdate->getTargetOrderCode();
        $orderIncrementId = current(explode('-', $orderCode));

        $this->_order = $this->orderservice->getByIncrementId($orderIncrementId);
    }

    /**
     * Try to apply payment update
     *
     * @throws Exception
     */
    private function _tryToApplyPaymentUpdate()
    {
        try {
            $this->_paymentUpdate->apply($this->_order->getPayment(), $this->_order);
        } catch (Exception $e) {
            $this->wplogger->error($e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }

    /**
     * Apply token update
     *
     * @param SimpleXMLElement $xmlRequest
     */
    private function _applyTokenUpdate($xmlRequest)
    {
        $tokenService = $this->worldpaytoken;
        $tokenService->updateOrInsertToken(
            new \Sapient\Worldpay\Model\Token\StateXml($xmlRequest),
            $this->_order->getPayment(),
            $this->_order->getOrder()->getCustomerId()
        );
    }

    /**
     * Set response code 200
     *
     * @return string
     */
    public function _returnOk()
    {
        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setHttpResponseCode(200);
        $resultJson->setData(self::RESPONSE_OK);
        return $resultJson;
    }

    /**
     * Set response code 500
     *
     * @return string
     */
    public function _returnFailure()
    {
        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setHttpResponseCode(500);
        $resultJson->setData(self::RESPONSE_FAILED);
        return $resultJson;
    }

    /**
     * Save Notification
     *
     * @param SimpleXMLElement $xml
     */
    private function updateNotification($xml)
    {
        $statusNode=$xml->notify->orderStatusEvent;
        $orderCode="";
        $paymentStatus="";
        if (isset($statusNode['orderCode'])) {
            list($orderCode, $ordercode_last) = explode("-", $statusNode['orderCode']);
        }
        if (isset($statusNode->payment->lastEvent)) {
                $paymentStatus=$statusNode->payment->lastEvent;
        }
        $hn = $this->historyNotification->create();
        $hn->setData('status', $paymentStatus);
        $hn->setData('order_id', trim($orderCode));
        $hn->save();
    }
    
    /**
     * Update order status
     */
    private function _updateOrderStatus()
    {
        $this->abstractMethod->updateOrderStatusForVoidSale($this->_order);
        $this->abstractMethod->updateOrderStatusForCancelOrder($this->_order);
    }
}
