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
        \Magento\Customer\Model\Session $customerSession
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
    }
    public function continuePost3dSecureAuthorizationProcess($paResponse, $directOrderParams, $threeDSecureParams)
    {
        $directOrderParams['paResponse'] = $paResponse;
        $directOrderParams['echoData'] = $threeDSecureParams->getEchoData();
        try {
            $response = $this->paymentservicerequest->order3DSecure($directOrderParams);
            $this->response = $this->directResponse->setResponse($response);
            $orderIncrementId = current(explode('-', $directOrderParams['orderCode']));
            $this->_order = $this->orderservice->getByIncrementId($orderIncrementId);
            $this->_paymentUpdate = $this->paymentservice->createPaymentUpdateFromWorldPayXml($this->response->getXml());
            $this->_paymentUpdate->apply($this->_order->getPayment(), $this->_order);
            $this->_abortIfPaymentError($this->_paymentUpdate);
        } catch (Exception $e) {
            $this->wplogger->info($e->getMessage());
            $this->_messageManager->addError(__($e->getMessage()));
            $this->checkoutSession->setWpResponseForwardUrl(
                  $this->urlBuilders->getUrl(self::CART_URL, ['_secure' => true])
            );
            return;
        }

    }

    /**
     * help to build url if payment is success
     */
    private function _handleAuthoriseSuccess()
    {
        $this->checkoutSession->setWpResponseForwardUrl(
            $this->urlBuilders->getUrl('checkout/onepage/success',array('_secure' => true))
        );
    }

    /**
     * it handles if payment is refused or cancelled
     * @param  Object $paymentUpdate
     */
    private function _abortIfPaymentError($paymentUpdate)
    {
        if ($paymentUpdate instanceof \Sapient\WorldPay\Model\Payment\Update\Refused) {
          $this->_messageManager->addError(__('Unfortunately the order could not be processed. Please contact us or try again later.'));
             $this->checkoutSession->setWpResponseForwardUrl(
              $this->urlBuilders->getUrl(self::CART_URL, ['_secure' => true])
            );
        } elseif ($paymentUpdate instanceof \Sapient\WorldPay\Model\Payment\Update\Cancelled) {
            $this->_messageManager->addError(__('Unfortunately the order could not be processed. Please contact us or try again later.'));
            $this->checkoutSession->setWpResponseForwardUrl(
              $this->urlBuilders->getUrl(self::CART_URL, ['_secure' => true])
            );
        } else {
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
                $this->updateWorldPayPayment->create()->saveTokenData($tokenData, $paymentData, $merchantCode);
            }
            $this->customerSession->unsIsSavedCardRequested();
        }
    }
}
