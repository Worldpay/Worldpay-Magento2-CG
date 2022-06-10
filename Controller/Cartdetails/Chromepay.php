<?php
namespace Sapient\Worldpay\Controller\Cartdetails;

class Chromepay extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;
    /**
     * @var RequestInterface
     */
    protected $_request;
    /**
     * Worldpay Payment Service Request
     *
     * @var \Sapient\Worldpay\Model\Request\PaymentServiceRequest
     **/
    protected $_paymentservicerequest;
    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_authSession;
    
    /**
     * Chromepay constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Sapient\Worldpay\Helper\Data $worldpayHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\App\Request\Http $request,
        \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Sapient\Worldpay\Helper\Data $worldpayHelper
    ) {
        $this->_pageFactory = $pageFactory;
        $this->_request = $request;
        $this->_paymentservicerequest = $paymentservicerequest;
        $this->_authSession = $authSession;
                $this->worldpayHelper = $worldpayHelper;
        return parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return string
     * @throws \Exception
     */
    public function execute()
    {
            $data = $this->getRequest()->getParams();
            $orderCode = $this->_authSession->getOrderCode();
            $currencyCode = $this->_authSession->getCurrencyCode();
            $exponent = $this->worldpayHelper->getCurrencyExponent($currencyCode);
        if ($data && $orderCode) {
            $reqData = json_decode($data['data']);
            $paymentDetails = $reqData->details;
            $shippingAddress = $reqData->shippingAddress;
            $billingAddress = $paymentDetails->billingAddress;
            $chromeOrderParams = [];
            $chromeOrderParams['orderCode'] = $orderCode;
            $chromeOrderParams['merchantCode'] = $this->worldpayHelper->getMerchantCode();
            $chromeOrderParams['orderDescription'] = $this->worldpayHelper->getOrderDescription();
            $chromeOrderParams['currencyCode'] = $currencyCode;
            $chromeOrderParams['amount'] = $data['totalAmount'];
            $chromeOrderParams['paymentType'] = $data['cardType'];
            $chromeOrderParams['paymentDetails'] = $paymentDetails;
            $chromeOrderParams['shopperEmail'] = $reqData->payerEmail;
            $chromeOrderParams['shippingAddress'] = $shippingAddress;
            $chromeOrderParams['billingAddress'] = $billingAddress;
            $chromeOrderParams['exponent'] = $exponent;
            if ($chromeOrderParams) {
                $response = $this->_paymentservicerequest->chromepayOrder($chromeOrderParams);
            }
        } else {
            $response = false;
        }
            return $response;
    }
}
