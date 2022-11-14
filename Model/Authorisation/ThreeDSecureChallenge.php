<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Authorisation;

use Exception;

class ThreeDSecureChallenge extends \Magento\Framework\DataObject
{
    /** @var \Sapient\Worldpay\Model\Payment\UpdateWorldpaymentFactory */
    protected $updateWorldPayPayment;

    public const CART_URL = 'checkout/cart';

    /**
     * ThreeDSecureChallenge Constructor
     *
     * @param \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Model\Response\DirectResponse $directResponse
     * @param \Sapient\Worldpay\Model\Payment\Service $paymentservice
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Sapient\Worldpay\Model\Payment\UpdateWorldpaymentFactory $updateWorldPayPayment
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Sapient\Worldpay\Model\Token\WorldpayToken $worldpaytoken
     * @param \Sapient\Worldpay\Helper\Data $worldpayHelper
     * @param \Sapient\Worldpay\Helper\Multishipping $multishippingHelper
     */
    public function __construct(
        \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Response\DirectResponse $directResponse,
        \Sapient\Worldpay\Model\Payment\Service $paymentservice,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Sapient\Worldpay\Model\Order\Service $orderservice,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Sapient\Worldpay\Model\Payment\UpdateWorldpaymentFactory $updateWorldPayPayment,
        \Magento\Customer\Model\Session $customerSession,
        \Sapient\Worldpay\Model\Token\WorldpayToken $worldpaytoken,
        \Sapient\Worldpay\Helper\Data $worldpayHelper,
        \Sapient\Worldpay\Helper\Multishipping $multishippingHelper
    ) {
        $this->paymentservicerequest = $paymentservicerequest;
        $this->wplogger = $wplogger;
        $this->directResponse = $directResponse;
        $this->paymentservice = $paymentservice;
        $this->checkoutSession = $checkoutSession;
        $this->urlBuilders    = $urlBuilder;
        $this->orderservice = $orderservice;
        $this->_messageManager = $messageManager;
        $this->updateWorldPayPayment = $updateWorldPayPayment;
        $this->customerSession = $customerSession;
        $this->worldpaytoken = $worldpaytoken;
        $this->worldpayHelper = $worldpayHelper;
        $this->multishippingHelper = $multishippingHelper;
    }
     /**
      * Get order id column value
      *
      * @return string
      */
   
    public function isIAVEnabled()
    {
        return $this->worldpayHelper->isIAVEnabled();
    }

