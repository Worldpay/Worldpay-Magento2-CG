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
    /**
     * @var $pageFactory
     */
    protected $pageFactory;
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
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Model\Payment\Service $paymentservice
     * @param \Sapient\Worldpay\Model\Token\WorldpayToken $worldpaytoken
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Sapient\Worldpay\Helper\GeneralException $helper
     * @param \Sapient\Worldpay\Model\PaymentMethods\PaymentOperations $abstractMethod
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
            $this->abstractMethod->canVoidSale($this->_order);
            $this->_fetchPaymentUpdate();
            $this->_registerWorldPayModel();
            $this->_applyPaymentUpdate();

        } catch (Exception $e) {
            $this->wplogger->error($e->getMessage());
            $codeErrorMessage = 'Void Sale Action Failed';
            $camErrorMessage = $this->helper->getConfigValue('AACH01', $store);
            $codeMessage = 'Void Sale executed Successfully, Please run Sync Status after sometime.';
            $camMessage = $this->helper->getConfigValue('AACH02', $store);
            $message = $camMessage? $camMessage : $codeMessage;
            $errorMessage = $camErrorMessage? $camErrorMessage : $codeErrorMessage;

            if ($e->getMessage() == 'same state') {
                  $this->messageManager->addSuccess($message);
            } else {
                $this->messageManager->addError($errorMessage .': '. $e->getMessage());
            }
            return $this->_redirectBackToOrderView($order->getId());
        }
        $codeMessage = 'Void Sale executed Successfully, Please run Sync Status after sometime.';
        $camMessage = $this->helper->getConfigValue('AACH02', $store);
        $message = $camMessage? $camMessage : $codeMessage;
        $this->messageManager->addSuccess($message);
        return $this->_redirectBackToOrderView($order->getId());
    }
    /**
     * Load Order by order id
     *
     * @return string
     */
    private function _loadOrder()
    {
        $this->_orderId = (int) $this->_request->getParam('order_id');
        $this->_order = $this->orderservice->getById($this->_orderId);
    }
    /**
     * FetchPaymentUpdate
     *
     * @return string
     */
    private function _fetchPaymentUpdate()
    {
        $xml = $this->paymentservice->getPaymentUpdateXmlForOrder($this->_order);
        $this->_paymentUpdate = $this->paymentservice->createPaymentUpdateFromWorldPayXml($xml);
        $this->_tokenState = new \Sapient\Worldpay\Model\Token\StateXml($xml);
    }
    /**
     * Register WorldPay Model
     *
     * @return string
     */
    private function _registerWorldPayModel()
    {
        $this->paymentservice->setGlobalPaymentByPaymentUpdate($this->_paymentUpdate);
    }
    /**
     * Apply Payment Update
     *
     * @return string
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
     * Redirect BackTo Order View
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
