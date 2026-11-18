<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Payment;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\LocalizedException;
use Sapient\Worldpay\Model\Order;
use Sapient\Worldpay\Model\Payment\Update\Factory;
use Sapient\Worldpay\Model\Worldpayment;
use Magento\Framework\Exception\CouldNotSaveException;
use Sapient\Worldpay\Model\Payment\Update\Factory as PaymentUpdateFactory;
use Sapient\Worldpay\Model\Request\PaymentServiceRequest;
use Sapient\Worldpay\Model\ResourceModel\Multishipping\Order\Collection as MultishippingOrderCollection;

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

    public function __construct(
        Factory $paymentupdatefactory,
        PaymentServiceRequest $paymentservicerequest,
        Worldpayment $worldpayPayment,
        MultishippingOrderCollection $multishippingOrderCollection
    ) {
        $this->paymentupdatefactory = $paymentupdatefactory;
        $this->paymentservicerequest = $paymentservicerequest;
        $this->worldpayPayment = $worldpayPayment;
        $this->multishippingOrderCollection = $multishippingOrderCollection;
    }

    public function createPaymentUpdateFromWorldPayXml(\SimpleXMLElement $xml): object
    {
        return $this->_getPaymentUpdateFactory()->create(new StateXml($xml));
    }

    protected function _getPaymentUpdateFactory(): PaymentUpdateFactory
    {
        if ($this->_paymentUpdateFactory === null) {
            $this->_paymentUpdateFactory = $this->paymentupdatefactory;
        }

        return $this->_paymentUpdateFactory;
    }

    public function createPaymentUpdateFromWorldPayResponse(StateInterface $state): object
    {
        return $this->_getPaymentUpdateFactory()->create($state);
    }

    /**
     * @throws CouldNotSaveException
     */
    public function getPaymentUpdateXmlForNotification(string $xml): void
    {
        $paymentNotifyService = new \SimpleXmlElement($xml);
        $lastEvent = (string) $paymentNotifyService->xpath('//lastEvent')[0];
        $journal = $paymentNotifyService->xpath('//journal/journalReference');
        $orderCodeNode = $paymentNotifyService->xpath('//orderStatusEvent');
        $orderCode =  $orderCodeNode[0]->attributes()['orderCode'][0];

        if ($lastEvent === 'AUTHORISED') {
            $worldpayPayment = $this->worldpayPayment->loadByWorldpayOrderId($orderCode);
            if (isset($worldpayPayment)) {
                try{
                    $this->updatePaymentDccDataFromXml($worldpayPayment, $xml);
                } catch (CouldNotSaveException $e) {
                    throw new CouldNotSaveException(__($e->getMessage()));
                }
            }
        }

        if (!empty($journal) && $lastEvent === 'CAPTURED') {
            $partialCaptureReference = (array) $journal[0]->attributes()['reference'][0];
            $nodes = $paymentNotifyService->xpath('//payment/balance');

            if (!empty($nodes) && isset($partialCaptureReference[0]) && $partialCaptureReference[0] === 'Partial Capture')
            {
                $attributes = (array) $nodes[0]->attributes()['accountType'];
                $accountType = $attributes[0] ?? null;

                if ($accountType === 'IN_PROCESS_AUTHORISED') {
                    $worldpayPayment = $this->worldpayPayment->loadByWorldpayOrderId($orderCode);

                    if ($worldpayPayment) {
                        $worldpayPayment->setData('payment_status', $lastEvent);
                        $worldpayPayment->save();
                    }

                    throw new CouldNotSaveException(
                        __('Notification received for Partial Capture')
                    );
                }
            }
        }
    }

    /**
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    public function getPaymentUpdateXmlForOrder(Order $order): \SimpleXMLElement
    {
        $worldPayPayment = $order->getWorldPayPayment();

        $interactionType = $worldPayPayment->getInteractionType();
        if ($worldPayPayment->getIsMultishippingOrder()) {
            $interactionType = self::INTERACTION_TYPE_MS;
        }
        $rawXml = $this->paymentservicerequest->inquiry(
            $worldPayPayment->getMerchantId(),
            $worldPayPayment->getWorldpayOrderId(),
            $order->getStoreId(),
            $order->getPaymentMethodCode(),
            $worldPayPayment->getPaymentType(),
            $interactionType
        );

        $paymentService = new \SimpleXmlElement($rawXml);
        $lastEvent = $paymentService->xpath('//lastEvent');
        $partialCaptureReference = $paymentService->xpath('//reference');

        if(isset($lastEvent[0]) && (string) $lastEvent[0] === 'CAPTURED'){
            /** Start Multishipping Code */
            if ($worldPayPayment->getIsMultishippingOrder()
                && isset($partialCaptureReference[0])
                && (str_contains((string) $partialCaptureReference[0], "Partial Capture - ") ||
                    str_contains((string) $partialCaptureReference[0], "Capture - "))) {
                $worldPayPayment->setData('payment_status', $lastEvent[0]);
                $worldPayPayment->save();
                $magentoorder = $order->getOrder();
                if ($magentoorder->hasInvoices() && $magentoorder->getBaseTotalDue() != 0) {
                    $gatewayError = 'Sync status action not possible for this Partial Capture Order.';
                    throw new CouldNotSaveException(__($gatewayError));
                }
            }
            /** End Multishipping Code */

            $nodes = $paymentService->xpath('//payment/balance');

            if (!empty($nodes) && isset($partialCaptureReference[0]) && $partialCaptureReference[0] === 'Partial Capture') {
                $attributes = (array)$nodes[0]->attributes()['accountType'];
                $accountType = $attributes[0] ?? null;

                if ($accountType === 'IN_PROCESS_AUTHORISED') {
                    $worldPayPayment->setData('payment_status', $lastEvent[0]);
                    $worldPayPayment->save();
                    $magentoorder = $order->getOrder();
                    if ($magentoorder->hasInvoices() && $magentoorder->getBaseTotalDue() != 0) {
                        $gatewayError = 'Sync status action not possible for this Partial Capture Order.';
                        throw new CouldNotSaveException(__($gatewayError));
                    }
                }
            }
        }
        return simplexml_load_string($rawXml);
    }

    public function setGlobalPaymentByPaymentUpdate(UpdateInterface $paymentUpdate): void
    {
        $this->worldpayPayment->loadByWorldpayOrderId($paymentUpdate->getTargetOrderCode());
    }

    public function updatePaymentDccData(Worldpayment $worldpayPayment, array $dccOfferParams): void
    {
        if (isset($dccOfferParams['dccOfferAmount'])) {
            $worldpayPayment->setData('dcc_offer_amount', $dccOfferParams['dccOfferAmount']);
        }
        if (isset($dccOfferParams['dccOfferCurrencyCode'])) {
            $worldpayPayment->setData('dcc_offer_currency_code', $dccOfferParams['dccOfferCurrencyCode']);
        }
        if (isset($dccOfferParams['dccOfferRate'])) {
            $worldpayPayment->setData('dcc_offer_rate', $dccOfferParams['dccOfferRate']);
        }
        if (isset($dccOfferParams['dccOfferAcceptanceDate'])) {
            $worldpayPayment->setData('dcc_offer_acceptance_date', $dccOfferParams['dccOfferAcceptanceDate']);
        }
        if (isset($dccOfferParams['dccOfferRateProvider'])) {
            $worldpayPayment->setData('dcc_offer_rate_provider', $dccOfferParams['dccOfferRateProvider']);
        }
        if (isset($dccOfferParams['dccOfferMarkupRate'])) {
            $worldpayPayment->setData('dcc_offer_markup_rate', $dccOfferParams['dccOfferMarkupRate']);
        }

        try {
            $worldpayPayment->save();
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __('Failed to save DCC offer data: %1', $e->getMessage())
            );
        }
    }

    public function updatePaymentDccDataFromXml(Worldpayment $worldpayPayment, string $xml): void
    {
        $xmlElement = new \SimpleXmlElement($xml);

        $dccOfferElements = $xmlElement->xpath('//dynamicCurrencyConversionOffer');

        if (empty($dccOfferElements)) {
            return;
        }

        $dccOffer = $dccOfferElements[0];
        $exchangeRateElement = $dccOffer->xpath('exchangeRate');
        $amountElement = $dccOffer->xpath('amount');
        $dateElement = $dccOffer->xpath('date');

        if (!empty($exchangeRateElement)) {
            $exchangeRateAttrs = $exchangeRateElement[0]->attributes();
            if (isset($exchangeRateAttrs['rate'])) {
                $worldpayPayment->setData('dcc_offer_rate', (string)$exchangeRateAttrs['rate']);
            }
            if (isset($exchangeRateAttrs['rateProvider'])) {
                $worldpayPayment->setData('dcc_offer_rate_provider', (string)$exchangeRateAttrs['rateProvider']);
            }
            if (isset($exchangeRateAttrs['markupRate'])) {
                $worldpayPayment->setData('dcc_offer_markup_rate', (string)$exchangeRateAttrs['markupRate']);
            }
        }

        if (!empty($amountElement)) {
            $amountAttrs = $amountElement[0]->attributes();
            if (isset($amountAttrs['value'])) {
                $worldpayPayment->setData('dcc_offer_amount', (string)$amountAttrs['value']);
            }
            if (isset($amountAttrs['currencyCode'])) {
                $worldpayPayment->setData('dcc_offer_currency_code', (string)$amountAttrs['currencyCode']);
            }
        }

        if (!empty($dateElement)) {
            $dateAttrs = $dateElement[0]->attributes();
            $dayOfMonth = isset($dateAttrs['dayOfMonth']) ? (string)$dateAttrs['dayOfMonth'] : '01';
            $month = isset($dateAttrs['month']) ? (string)$dateAttrs['month'] : '01';
            $year = isset($dateAttrs['year']) ? (string)$dateAttrs['year'] : date('Y');

            $acceptanceDate = sprintf('%04d%02d%02d', $year, $month, $dayOfMonth);
            $worldpayPayment->setData('dcc_offer_acceptance_date', $acceptanceDate);
        }

        try {
            $worldpayPayment->save();
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __('Failed to save DCC offer data from notification: %1', $e->getMessage())
            );
        }
    }
}