    /**
     * Continue post 3ds2 authorization process
     *
     * @param array $directOrderParams
     * @param array $threeDSecureParams
     */
    public function continuePost3dSecure2AuthorizationProcess($directOrderParams, $threeDSecureParams)
    {
        //$directOrderParams['response'] = $responseParams;
        //$directOrderParams['echoData'] = $threeDSecureParams->getEchoData();
        // @setIs3DSRequest flag set to ensure whether it is 3DS request or not.
        // To add cookie for 3DS second request.
        $this->checkoutSession->setIs3DS2Request(true);
        try {
            $response = $this->paymentservicerequest->order3Ds2Secure($directOrderParams);
            $this->response = $this->directResponse->setResponse($response);
            // @setIs3DSRequest flag is unset from checkout session.
            $this->checkoutSession->setIs3DS2Request();
            $orderIncrementId = current(explode('-', $directOrderParams['orderCode']));
            if ($this->checkoutSession->getIavCall()) {
                    $responseXml = $this->response->getXml();
                    $orderStatus = $responseXml->reply->orderStatus;
                    $payment=$orderStatus->payment;
                    $lastEvent = $payment->lastEvent;
                    $riskScore=$payment->riskScore['value'];
                    $riskProviderFinalScore=$payment->riskScore['finalScore'];
                    $this->_messageManager->getMessages(true);
                if (($lastEvent[0] == 'AUTHORISED') ||
                        ($this->isIAVEnabled() && ($lastEvent[0] == 'CANCELLED') &&
                         ($riskScore < 100 || $riskProviderFinalScore < 100))) {
                    if ($this->checkoutSession->getIavCall()) {
                        $this->customerSession->setIavCall(true);
                    }
                    $this->updateWorldPayPayment->create()
                    ->updateWorldpayPaymentForMyAccount($this->response, $payment);
                     $this->_messageManager->addSuccess(
                         $this->worldpayHelper->getMyAccountSpecificexception('IAVMA3')
                                ? $this->worldpayHelper->getMyAccountSpecificexception('IAVMA3')
                         : 'The card has been added'
                     );
                     $this->checkoutSession->setWpResponseForwardUrl($this->urlBuilders->getUrl(
                         'worldpay/savedcard',
                         ['_secure' => true]
                     ));
                } else {
                     $this->_messageManager->addError(
                         $this->worldpayHelper->getMyAccountSpecificexception('IAVMA4')
                                ? $this->worldpayHelper->getMyAccountSpecificexception('IAVMA4')
                         : 'Your card could not be saved'
                     );
                    $this->checkoutSession->setWpResponseForwardUrl($this->urlBuilders->getUrl(
                        'worldpay/savedcard',
                        ['_secure' => true]
                    ));
                }
                    
            } else {
                    $this->_order = $this->orderservice->getByIncrementId($orderIncrementId);
                    $worldpaypayment = $this->_order->getWorldPayPayment();
                    $this->_paymentUpdate = $this->paymentservice->createPaymentUpdateFromWorldPayXml(
                        $this->response->getXml()
                    );
                    $isMultishipping = false;
                if ($worldpaypayment->getIsMultishippingOrder()) {
                    $isMultishipping = true;
                }
                    $this->_paymentUpdate->apply($this->_order->getPayment(), $this->_order, $isMultishipping);
                    /** Start Multishipping Code */
                if ($isMultishipping) {
                    $quote_id = $this->_order->getQuoteId();
                    $inc_id = $orderIncrementId;
                    $multishippingOrders = $this->multishippingHelper->getMultishippingOrders($inc_id, $quote_id);
                    if ($multishippingOrders->count() > 0) {
                        foreach ($multishippingOrders as $multishippingOrder) {
                            $order_id = $multishippingOrder->getOrderId();
                            $other_order = $this->orderservice->getByIncrementId($order_id);
                            $type = true;
                            $this->multishippingHelper->_copyWorldPayPayment($orderIncrementId, $order_id, $type);
                            $this->multishippingHelper->_addTransaction($other_order->getPayment(), $other_order);
                        }
                    }
                }
                    /** End Multishipping Code */
                    $this->_abortIfPaymentError($this->_paymentUpdate, $orderIncrementId, $isMultishipping);
            }
            
        } catch (Exception $e) {
            $this->wplogger->info($e->getMessage());
            if ($e->getMessage() === 'Asymmetric transaction rollback.') {
                $errorMessage = $this->paymentservicerequest->getCreditCardSpecificException('CCAM16');
                $this->_messageManager->addError(__($errorMessage));
            } else {
                $this->_messageManager->getMessages(true);
                $this->_messageManager->
                        addError(__($this->paymentservicerequest->getCreditCardSpecificException('CCAM10')));
            }
            $this->checkoutSession->setWpResponseForwardUrl(
                $this->urlBuilders->getUrl(self::CART_URL, ['_secure' => true])
            );
            if ($this->checkoutSession->getIavCall()) {
                $this->checkoutSession->unsIavCall();
                $this->checkoutSession->setWpResponseForwardUrl($this->urlBuilders->getUrl(
                    'worldpay/savedcard/addnewcard',
                    ['_secure' => true]
                ));
            }
            return;
        }
    }

    /**
     * Help to build url if payment is success
     *
     * @param bool|null $isMultishipping
     */
    private function _handleAuthoriseSuccess($isMultishipping = null)
    {
        if ($this->checkoutSession->getInstantPurchaseOrder()) {
            $redirectUrl = $this->checkoutSession->getInstantPurchaseRedirectUrl();
            $this->checkoutSession->setWpResponseForwardUrl($redirectUrl);
        } else {
            if ($isMultishipping) {
                $this->checkoutSession->setWpResponseForwardUrl(
                    $this->urlBuilders->getUrl('multishipping/checkout/success', ['_secure' => true])
                );
            } else {
                $this->checkoutSession->setWpResponseForwardUrl(
                    $this->urlBuilders->getUrl('checkout/onepage/success', ['_secure' => true])
                );
            }
        }
    }

