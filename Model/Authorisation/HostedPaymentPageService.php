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
     * @var \Sapient\Worldpay\Helper\Registry
     */
    protected $registryhelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutsession;

    /**
     * @var \Sapient\Worldpay\Model\Checkout\Hpp\State
     */
    protected $hppstate;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlInterface;
    /**
     * @var \Sapient\Worldpay\Model\Response\RedirectResponse
     */
    protected $redirectresponse;

    /**
     * @var \Sapient\Worldpay\Helper\Multishipping
     */
    protected $multishippingHelper;

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
     * @param \Sapient\Worldpay\Helper\Multishipping $multishippingHelper
     */
    public function __construct(
        \Sapient\Worldpay\Model\Mapping\Service $mappingservice,
        \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Response\RedirectResponse $redirectresponse,
        \Sapient\Worldpay\Helper\Registry $registryhelper,
        \Sapient\Worldpay\Model\Checkout\Hpp\State $hppstate,
        \Magento\Checkout\Model\Session $checkoutsession,
        \Magento\Framework\UrlInterface $urlInterface,
        \Sapient\Worldpay\Helper\Multishipping $multishippingHelper
    ) {
        $this->mappingservice = $mappingservice;
        $this->paymentservicerequest = $paymentservicerequest;
        $this->wplogger = $wplogger;
        $this->redirectresponse = $redirectresponse;
        $this->registryhelper = $registryhelper;
        $this->checkoutsession = $checkoutsession;
        $this->hppstate = $hppstate;
        $this->_urlInterface = $urlInterface;
        $this->multishippingHelper = $multishippingHelper;
    }
    /**
     * Handles provides authorization data for Hosted Payment Page integration
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
        if ($this->multishippingHelper->isMultiShipping($quote)) {
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
        if (empty($this->checkoutsession->getIframePay())) {
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
            $this->registryhelper->setworldpayRedirectUrl(
                $this->_urlInterface->getUrl('worldpay/hostedpaymentpage/pay')
            );

            $this->checkoutsession->setWpRedirecturl($this->_urlInterface->getUrl('worldpay/hostedpaymentpage/pay'));
        }
    }

    /**
     * Get redirect response model
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
     * Get hosted payment page status
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
