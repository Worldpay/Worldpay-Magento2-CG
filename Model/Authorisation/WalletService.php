<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Authorisation;

use Exception;

class WalletService extends \Magento\Framework\DataObject
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
       * @var \Sapient\Worldpay\Model\Mapping\Service
       */
    protected $mappingservice;

      /**
       * @var \Sapient\Worldpay\Logger\WorldpayLogger
       */
    protected $wplogger;
     
    /**
     * @var \Sapient\Worldpay\Model\Response\DirectResponse
     */
    protected $directResponse;
     /**
      * @var \Sapient\Worldpay\Model\Payment\Service
      */
    protected $paymentservice;

     /**
      * @var \Sapient\Worldpay\Model\Request\PaymentServiceRequest
      */
    protected $paymentservicerequest;
     /**
      * @var \Sapient\Worldpay\Helper\Registry
      */
    protected $registryhelper;

    /**
     * @var \Sapient\Worldpay\Helper\Data
     */
    protected $worldpayHelper;
    
    /**
     * @var \Sapient\Worldpay\Helper\Multishipping
     */
    protected $multishippingHelper;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilders;

    /**
     * WalletService constructor
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
     * @param \Sapient\Worldpay\Helper\Multishipping $multishippingHelper
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
        \Sapient\Worldpay\Helper\Multishipping $multishippingHelper
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
        $this->multishippingHelper = $multishippingHelper;
    }

    /**
     * Handles provides authorization data for redirect
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
        /** Start Multishipping Code */
        if ($this->worldpayHelper->isMultiShipping($quote)) {
            $sessionOrderCode = $this->multishippingHelper->getOrderCodeFromSession();
            if (!empty($sessionOrderCode)) {
                $quote_id = $mageOrder->getQuoteId();
                $inc_id = $mageOrder->getIncrementId();
                $orgWorldpayPayment = $this->multishippingHelper->getOrgWorldpayId($sessionOrderCode);
                $orgOrderId = $orgWorldpayPayment['order_id'];
                $isOrg = false;
                $this->multishippingHelper->_createWorldpayMultishipping($mageOrder, $sessionOrderCode, $isOrg);
                $this->multishippingHelper->_copyWorldPayPayment($orgOrderId, $orderCode);
                $is3dsOrder = $this->multishippingHelper->is3dsOrder();
                if ($is3dsOrder) {
                    $payment->setIsTransactionPending(1);
                } else {
                    $payment->setTransactionId(time());
                    $payment->setIsTransactionClosed(0);
                }
                return;
            } else {
                $isOrg = true;
                $this->multishippingHelper->_createWorldpayMultishipping($mageOrder, $orderCode, $isOrg);
            }
        }
        /** End Multishipping Code */
        if ($paymentDetails['additional_data']['cc_type'] == 'PAYWITHGOOGLE-SSL') {
            $walletOrderParams = $this->mappingservice->collectWalletOrderParameters(
                $orderCode,
                $quote,
                $orderStoreId,
                $paymentDetails
            );

            $response = $this->paymentservicerequest->walletsOrder($walletOrderParams);
    /*    $response = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE paymentService PUBLIC "-//WorldPay//DTD WorldPay PaymentService v1//EN"
                                "http://dtd.worldpay.com/paymentService_v1.dtd">
<paymentService version="1.4" merchantCode="SAPIENTNITROECOMMERCEV1"><reply><orderStatus orderCode="gpay-2"><challengeRequired><threeDSChallengeDetails><threeDSVersion>1.0.2</threeDSVersion><transactionId3DS>1BqYFw0XafF2NQDwEsSF</transactionId3DS><acsURL><![CDATA[https://worldpay.com]]></acsURL><payload>eJxVUttOwzAM/ZVoz7CkYZR2MkaDIbGHjbGND4hSi1WiaUkyuvH1JN3defHx5dixDU/b6pv9knVlbR57SV/02BPCam2JxkvSG0sIU3JOfRErixCRCpnJJBdpnuQ9hPloQT8IBwYMBH0J/AhDqtVrZTyC0j/PkxkmZwF+sEFFdjK+dEW5Pcft/dAo59raFpjIu8F9+pABP5nAqIpwqZqSjGez0tuarch5tvS1JeCdG3S9Md7uMJMp8COAjf3GtffNkPO2bftuT2IiR1/XFfAYAPz8l/kmai4QbssCp3+T3XSl/2bjiXz/GF3KI/AYAYXyhFJIkYgkZWIwTLJhbKGzg6piJ/j6uWDiRogwl70BmlhntAfBfAkhrMaS0TvM4xxOCGjb1Ca0j2ENJx0KchpHRcEMtUwrW7DSsOmOKd1NIXQSA4Cff/byFnemfRj8/eD6xcV1jlisDBOUuci6ahEAj6n8cBj8cDxBuzqqfwFtzCg=</payload></threeDSChallengeDetails></challengeRequired></orderStatus></reply></paymentService>
';*/
            $directResponse = $this->directResponse->setResponse($response);
            $threeDSecureParams = $directResponse->get3dSecureParams();
            $threeDsEnabled = $this->worldpayHelper->is3DSecureEnabled();
            $threeDSecureChallengeParams = $directResponse->get3ds2Params();
            if ($threeDSecureParams) {
            // Handles success response with 3DS & redirect for varification.
                $this->checkoutSession->setauthenticatedOrderId($mageOrder->getIncrementId());
                $payment->setIsTransactionPending(1);
                $this->_handle3DSecure($threeDSecureParams, $walletOrderParams, $orderCode);
            } elseif ($threeDSecureChallengeParams) {
                // Handles success response with 3DS2 & redirect for varification.
                $this->checkoutSession->setauthenticatedOrderId($mageOrder->getIncrementId());
                $payment->setIsTransactionPending(1);
                $threeDSecureConfig = $this->get3DS2ConfigValues();
                $this->_handle3Ds2($threeDSecureChallengeParams, $walletOrderParams, $orderCode, $threeDSecureConfig);
            } else {
                if ($paymentDetails['additional_data']['cc_type'] == 'PAYWITHGOOGLE-SSL') {
                    $responseXml=$directResponse->getXml();
                    $orderStatus = $responseXml->reply->orderStatus;
                    $paymentxml=$orderStatus->payment;
                    $paymentxml->paymentMethod[0] = 'PAYWITHGOOGLE-SSL';
                }
                $this->updateWorldPayPayment->create()->updateWorldpayPayment($directResponse, $payment);
                $this->_applyPaymentUpdate($directResponse, $payment);
            }
        }
        
        if ($paymentDetails['additional_data']['cc_type'] == 'APPLEPAY-SSL') {
            $applePayOrderParams = $this->mappingservice->collectWalletOrderParameters(
                $orderCode,
                $quote,
                $orderStoreId,
                $paymentDetails
            );

            $response = $this->paymentservicerequest->applePayOrder($applePayOrderParams);
            /* dummy Apple Pay Response , Please remove before release
            $response = '<?xml version="1.0" encoding="UTF-8"?>
            <!DOCTYPE paymentService PUBLIC "-//WorldPay//DTD WorldPay PaymentService v1//EN"
                                            "http://dtd.worldpay.com/paymentService_v1.dtd">
            <paymentService version="1.4" merchantCode="SAPIENTNITROECOM">
            <reply><orderStatus orderCode="000001007-1646392522">
            <payment><paymentMethod>ECMC-SSL</paymentMethod><paymentMethodDetail>
            <card number="520424******2484" type="creditcard">
            <expiryDate><date month="09" year="2022"/></expiryDate>
            </card></paymentMethodDetail>
            <amount value="3882" currencyCode="USD" exponent="2" debitCreditIndicator="credit"/>
            <lastEvent>AUTHORISED</lastEvent>
            <AuthorisationId id="516808"/><CVCResultCode description="B"/><AVSResultCode description="H"/>
            <issuerCountryCode>US</issuerCountryCode><balance accountType="IN_PROCESS_AUTHORISED">
            <amount value="3882" currencyCode="USD" exponent="2" debitCreditIndicator="credit"/></balance>
            <riskScore Provider="FraudSight" finalScore="0"
            id="27930203-0940-411d-b79c-f39648c684d9" message="low-risk"/>
            <FraudSight score="0" id="27930203-0940-411d-b79c-f39648c684d9" message="low-risk">
            <reasonCodes><reasonCode>SUSPECTED FRAUD</reasonCode>
            </reasonCodes></FraudSight></payment></orderStatus></reply></paymentService>';
             */
            $directResponse = $this->directResponse->setResponse($response);
            $this->updateWorldPayPayment->create()->updateWorldpayPayment($directResponse, $payment);
            $this->_applyPaymentUpdate($directResponse, $payment);
        }
    }
    /**
     * Get 3ds2 params from the configuration and set to checkout session
     *
     * @return array
     */
    public function get3DS2ConfigValues()
    {
        $data = [];
        $data['jwtApiKey'] =  $this->worldpayHelper->isJwtApiKey();
        $data['jwtIssuer'] =  $this->worldpayHelper->isJwtIssuer();
        $data['organisationalUnitId'] = $this->worldpayHelper->isOrganisationalUnitId();
        $data['challengeWindowType'] = $this->worldpayHelper->getChallengeWindowSize();
    
        $mode = $this->worldpayHelper->getEnvironmentMode();
        if ($mode == 'Test Mode') {
            $data['challengeurl'] =  $this->worldpayHelper->isTestChallengeUrl();
        } else {
            $data['challengeurl'] =  $this->worldpayHelper->isProductionChallengeUrl();
        }
        
        return $data;
    }
    
    /**
     * Handles 3d secure for direct
     *
     * @param array $threeDSecureParams
     * @param array $directOrderParams
     * @param string $mageOrderId
     */
    private function _handle3DSecure($threeDSecureParams, $directOrderParams, $mageOrderId)
    {
        $this->registryhelper->setworldpayRedirectUrl($threeDSecureParams);
        $this->checkoutSession->set3DSecureParams($threeDSecureParams);
        $this->checkoutSession->setDirectOrderParams($directOrderParams);
        $this->checkoutSession->setAuthOrderId($mageOrderId);
    }
    
    /**
     * Handles 3ds2 secure for direct
     *
     * @param array $threeDSecureChallengeParams
     * @param array $directOrderParams
     * @param string $mageOrderId
     * @param array $threeDSecureConfig
     */
    private function _handle3Ds2($threeDSecureChallengeParams, $directOrderParams, $mageOrderId, $threeDSecureConfig)
    {
        $this->registryhelper->setworldpayRedirectUrl($threeDSecureChallengeParams);
        $this->checkoutSession->set3Ds2Params($threeDSecureChallengeParams);
        $this->checkoutSession->setDirectOrderParams($directOrderParams);
        $this->checkoutSession->setAuthOrderId($mageOrderId);
        $this->checkoutSession->set3DS2Config($threeDSecureConfig);
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
        $this->_abortIfPaymentError($paymentUpdate);
    }

    /**
     * Abort if payment error
     *
     * @param Object $paymentUpdate
     */
    private function _abortIfPaymentError($paymentUpdate)
    {
        if ($paymentUpdate instanceof \Sapient\WorldPay\Model\Payment\Update\Refused) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(sprintf('Payment REFUSED'))
            );
        }

        if ($paymentUpdate instanceof \Sapient\WorldPay\Model\Payment\Update\Cancelled) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(sprintf('Payment CANCELLED'))
            );
        }

        if ($paymentUpdate instanceof \Sapient\WorldPay\Model\Payment\Update\Error) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(sprintf('Payment ERROR'))
            );
        }
    }
}
