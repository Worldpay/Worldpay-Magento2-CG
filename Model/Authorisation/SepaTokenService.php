<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Authorisation;

use Magento\Framework\Exception\PaymentException;
use Magento\Framework\Phrase;

class SepaTokenService extends \Magento\Framework\DataObject
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var \Sapient\Worldpay\Model\Payment\UpdateWorldpaymentFactory
     */
    protected $updateWorldPayPayment;

      /**
       * @var \Sapient\Worldpay\Model\Request\PaymentServiceRequest
       */
    protected $mappingservice;

      /**
       * @var \Sapient\Worldpay\Model\Payment\UpdateWorldpaymentFactory
       */
    protected $paymentservicerequest;

      /**
       * @var \Sapient\Worldpay\Logger\WorldpayLogger
       */
    protected $wplogger;

      /**
       * @var \Sapient\Worldpay\Model\Response\DirectResponse
       */
    protected $directResponse;

      /**
       * @var  \Sapient\Worldpay\Model\Payment\Service
       */
    protected $paymentservice;

      /**
       * @var \Sapient\Worldpay\Helper\Data
       */
    protected $worldpayHelper;

      /**
       * @var \Sapient\Worldpay\Helper\Registry
       */
    protected $registryhelper;
    
      /**
       * @var \Magento\Framework\UrlInterface
       */
    protected $urlBuilders;
    /**
     * @var \Magento\Framework\DataObject\Copy
     */
    private $objectCopyService;

    /**
     * DirectService constructor
     *
     * @param \Sapient\Worldpay\Model\Mapping\Service $mappingservice
     * @param \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Model\Response\DirectResponse $directResponse
     * @param \Sapient\Worldpay\Model\Payment\UpdateWorldpaymentFactory $updateWorldPayPayment
     * @param \Sapient\Worldpay\Model\Payment\Service $paymentservice
     * @param \Sapient\Worldpay\Helper\Registry $registryhelper
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Sapient\Worldpay\Helper\Data $worldpayHelper
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     */
    public function __construct(
        \Sapient\Worldpay\Model\Mapping\Service $mappingservice,
        \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Response\DirectResponse $directResponse,
        \Sapient\Worldpay\Model\Payment\UpdateWorldpaymentFactory $updateWorldPayPayment,
        \Sapient\Worldpay\Model\Payment\Service $paymentservice,
        \Sapient\Worldpay\Helper\Registry $registryhelper,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Sapient\Worldpay\Helper\Data $worldpayHelper,
        \Magento\Framework\DataObject\Copy $objectCopyService,
    ) {
        $this->mappingservice = $mappingservice;
        $this->paymentservicerequest = $paymentservicerequest;
        $this->wplogger = $wplogger;
        $this->directResponse = $directResponse;
        $this->paymentservice = $paymentservice;
        $this->checkoutSession = $checkoutSession;
        $this->updateWorldPayPayment = $updateWorldPayPayment;
        $this->worldpayHelper = $worldpayHelper;
        $this->registryhelper = $registryhelper;
        $this->urlBuilders    = $urlBuilder;
        $this->objectCopyService = $objectCopyService;
    }

    /**
     * Handles provides authorization data for direct
     *
     * It initiates a  XML request to WorldPay and registers worldpayRedirectUrl
     *
     * @param MageOrder $mageOrder
     * @param Quote $quote
     * @param string $orderCode
     * @param string $orderStoreId
     * @param array $paymentDetails
     * @param Payment $payment
     */
    public function authorizePayment(
        $mageOrder,
        $quote,
        $orderCode,
        $orderStoreId,
        $paymentDetails,
        $payment
    ) {
        
        $directOrderParams = $this->mappingservice->collectSEPATokenOrderParameters(
            $orderCode,
            $quote,
            $orderStoreId,
            $paymentDetails
        );
        $response = $this->paymentservicerequest->sepaTokenOrder($directOrderParams);
        $directResponse = $this->directResponse->setResponse($response);
        $disclaimerFlag = '';
        
        if (!empty($directOrderParams['primeRoutingData'])) {
            $additionalInformation = $payment->getAdditionalInformation();
            $additionalInformation["worldpay_primerouting_enabled"]=true;
                $payment->setAdditionalInformation(
                    $additionalInformation
                );
        }
        
        if (isset($paymentDetails['additional_data']['disclaimerFlag'])) {
            $disclaimerFlag = $paymentDetails['additional_data']['disclaimerFlag'];
        }
        
        $this->updateWorldPayPayment->create()->updateWorldpayPayment($directResponse, $payment, $disclaimerFlag);
        $this->_applyPaymentUpdate($directResponse, $payment);
    }

    /**
     * Apply payment update
     *
     * @param \Sapient\Worldpay\Model\Response\DirectResponse $directResponse
     * @param Payment $payment
     */
    private function _applyPaymentUpdate(
        \Sapient\Worldpay\Model\Response\DirectResponse $directResponse,
        $payment
    ) {
        $paymentUpdate = $this->paymentservice->createPaymentUpdateFromWorldPayXml($directResponse->getXml());
        $paymentUpdate->apply($payment);
        $this->_abortIfPaymentError($paymentUpdate, $directResponse);
    }

    /**
     * Abort if payment error
     *
     * @param Object $paymentUpdate
     * @param \SimpleXMLObject $directResponse
     */
    private function _abortIfPaymentError($paymentUpdate, $directResponse)
    {
        $responseXml = $directResponse->getXml();
        $orderStatus = $responseXml->reply->orderStatus;
        $payment = $orderStatus->payment;
        $wpayCode = $payment->ISO8583ReturnCode['code'] ? $payment->ISO8583ReturnCode['code'] : 'Payment REFUSED';
        if ($paymentUpdate instanceof \Sapient\WorldPay\Model\Payment\Update\Refused) {
            $msg =  new Phrase($wpayCode);
            throw new PaymentException($msg);
        }

        if ($paymentUpdate instanceof \Sapient\WorldPay\Model\Payment\Update\Cancelled) {
            $msg =  new Phrase('Payment CANCELLED');
            throw new PaymentException($msg);
        }

        if ($paymentUpdate instanceof \Sapient\WorldPay\Model\Payment\Update\Error) {
            $msg =  new Phrase('Payment ERROR');
            throw new PaymentException($msg);
        }
    }
}
