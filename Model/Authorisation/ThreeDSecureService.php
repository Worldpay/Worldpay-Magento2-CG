<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Authorisation;

use Exception;

class ThreeDSecureService extends \Magento\Framework\DataObject
{
    /** @var \Sapient\Worldpay\Model\Payment\UpdateWorldpaymentFactory */
    protected $updateWorldPayPayment;

    const CART_URL = 'checkout/cart';

    /**
     * Constructor
     * @param \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest,
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
     * @param \Sapient\Worldpay\Model\Response\DirectResponse $directResponse,
     * @param \Sapient\Worldpay\Model\Payment\Service $paymentservice,
     * @param \Magento\Checkout\Model\Session $checkoutSession,
     * @param \Magento\Framework\UrlInterface $urlBuilder,
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice,
     * @param \Magento\Framework\Message\ManagerInterface $messageManager,
     * @param \Sapient\Worldpay\Model\Payment\UpdateWorldpaymentFactory $updateWorldPayPayment,
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Sapient\Worldpay\Helper\Data $worldpayHelper
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
        \Sapient\Worldpay\Helper\Data $worldpayHelper
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
    public function continuePost3dSecureAuthorizationProcess($paResponse, $directOrderParams, $threeDSecureParams)
    {
        $directOrderParams['paResponse'] = $paResponse;
        if (!empty($threeDSecureParams) && $threeDSecureParams->getEchoData()) {
            $directOrderParams['echoData'] = $threeDSecureParams->getEchoData();
        }
        // @setIs3DSRequest flag set to ensure whether it is 3DS request or not.
        // To add cookie for 3DS second request.
        $this->checkoutSession->setIs3DSRequest(true);
        try {
            if (isset($directOrderParams['echoData'])) {
                $response = $this->paymentservicerequest->order3DSecure($directOrderParams);
                $this->response = $this->directResponse->setResponse($response);
                // @setIs3DSRequest flag is unset from checkout session.
                $this->checkoutSession->unsIs3DSRequest();
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
                             'savedcard',
                             ['_secure' => true]
                         ));
                    } else {
                         $this->_messageManager->addError(
                             $this->worldpayHelper->getMyAccountSpecificexception('IAVMA4')
                                ? $this->worldpayHelper->getMyAccountSpecificexception('IAVMA4')
                             : 'Your card could not be saved'
                         );
                         $this->checkoutSession->setWpResponseForwardUrl($this->urlBuilders->getUrl(
                             'savedcard',
                             ['_secure' => true]
                         ));
                    }
                    
                } else {
                    $this->_order = $this->orderservice->getByIncrementId($orderIncrementId);
                    $this->_paymentUpdate = $this->paymentservice->createPaymentUpdateFromWorldPayXml(
                        $this->response->getXml()
                    );
                    $this->_paymentUpdate->apply($this->_order->getPayment(), $this->_order);
                    $this->_abortIfPaymentError($this->_paymentUpdate, $orderIncrementId);
                }
            } else {
                $errormessage = $this->paymentservicerequest->getCreditCardSpecificException('CCAM15');
                $this->wplogger->info($errormessage);
                $this->_messageManager->addError(__($errormessage?$errormessage:$e->getMessage()));
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
                return;
            }
        } catch (Exception $e) {
            $errormessage='';
            $this->wplogger->info($e->getMessage());
            if (strpos($e->getMessage(), "Parse error")!==false) {
                $errormessage = $this->paymentservicerequest->getCreditCardSpecificException('CCAM23');
            }
            if ($e->getMessage()=== 'Unique constraint violation found') {
                $this->_messageManager
                        ->addError(__($this->paymentservicerequest
                                ->getCreditCardSpecificException('CCAM22')));
            } else {
                $this->_messageManager->addError(__($errormessage?$errormessage:$e->getMessage()));
            }
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
            return;
        }
    }

    /**
     * help to build url if payment is success
     */
    private function _handleAuthoriseSuccess()
    {
        $this->checkoutSession->setWpResponseForwardUrl(
            $this->urlBuilders->getUrl('checkout/onepage/success', ['_secure' => true])
        );
    }

    /**
     * it handles if payment is refused or cancelled
     * @param  Object $paymentUpdate
     */
    private function _abortIfPaymentError($paymentUpdate, $orderId)
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
            $this->_messageManager->addError(__($this->paymentservicerequest->getCreditCardSpecificException('CCAM9')));
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
            $this->_handleAuthoriseSuccess();
            $this->_updateTokenData($this->response->getXml());
        }
    }

    /**
     * This will Save card
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

    private function _applyTokenUpdate($xmlRequest)
    {
        $tokenService = $this->worldpaytoken;
        $tokenService->updateOrInsertToken(
            new \Sapient\Worldpay\Model\Token\StateXml($xmlRequest),
            $this->_order->getPayment()
        );
    }
}
