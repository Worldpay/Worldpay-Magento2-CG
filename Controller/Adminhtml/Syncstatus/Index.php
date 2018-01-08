<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Adminhtml\Syncstatus;

use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Exception;

/**
 * Sync payment details in worldpay
 */
class Index extends \Magento\Backend\App\Action
{
    protected $pageFactory;
    protected $_rawBody;

    private $_orderId;
    private $_order;
    private $_paymentUpdate;
    private $_tokenState;

    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Model\Payment\Service $paymentservice,
     * @param \Sapient\Worldpay\Model\Token\WorldpayToken $worldpaytoken,    
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice
     */
    public function __construct(Context $context,  JsonFactory $resultJsonFactory,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Payment\Service $paymentservice,
        \Sapient\Worldpay\Model\Token\WorldpayToken $worldpaytoken,
        \Sapient\Worldpay\Model\Order\Service $orderservice
    ) { 
       
        parent::__construct($context);
        $this->wplogger = $wplogger;
        $this->paymentservice = $paymentservice;
        $this->orderservice = $orderservice;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->worldpaytoken = $worldpaytoken;

    }
 
    public function execute()
    {
    
         $this->_loadOrder();

        try {
            $this->_fetchPaymentUpdate();
            $this->_registerWorldPayModel();
            $this->_applyPaymentUpdate();
            $this->_applyTokenUpdate();
            
        } catch (Exception $e) {
            $this->wplogger->error($e->getMessage());
             if ($e->getMessage() == 'same state') {
                  $this->messageManager->addSuccess('Payment synchronized successfully!!');
             } else {
                $this->messageManager->addError('Synchronising Payment Status failed: ' . $e->getMessage());
             }
            return $this->_redirectBackToOrderView();
        }

        $this->messageManager->addSuccess('Payment synchronized successfully!!');
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
            $this->_paymentUpdate->apply($this->_order->getPayment(),$this->_order);
        } catch (Exception $e) {
            $this->wplogger->error($e->getMessage());
            throw new Exception($e->getMessage());   
        }
    }

    private function _applyTokenUpdate()
    {
        $this->worldpaytoken->updateOrInsertToken($this->_tokenState);
    }

    private function _redirectBackToOrderView()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->_redirect->getRefererUrl());
        return $resultRedirect; 
    }



 
 
}