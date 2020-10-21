<?php
/**
 * @copyright 2020 Sapient
 */
namespace Sapient\Worldpay\Controller\Adminhtml\VoidSale;

use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Exception;
use Sapient\Worldpay\Helper\GeneralException;

/**
 * Void Sale details in worldpay
 */
class Index extends \Magento\Backend\App\Action
{
    protected $pageFactory;
    protected $_rawBody;
    private $_orderId;
    private $_order;
    private $_paymentUpdate;
    private $_tokenState;
    private $helper;
    private $storeManager;
    private $abstractMethod;

    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Model\Payment\Service $paymentservice,
     * @param \Sapient\Worldpay\Model\Token\WorldpayToken $worldpaytoken,
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
       @param \Sapient\Worldpay\Helper\GeneralException $helper
       @param \Sapient\Worldpay\Model\PaymentMethods\AbstractMethod $abstractMethod
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Payment\Service $paymentservice,
        \Sapient\Worldpay\Model\Token\WorldpayToken $worldpaytoken,
        \Sapient\Worldpay\Model\Order\Service $orderservice,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Sapient\Worldpay\Helper\GeneralException $helper,
        \Sapient\Worldpay\Model\PaymentMethods\AbstractMethod $abstractMethod
    ) {

        parent::__construct($context);
        $this->wplogger = $wplogger;
        $this->paymentservice = $paymentservice;
        $this->orderservice = $orderservice;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->worldpaytoken = $worldpaytoken;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->abstractMethod = $abstractMethod;
    }

    public function execute()
    {
        $this->_loadOrder();
        $storeid = $this->_order->getOrder()->getStoreId();
        $store = $this->storeManager->getStore($storeid)->getCode();
        try {
            $this->abstractMethod->canVoidSale($this->_order);
            $this->_fetchPaymentUpdate();
            $this->_registerWorldPayModel();
            $this->_applyPaymentUpdate();

        } catch (Exception $e) {
            $this->wplogger->error($e->getMessage());
            $codeErrorMessage = 'Void Sale Action Failled!!!';
            $camErrorMessage = $this->helper->getConfigValue('AACH01', $store);
            $codeMessage = 'Void Sale executed Successfully!!! Please run Sync Status after sometime.';
            $camMessage = $this->helper->getConfigValue('AACH02', $store);
            $message = $camMessage? $camMessage : $codeMessage;
            $errorMessage = $camErrorMessage? $camErrorMessage : $codeErrorMessage;

            if ($e->getMessage() == 'same state') {
                  $this->messageManager->addSuccess($message);
            } else {
                $this->messageManager->addError($errorMessage .': '. $e->getMessage());
            }
            return $this->_redirectBackToOrderView();
        }
        $codeMessage = 'Void Sale executed Successfully!!! Please run Sync Status after sometime.';
        $camMessage = $this->helper->getConfigValue('AACH02', $store);
        $message = $camMessage? $camMessage : $codeMessage;
        $this->messageManager->addSuccess($message);
        return $this->_redirectBackToOrderView();
    }

    private function _loadOrder()
    {
        $this->_orderId = (int) $this->_request->getParam('order_id');
        $this->_order = $this->orderservice->getById($this->_orderId);
    }

    private function _fetchPaymentUpdate()
    {
        $xml = $this->paymentservice->getPaymentUpdateXmlForOrder($this->_order);
        $this->_paymentUpdate = $this->paymentservice->createPaymentUpdateFromWorldPayXml($xml);
        $this->_tokenState = new \Sapient\Worldpay\Model\Token\StateXml($xml);
    }

    private function _registerWorldPayModel()
    {
        $this->paymentservice->setGlobalPaymentByPaymentUpdate($this->_paymentUpdate);
    }

    private function _applyPaymentUpdate()
    {
        try {
            $this->_paymentUpdate->apply($this->_order->getPayment(), $this->_order);
        } catch (Exception $e) {
            $this->wplogger->error($e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }

    private function _redirectBackToOrderView()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }
}
