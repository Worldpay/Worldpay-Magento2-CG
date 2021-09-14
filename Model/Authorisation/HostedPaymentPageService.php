<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Authorisation;

use Exception;

class HostedPaymentPageService extends \Magento\Framework\DataObject
{
   
    /** @var  \Sapient\Worldpay\Model\Checkout\Hpp\State */
    protected $_status;
    /** @var  \Sapient\Worldpay\Model\Response\RedirectResponse */
    protected $_redirectResponseModel;

    /**
     * Constructor
     * @param \Sapient\Worldpay\Model\Mapping\Service $mappingservice
     * @param \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Model\Response\RedirectResponse $redirectresponse
     * @param \Sapient\Worldpay\Helper\Registry $registryhelper
     * @param \Sapient\Worldpay\Model\Checkout\Hpp\State $hppstate
     * @param \Magento\Checkout\Model\Session $checkoutsession
     * @param \Magento\Framework\UrlInterface $urlInterface
     */
    public function __construct(
        \Sapient\Worldpay\Model\Mapping\Service $mappingservice,
        \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Response\RedirectResponse $redirectresponse,
        \Sapient\Worldpay\Helper\Registry $registryhelper,
        \Sapient\Worldpay\Model\Checkout\Hpp\State $hppstate,
        \Magento\Checkout\Model\Session $checkoutsession,
        \Magento\Framework\UrlInterface $urlInterface
    ) {
        $this->mappingservice = $mappingservice;
        $this->paymentservicerequest = $paymentservicerequest;
        $this->wplogger = $wplogger;
        $this->redirectresponse = $redirectresponse;
        $this->registryhelper = $registryhelper;
        $this->checkoutsession = $checkoutsession;
        $this->hppstate = $hppstate;
        $this->_urlInterface = $urlInterface;
    }
    
    /**
     * handles provides authorization data for Hosted Payment Page integration
     * It initiates a  XML request to WorldPay and registers worldpayRedirectUrl
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

        $redirectOrderParams = $this->mappingservice->collectRedirectOrderParameters(
            $orderCode,
            $quote,
            $orderStoreId,
            $paymentDetails
        );

        $response = $this->paymentservicerequest->redirectOrder($redirectOrderParams);
       
        $this->_getStatus()
            ->reset()
            ->init($this->_getRedirectResponseModel()->getRedirectUrl($response));

        $payment->setIsTransactionPending(1);
        $this->registryhelper->setworldpayRedirectUrl($this->_urlInterface->getUrl('worldpay/hostedpaymentpage/pay'));

        $this->checkoutsession->setWpRedirecturl($this->_urlInterface->getUrl('worldpay/hostedpaymentpage/pay'));
    }

    /**
     * Get response model
     *
     * @return  \Sapient\Worldpay\Model\Response\RedirectResponse
     */
    protected function _getRedirectResponseModel()
    {
        if ($this->_redirectResponseModel === null) {
            $this->_redirectResponseModel = $this->redirectresponse;
        }

        return $this->_redirectResponseModel;
    }

    /**
     * Method to retrieve status
     *
     * @return  \Sapient\Worldpay\Model\Checkout\Hpp\State
     */
    protected function _getStatus()
    {
        if ($this->_status === null) {
            $this->_status = $this->hppstate;
        }

        return $this->_status;
    }
}
