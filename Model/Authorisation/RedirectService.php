<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Authorisation;

use Exception;

class RedirectService extends \Magento\Framework\DataObject
{

    /** @var \Sapient\Worldpay\Model\Response\RedirectResponse */
    protected $_redirectResponseModel;

    /**
     * Constructor
     * @param \Sapient\Worldpay\Model\Mapping\Service $mappingservice
     * @param \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Model\Payment\Service $paymentservice
     * @param \Sapient\Worldpay\Model\Response\RedirectResponse $redirectresponse
     * @param \Sapient\Worldpay\Helper\Registry $registryhelper
     * @param \Magento\Checkout\Model\Session $checkoutsession
     * @param \Sapient\Worldpay\Model\Utilities\PaymentMethods $paymentlist
     * @param \Sapient\Worldpay\Helper\Data $worldpayhelper
     * @param \Sapient\Worldpay\Helper\Multishipping $multishippingHelper
     */
    public function __construct(
        \Sapient\Worldpay\Model\Mapping\Service $mappingservice,
        \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Payment\Service $paymentservice,
        \Sapient\Worldpay\Model\Response\RedirectResponse $redirectresponse,
        \Sapient\Worldpay\Helper\Registry $registryhelper,
        \Magento\Checkout\Model\Session $checkoutsession,
        \Sapient\Worldpay\Model\Utilities\PaymentMethods $paymentlist,
        \Sapient\Worldpay\Helper\Data $worldpayhelper,
        \Sapient\Worldpay\Helper\Multishipping $multishippingHelper
    ) {
        $this->mappingservice = $mappingservice;
        $this->paymentservicerequest = $paymentservicerequest;
        $this->wplogger = $wplogger;
        $this->paymentservice = $paymentservice;
        $this->redirectresponse = $redirectresponse;
        $this->registryhelper = $registryhelper;
        $this->checkoutsession = $checkoutsession;
        $this->paymentlist = $paymentlist;
        $this->worldpayhelper = $worldpayhelper;
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

        $this->checkoutsession->setauthenticatedOrderId($mageOrder->getIncrementId());
        /** Start Multishipping Code */
        if ($this->worldpayhelper->isMultiShipping($quote)) {
            $sessionOrderCode = $this->multishippingHelper->getOrderCodeFromSession();
            if (!empty($sessionOrderCode)) {
                $orgWorldpayPayment = $this->multishippingHelper->getOrgWorldpayId($sessionOrderCode);
                $orgOrderId = $orgWorldpayPayment['order_id'];
                $isOrg = false;
                $this->multishippingHelper->_createWorldpayMultishipping($mageOrder, $sessionOrderCode, $isOrg);
                $this->multishippingHelper->_copyWorldPayPayment($orgOrderId, $orderCode);
                $payment->setIsTransactionPending(1);
                return;
            } else {
                $isOrg = true;
                $this->multishippingHelper->_createWorldpayMultishipping($mageOrder, $orderCode, $isOrg);
            }
        }
        /** End Multishipping Code */
        if ($paymentDetails['additional_data']['cc_type'] == 'KLARNA-SSL') {
             $redirectOrderParams = $this->mappingservice->collectKlarnaOrderParameters(
                 $orderCode,
                 $quote,
                 $orderStoreId,
                 $paymentDetails
             );

            $response = $this->paymentservicerequest->redirectKlarnaOrder($redirectOrderParams);
        } elseif (!empty($paymentDetails['additional_data']['cc_bank']) &&
           $paymentDetails['additional_data']['cc_type'] == 'IDEAL-SSL') {
               $callbackurl = $this->redirectresponse->getCallBackUrl();
               $redirectOrderParams = $this->mappingservice->collectRedirectOrderParameters(
                   $orderCode,
                   $quote,
                   $orderStoreId,
                   $paymentDetails
               );
               $redirectOrderParams['cc_bank'] = $paymentDetails['additional_data']['cc_bank'];
               $redirectOrderParams['callbackurl'] = $callbackurl;

            $response = $this->paymentservicerequest->directIdealOrder($redirectOrderParams);
        } else {
            $redirectOrderParams = $this->mappingservice->collectRedirectOrderParameters(
                $orderCode,
                $quote,
                $orderStoreId,
                $paymentDetails
            );
            $response = $this->paymentservicerequest->redirectOrder($redirectOrderParams);
        }
        $paymentType = $redirectOrderParams['paymentType'];
        if ($paymentDetails['additional_data']['cc_type'] == 'savedcard') {
            $paymentType = null;
        }
        $redirectLocation = $this->_getRedirectResponseModel()->getRedirectLocation($response);

        $successUrl = $this->_buildRedirectUrl(
            $redirectLocation,
            $paymentType,
            $this->_getCountryForQuote($quote),
            $this->_getLanguageForLocale()
        );
        $payment->setIsTransactionPending(1);
        $this->registryhelper->setworldpayRedirectUrl($successUrl);
        $this->checkoutsession->setWpRedirecturl($successUrl);
    }

    /**
     * Build redirect url
     *
     * @param string $redirect
     * @param string $paymentType
     * @param string $countryCode
     * @param string $languageCode
     * @return string
     */
    private function _buildRedirectUrl($redirect, $paymentType, $countryCode, $languageCode)
    {
        if ($paymentType == "SEPA_DIRECT_DEBIT-SSL") {
           //$redirect .= '&preferredPaymentMethod=' . $paymentType;
            $redirect .= '&country=' . $countryCode;
            $redirect .= '&language=' . $languageCode;
        } else {
            $redirect .= '&preferredPaymentMethod=' . $paymentType;
               $redirect .= '&country=' . $countryCode;
               $redirect .= '&language=' . $languageCode;

        }
        return $redirect;
    }

    /**
     * Get billing Country
     *
     * @param Quote $quote
     * @return string
     */
    private function _getCountryForQuote($quote)
    {
        $address = $quote->getBillingAddress();
        if ($address->getId()) {
            return $address->getCountry();
        }
        return $this->worldpayhelper->getDefaultCountry();
    }

    /**
     * Get local language code
     *
     * @return string
     */
    protected function _getLanguageForLocale()
    {
        $locale = $this->worldpayhelper->getLocaleDefault();
        if (substr($locale, 3, 2) == 'NO') {
            return 'no';
        }
        return substr($locale, 0, 2);
    }

    /**
     * Get redirect response model
     *
     * @return \Sapient\Worldpay\Model\Response\RedirectResponse
     */
    protected function _getRedirectResponseModel()
    {
        if ($this->_redirectResponseModel === null) {
            $this->_redirectResponseModel = $this->redirectresponse;
        }
        return $this->_redirectResponseModel;
    }
}
