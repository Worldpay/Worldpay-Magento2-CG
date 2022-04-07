<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Adminhtml\Syncstatus;

use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Exception;
use Sapient\Worldpay\Helper\GeneralException;

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
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice,
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
        \Sapient\Worldpay\Model\PaymentMethods\PaymentOperations $abstractMethod,
        \Sapient\Worldpay\Helper\GeneralException $helper
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
        $order = $this->_order->getOrder();
        $storeid = $order->getStoreId();
        $store = $this->storeManager->getStore($storeid)->getCode();
        try {
            $this->_fetchPaymentUpdate();
            $this->_registerWorldPayModel();
            $this->_applyPaymentUpdate();
            $this->_updateOrderStatus();
            $this->_applyTokenUpdate();

        } catch (Exception $e) {
            $this->wplogger->error($e->getMessage());
            if ($e->getMessage() == 'same state') {
                 $this->messageManager->addSuccess($this->helper->getConfigValue('ACAM3', $store));
            } else {
                $this->messageManager->addError(
                    $this->helper->getConfigValue('ACAM4', $store).': ' . $e->getMessage()
                );
            }
            return $this->_redirectBackToOrderView($order->getId());
        }

        $this->messageManager->addSuccess($this->helper->getConfigValue('ACAM3', $store));
        return $this->_redirectBackToOrderView($order->getId());
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

    private function _applyTokenUpdate()
    {
        $this->worldpaytoken->updateOrInsertToken($this->_tokenState, $this->_order->getPayment());
    }

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
    private function _updateOrderStatus()
    {
        $this->abstractMethod->updateOrderStatusForVoidSale($this->_order);
        $this->abstractMethod->updateOrderStatusForCancelOrder($this->_order);
    }
}