    /**
     * It handles if payment is refused or cancelled
     *
     * @param Object $paymentUpdate
     * @param string $orderId
     * @param bool|null $isMultishipping
     */
    private function _abortIfPaymentError($paymentUpdate, $orderId, $isMultishipping = null)
    {
        $responseXml = $this->response->getXml();
        $orderStatus = $responseXml->reply->orderStatus;
        $payment = $orderStatus->payment;
        $wpayCode = $payment->ISO8583ReturnCode['code'] ? $payment->ISO8583ReturnCode['code'] : '';
        if ($paymentUpdate instanceof \Sapient\WorldPay\Model\Payment\Update\Refused) {
            $message = $this->worldpayHelper->getExtendedResponse($wpayCode, $orderId);
            $responseMessage = !empty($message) ? $message :
            $this->paymentservicerequest->getCreditCardSpecificException('CCAM9');
            $this->_messageManager->addError(__($responseMessage));
            if ($this->checkoutSession->getInstantPurchaseOrder()) {
                $redirectUrl = $this->checkoutSession->getInstantPurchaseRedirectUrl();
                $this->checkoutSession->unsInstantPurchaseMessage();
                $this->checkoutSession->setWpResponseForwardUrl($redirectUrl);
            } elseif ($this->checkoutSession->getIavCall()) {
                $this->checkoutSession->unsIavCall();
                $this->checkoutSession->setWpResponseForwardUrl($this->urlBuilders->getUrl(
                    'worldpay/savedcard/addnewcard',
                    ['_secure' => true]
                ));
            } else {
                $this->checkoutSession->setWpResponseForwardUrl(
                    $this->urlBuilders->getUrl(self::CART_URL, ['_secure' => true])
                );
            }
        } elseif ($paymentUpdate instanceof \Sapient\WorldPay\Model\Payment\Update\Cancelled) {
            $this->_messageManager
                    ->addError(__($this->paymentservicerequest
                            ->getCreditCardSpecificException('CCAM9')));
            if ($this->checkoutSession->getInstantPurchaseOrder()) {
                $redirectUrl = $this->checkoutSession->getInstantPurchaseRedirectUrl();
                $this->checkoutSession->unsInstantPurchaseMessage();
                $this->checkoutSession->setWpResponseForwardUrl($redirectUrl);
            } elseif ($this->checkoutSession->getIavCall()) {
                $this->checkoutSession->unsIavCall();
                $this->checkoutSession->setWpResponseForwardUrl($this->urlBuilders->getUrl(
                    'worldpay/savedcard/addnewcard',
                    ['_secure' => true]
                ));
            } else {
                $this->checkoutSession->setWpResponseForwardUrl(
                    $this->urlBuilders->getUrl(self::CART_URL, ['_secure' => true])
                );
            }
        } else {
            $this->orderservice->redirectOrderSuccess();
            $this->orderservice->removeAuthorisedOrder();
            $this->_handleAuthoriseSuccess($isMultishipping);
            $this->_updateTokenData($this->response->getXml());
        }
    }

    /**
     * This will Save card
     *
     * @param xml $xmlResponseData
     */
    private function _updateTokenData($xmlResponseData)
    {
        if ($this->customerSession->getIsSavedCardRequested()) {
            $tokenData = $xmlResponseData->reply->orderStatus->token;
            $paymentData = $xmlResponseData->reply->orderStatus->payment;
            $merchantCode = $xmlResponseData['merchantCode'];
            if ($tokenData) {
                $this->_applyTokenUpdate($xmlResponseData);
            }
            $this->customerSession->unsIsSavedCardRequested();
        }
    }

    /**
     * Update or Insert Token Detail
     *
     * @param SimpleXMLElement $xmlRequest
     */
    private function _applyTokenUpdate($xmlRequest)
    {
        $tokenService = $this->worldpaytoken;
        $tokenService->updateOrInsertToken(
            new \Sapient\Worldpay\Model\Token\StateXml($xmlRequest),
            $this->_order->getPayment()
        );
    }
}
