<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment;

class Service
{
    public const INTERACTION_TYPE_MS = "ECOM_MULTISHIPPING";
    /**
     * @var \Sapient\Worldpay\Model\Request\PaymentServiceRequest
     */
    protected $_paymentServiceRequest;
    /**
     * @var _adminhtmlResponse
     */
    protected $_adminhtmlResponse;
    /**
     * @var _paymentUpdateFactory
     */
    protected $_paymentUpdateFactory;
    /**
     * @var _redirectResponse
     */
    protected $_redirectResponse;
    /**
     * @var _paymentModel
     */
    protected $_paymentModel;
    /**
     * @var _helper
     */
    protected $_helper;

    /**
     * @var paymentupdatefactory
     */
    protected $paymentupdatefactory;

    /**
     * @var \Sapient\Worldpay\Model\Request\PaymentServiceRequest
     */
    protected $paymentservicerequest;

    /**
     * @var \Sapient\Worldpay\Model\Worldpayment
     */
    protected $worldpayPayment;

    /**
     * @var \Sapient\Worldpay\Model\ResourceModel\Multishipping\Order\Collection
     */
    protected $multishippingOrderCollection;
    /**
     * Constructor
     *
     * @param \Sapient\Worldpay\Model\Payment\Update\Factory $paymentupdatefactory
     * @param \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest
     * @param \Sapient\Worldpay\Model\Worldpayment $worldpayPayment
     * @param \Sapient\Worldpay\Model\ResourceModel\Multishipping\Order\Collection $multishippingOrderCollection
     */
    public function __construct(
        \Sapient\Worldpay\Model\Payment\Update\Factory $paymentupdatefactory,
        \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest,
        \Sapient\Worldpay\Model\Worldpayment $worldpayPayment,
        \Sapient\Worldpay\Model\ResourceModel\Multishipping\Order\Collection $multishippingOrderCollection
    ) {
        $this->paymentupdatefactory = $paymentupdatefactory;
        $this->paymentservicerequest = $paymentservicerequest;
        $this->worldpayPayment = $worldpayPayment;
        $this->multishippingOrderCollection = $multishippingOrderCollection;
    }
    /**
     * Create Payment Update From WorldPay Xml
     *
     * @param string $xml
     * @return array
     */
    public function createPaymentUpdateFromWorldPayXml($xml)
    {
        return $this->_getPaymentUpdateFactory()
            ->create(new \Sapient\Worldpay\Model\Payment\StateXml($xml));
    }
    /**
     * Get Payment Update Factory
     *
     * @return array
     */
    protected function _getPaymentUpdateFactory()
    {
        if ($this->_paymentUpdateFactory === null) {
            $this->_paymentUpdateFactory = $this->paymentupdatefactory;
        }

        return $this->_paymentUpdateFactory;
    }
    /**
     * Create Payment Update From WorldPay Response
     *
     * @param Sapient\Worldpay\Model\Payment\StateInterface $state
     * @return array
     */
    public function createPaymentUpdateFromWorldPayResponse(\Sapient\Worldpay\Model\Payment\StateInterface $state)
    {
        return $this->_getPaymentUpdateFactory()
            ->create($state);
    }
    /**
     * Get Payment Update Xml For Notification
     *
     * @param string $xml
     * @return array
     */
    public function getPaymentUpdateXmlForNotification($xml)
    {
        $paymentNotifyService = new \SimpleXmlElement($xml);
        $lastEvent = $paymentNotifyService->xpath('//lastEvent');
        $journal = $paymentNotifyService->xpath('//journal/journalReference');
        if (!empty($journal) && $lastEvent[0] == 'CAPTURED') {
            $partialCaptureReference = (array) $journal[0]->attributes()['reference'][0];
            $ordercodenode = $paymentNotifyService->xpath('//orderStatusEvent');
            $ordercode = (array) $ordercodenode[0]->attributes()['orderCode'][0];
            $nodes = $paymentNotifyService->xpath('//payment/balance');
            $getNodeValue = '';
            $getAttibute = '';
            if (isset($nodes, $lastEvent[0], $partialCaptureReference[0])) {
                if ($nodes && $lastEvent[0] == 'CAPTURED' && $partialCaptureReference[0] == 'Partial Capture') {
                    $getAttibute = (array) $nodes[0]->attributes()['accountType'];
                    $getNodeValue = $getAttibute[0];
                }
                if ($lastEvent[0] == 'CAPTURED' && $partialCaptureReference[0] == 'Partial Capture'
                        && $getNodeValue == 'IN_PROCESS_AUTHORISED') {
                    $worldpaypayment = $this->worldpayPayment->loadByWorldpayOrderId($ordercode[0]);
                    if (isset($worldpaypayment)) {
                        $worldpaypayment->setData('payment_status', $lastEvent[0]);
                        $worldpaypayment->save();
                    }
                    $gatewayError = 'Notification received for Partial Captutre';
                    throw new \Magento\Framework\Exception\CouldNotDeleteException(__($gatewayError));
                }
            }
        }
    }
    /**
     * Get Payment Update Xml For Order
     *
     * @param Sapient\Worldpay\Model\Order $order
     * @return array
     */
    public function getPaymentUpdateXmlForOrder(\Sapient\Worldpay\Model\Order $order)
    {
        $worldPayPayment = $order->getWorldPayPayment();
        
        if (!$worldPayPayment) {
            return false;
        }
        $interactionType = $worldPayPayment->getInteractionType();
        if ($worldPayPayment->getIsMultishippingOrder()) {
            $interactionType = self::INTERACTION_TYPE_MS;
        }

        $rawXml = $this->paymentservicerequest->inquiry(
            $worldPayPayment->getMerchantId(),
            $worldPayPayment->getWorldpayOrderId(),
            $worldPayPayment->getStoreId(),
            $order->getPaymentMethodCode(),
            $worldPayPayment->getPaymentType(),
            $interactionType
        );
        
        $paymentService = new \SimpleXmlElement($rawXml);
        $lastEvent = $paymentService->xpath('//lastEvent');
        $partialCaptureReference = $paymentService->xpath('//reference');
        $ordercodenode = $paymentService->xpath('//orderStatus');
        $ordercode = (array)$ordercodenode[0]->attributes()['orderCode'][0];
    
        $nodes = $paymentService->xpath('//payment/balance');
        $getNodeValue ='';
        $getAttibute = '';
        
        if (isset($nodes, $lastEvent[0], $partialCaptureReference[0])) {
            /** Start Multishipping Code */
            if ($lastEvent[0] == 'CAPTURED' && $worldPayPayment->getIsMultishippingOrder()
                && (strpos($partialCaptureReference[0], "Partial Capture - ") !== false ||
                strpos($partialCaptureReference[0], "Capture - ") !== false)) {
                $worldpaypayment = $this->worldpayPayment->loadByPaymentId($worldPayPayment->getOrderId());
                if (isset($worldpaypayment)) {
                    $worldpaypayment->setData('payment_status', $lastEvent[0]);
                    $worldpaypayment->save();
                }
                $magentoorder = $order->getOrder();
                if ($magentoorder->hasInvoices() && $magentoorder->getBaseTotalDue() != 0) {
                    $gatewayError = 'Sync status action not possible for this Partial Capture Order.';
                    throw new \Magento\Framework\Exception\CouldNotDeleteException(__($gatewayError));
                }
            }
            /** End Multishipping Code */
            if ($nodes && $lastEvent[0] == 'CAPTURED' && $partialCaptureReference[0] == 'Partial Capture') {
                $getAttibute = (array) $nodes[0]->attributes()['accountType'];
                $getNodeValue = $getAttibute[0];
            }
            if ($lastEvent[0] == 'CAPTURED' && $partialCaptureReference[0] == 'Partial Capture'
            && $getNodeValue == 'IN_PROCESS_AUTHORISED') {
                $worldpaypayment= $this->worldpayPayment->loadByWorldpayOrderId($ordercode[0]);
                if (isset($worldpaypayment)) {
                    $worldpaypayment->setData('payment_status', $lastEvent[0]);
                    $worldpaypayment->save();
                }
                $gatewayError = 'Sync status action not possible for this Partial Capture Order.';
                throw new \Magento\Framework\Exception\CouldNotDeleteException(__($gatewayError));
            }
            
        }
        return simplexml_load_string($rawXml);
    }
    /**
     * Set Payment Global Payment By Payment Update
     *
     * @param string $paymentUpdate
     * @return array
     */
    public function setGlobalPaymentByPaymentUpdate($paymentUpdate)
    {
        $this->worldpayPayment->loadByWorldpayOrderId($paymentUpdate->getTargetOrderCode());
    }
}
