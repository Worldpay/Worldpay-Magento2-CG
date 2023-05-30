<?php
namespace Sapient\Worldpay\Controller\Adminhtml\Cancel;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Exception;

/**
 * Description of Index
 *
 * @author aatrai
 */
class Index extends \Magento\Backend\App\Action
{
    /**
     * @var $pageFactory
     */
    protected $pageFactory;

     /**
      * @var \Sapient\Worldpay\Logger\WorldpayLogger
      */
    protected $wplogger;
     /**
      * @var \Sapient\Worldpay\Model\Payment\Service
      */
    protected $paymentservice;
     /**
      * @var \Sapient\Worldpay\Model\Order\Service
      */
    protected $orderservice;

     /**
      * @var JsonFactory
      */
    protected $resultJsonFactory;

     /**
      * @var \Sapient\Worldpay\Model\Token\WorldpayToken
      */
    protected $worldpaytoken;

    /**
     * @var $_rawBody
     */
    protected $_rawBody;
    /**
     * @var $_orderId
     */
    private $_orderId;
    /**
     * @var $_order
     */

    private $_order;
    /**
     * @var $_paymentUpdate
     */
    private $_paymentUpdate;
    /**
     * @var $_tokenState
     */

    private $_tokenState;
    /**
     * @var $helper
     */
    private $helper;
    /**
     * @var $storeManager
     */

    private $storeManager;
    /**
     * @var $abstractMethod
     */
    private $abstractMethod;
    
    /**
     * Constructor
     *
     * @param string $context
     * @param string $resultJsonFactory
     * @param string $wplogger
     * @param string $paymentservice
     * @param string $worldpaytoken
     * @param string $orderservice
     * @param string $storeManager
     * @param string $helper
     * @param string $abstractMethod
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
        \Sapient\Worldpay\Model\PaymentMethods\PaymentOperations $abstractMethod
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
    /**
     * Execute
     *
     * @return string
     */

    public function execute()
    {
        $this->_loadOrder();
        $order = $this->_order->getOrder();
        $storeid = $order->getStoreId();
        $store = $this->storeManager->getStore($storeid)->getCode();
        try {
            $this->abstractMethod->canCancel($this->_order);
            $this->_fetchPaymentUpdate();
            $this->_registerWorldPayModel();
            $this->_applyPaymentUpdate();

        } catch (Exception $e) {
            $this->wplogger->error($e->getMessage());
            $codeErrorMessage = 'Cancel Action Failed';
            $camErrorMessage = $this->helper->getConfigValue('AFR01', $store);
            $codeMessage = 'Order cancelled successfully, Please run Sync Status after sometime.';
            $camMessage = $this->helper->getConfigValue('AFR02', $store);
            $message = $camMessage? $camMessage : $codeMessage;
            $errorMessage = $camErrorMessage? $camErrorMessage : $codeErrorMessage;

            if ($e->getMessage() == 'same state') {
                  $this->messageManager->addSuccess($message);
            } else {
                $this->messageManager->addError($errorMessage .': '. $e->getMessage());
            }
            return $this->_redirectBackToOrderView($order->getId());
        }
        $codeMessage = 'Order cancelled successfully, Please run Sync Status after sometime.';
        $camMessage = $this->helper->getConfigValue('AFR02', $store);
        $message = $camMessage? $camMessage : $codeMessage;
        $this->messageManager->addSuccess($message);
        return $this->_redirectBackToOrderView($order->getId());
    }
    /**
     * Load order data
     */

    private function _loadOrder()
    {
        $this->_orderId = (int) $this->_request->getParam('order_id');
        $this->_order = $this->orderservice->getById($this->_orderId);
    }
    /**
     * FetchPaymentUpdate
     */

    private function _fetchPaymentUpdate()
    {
        $xml = $this->paymentservice->getPaymentUpdateXmlForOrder($this->_order);
        $this->_paymentUpdate = $this->paymentservice->createPaymentUpdateFromWorldPayXml($xml);
        $this->_tokenState = new \Sapient\Worldpay\Model\Token\StateXml($xml);
    }
    /**
     * RegisterWorldPayModel
     */

    private function _registerWorldPayModel()
    {
        $this->paymentservice->setGlobalPaymentByPaymentUpdate($this->_paymentUpdate);
    }
    /**
     * ApplyPaymentUpdate
     */

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
    /**
     * RedirectBackToOrderView
     *
     * @param Int $orderId
     * @return string
     */
    private function _redirectBackToOrderView($orderId)
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath(
            'sales/order/view',
            [
                'order_id' => $orderId
            ]
        );
        return $resultRedirect;
    }
}
