<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Notification;

use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\App\RequestInterface;
use Exception;
use Sapient\Worldpay\Logger\WorldpayLogger;
use Sapient\Worldpay\Model\HistoryNotificationFactory;
use Sapient\Worldpay\Model\Order\Service as OrderService;
use Sapient\Worldpay\Model\Payment\Service as PaymentService;
use Sapient\Worldpay\Model\PaymentMethods\PaymentOperations;
use Sapient\Worldpay\Model\Token\StateXml;
use Sapient\Worldpay\Model\Token\WorldpayToken;

class Index implements HttpPostActionInterface, HttpGetActionInterface
{
    public const RESPONSE_OK = '[OK]';
    public const RESPONSE_FAILED = '[FAILED]';

    protected PageFactory $pageFactory;
    protected string|bool|null $_rawBody = null;
    protected HistoryNotificationFactory $historyNotification;
    protected PaymentOperations $abstractMethod;
    protected File $fileDriver;
    protected WorldpayLogger $wplogger;
    protected PaymentService $paymentservice;
    protected OrderService $orderservice;
    protected JsonFactory $resultJsonFactory;
    protected WorldpayToken $worldpaytoken;
    protected mixed $_paymentUpdate;
    protected mixed $_order;
    protected Context $context;
    protected RequestInterface $request;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        WorldpayLogger $wplogger,
        PaymentService $paymentservice,
        WorldpayToken $worldpaytoken,
        OrderService $orderservice,
        PaymentOperations $abstractMethod,
        HistoryNotificationFactory $historyNotification,
        File $fileDriver
    ) {
        $this->context = $context;
        $this->request = $context->getRequest();
        $this->wplogger = $wplogger;
        $this->paymentservice = $paymentservice;
        $this->orderservice = $orderservice;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->historyNotification = $historyNotification;
        $this->worldpaytoken = $worldpaytoken;
        $this->abstractMethod = $abstractMethod;
        $this->fileDriver = $fileDriver;
    }

    public function execute(): Json
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
                return $this->_returnFailure();
            }
        } catch (Exception $e) {
            $this->wplogger->error($e->getMessage());
            if ($e->getMessage() == 'invalid state transition' || $e->getMessage() == 'same state'
                    || $e->getMessage() == 'Notification received for Partial Capture') {
                return $this->_returnOk();
            } else {
                return $this->_returnFailure();
            }
        }
    }

    public function _getRawBody(): string
    {
        if (null === $this->_rawBody) {
            $body = $this->request->getContent();
            if (strlen(trim($body)) > 0) {
                $this->_rawBody = $body;
            } else {
                $this->_rawBody = false;
            }
        }
        return $this->_rawBody;
    }

    private function _createPaymentUpdate(\SimpleXMLElement $xmlRequest): void
    {
        $this->wplogger->info('########## Received notification ##########');
        $this->wplogger->info($this->_getRawBody());
        try {
            $this->paymentservice->getPaymentUpdateXmlForNotification($this->_getRawBody());
        } catch (Exception $e) {
            $this->wplogger->error($e->getMessage());
            throw new LocalizedException(
                __($e->getMessage())
            );
        }
        $this->_paymentUpdate = $this->paymentservice
            ->createPaymentUpdateFromWorldPayXml($xmlRequest);

        $this->_logNotification();
    }

    private function _logNotification(): void
    {
        $this->wplogger->info('########## Payment update of type: ' .
                get_class($this->_paymentUpdate). ' created ##########');
    }

    private function _loadOrder(): void
    {
        $orderCode = $this->_paymentUpdate->getTargetOrderCode();
        $orderIncrementId = current(explode('-', $orderCode));

        $this->_order = $this->orderservice->getByIncrementId($orderIncrementId);
    }

    private function _tryToApplyPaymentUpdate(): void
    {
        try {
            $this->_paymentUpdate->apply($this->_order->getPayment(), $this->_order);
        } catch (Exception $e) {
            $this->wplogger->error($e->getMessage());
            throw new LocalizedException(
                __($e->getMessage())
            );
        }
    }

    private function _applyTokenUpdate(\SimpleXMLElement $xmlRequest): void
    {
        $tokenService = $this->worldpaytoken;
        $tokenService->updateOrInsertToken(
            new StateXml($xmlRequest),
            $this->_order->getPayment(),
            $this->_order->getOrder()->getCustomerId()
        );
    }

    public function _returnOk(): Json
    {
        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setHttpResponseCode(200);
        $resultJson->setData(self::RESPONSE_OK);
        return $resultJson;
    }

    public function _returnFailure(): Json
    {
        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setHttpResponseCode(500);
        $resultJson->setData(self::RESPONSE_FAILED);
        return $resultJson;
    }

    private function updateNotification(\SimpleXMLElement $xml): void
    {
        $statusNode = $xml->notify->orderStatusEvent;
        $orderCode = "";
        $paymentStatus = "";
        $reference = "";
        if (isset($statusNode['orderCode'])) {
            list($orderCode, $ordercode_last) = explode("-", $statusNode['orderCode']);
        }
        if (isset($statusNode->payment->lastEvent)) {
            $paymentStatus = $statusNode->payment->lastEvent;
        }
        if (isset($statusNode->payment->reference)) {
            $reference = $statusNode->payment->reference;
        }
        $hn = $this->historyNotification->create();
        $hn->setData('status', $paymentStatus);
        $hn->setData('order_id', trim($orderCode));
        $hn->setData('reference', trim($reference));
        $hn->save();
    }

    private function _updateOrderStatus(): void
    {
        $this->abstractMethod->updateOrderStatusForVoidSale($this->_order);
        $this->abstractMethod->updateOrderStatusForCancelOrder($this->_order);
    }
}
