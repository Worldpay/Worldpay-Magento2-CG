<?php
namespace Sapient\Worldpay\Controller\Cartdetails;

class Chromepay extends \Magento\Framework\App\Action\Action
{
	protected $_pageFactory;
	protected $_request;
	protected $_paymentservicerequest;
	protected $_authSession;

	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $pageFactory,
                \Magento\Framework\App\Request\Http $request,
                \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest,
                \Magento\Backend\Model\Auth\Session $authSession,
                \Sapient\Worldpay\Helper\Data $worldpayHelper)
	{
		$this->_pageFactory = $pageFactory;
		$this->_request = $request;
		$this->_paymentservicerequest = $paymentservicerequest;
		$this->_authSession = $authSession;
                $this->worldpayHelper = $worldpayHelper;
		return parent::__construct($context);
	}

	public function execute()
	{
            $data = $this->getRequest()->getParams();
            $orderCode = $this->_authSession->getOrderCode();
            $currencyCode = $this->_authSession->getCurrencyCode();
            if($data && $orderCode){
                $reqData = json_decode($data['data']);
                $paymentDetails = $reqData->details;
                $shippingAddress = $reqData->shippingAddress;
                $billingAddress = $paymentDetails->billingAddress;
                $chromeOrderParams = array();
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
                if($chromeOrderParams){
                    $response = $this->_paymentservicerequest->chromepayOrder($chromeOrderParams);
                }
            } else{
                $response = false;
            }
            return $response;
	}
}