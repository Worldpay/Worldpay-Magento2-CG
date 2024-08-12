<?php
namespace Sapient\Worldpay\Controller\Cartdetails;

class Chromepay extends \Magento\Framework\App\Action\Action
{
     /**
      * @var $_pageFactory
      */
    protected $_pageFactory;
    /**
     * @var $_request
     */
    protected $_request;
    /**
     * @var $_paymentservicerequest
     */
    protected $_paymentservicerequest;
    /**
     * @var $_authSession
     */
    protected $_authSession;

    /**
     * @var \Sapient\Worldpay\Helper\Data
     */
    protected $worldpayHelper;
    /**
     * Constructor
     *
     * @param Context $context
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
     * Execute
     *
     * @return string
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
            $browserfields = [
                'browserScreenHeight' => $paymentDetails['additional_data']
                    ['browser_screenheight'],
                'browserScreenWidth' => $paymentDetails['additional_data']
                    ['browser_screenwidth'],
                'browserColourDepth' => $paymentDetails['additional_data']
                    ['browser_colordepth']
            ];
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
            $chromeOrderParams['browserFields'] = $browserfields;
            if ($chromeOrderParams) {
                $response = $this->_paymentservicerequest->chromepayOrder($chromeOrderParams);
            }
        } else {
            $response = false;
        }
            return $response;
    }
}
