<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Adminhtml\motoRedirectResult;

use Sapient\Worldpay\Model\Payment\StateResponse;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Exception;

/**
 * if payment is canceled redirect to admin create order page
 */
class Cancel extends \Magento\Backend\App\Action
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
     * @var $_paymentUpdate
     */
    
    private $_paymentUpdate;

    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Model\Payment\Service $paymentservice
     * @param \Sapient\Worldpay\Model\Request\AuthenticationService $authenticatinservice
     * @param \Sapient\Worldpay\Model\Adminhtml\Order\Service $adminorderservice
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice
     * @param \Sapient\Worldpay\Model\Payment\StateResponse $paymentStateResponse
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Payment\Service $paymentservice,
        \Sapient\Worldpay\Model\Request\AuthenticationService $authenticatinservice,
        \Sapient\Worldpay\Model\Adminhtml\Order\Service $adminorderservice,
        \Sapient\Worldpay\Model\Order\Service $orderservice,
        \Sapient\Worldpay\Model\Payment\StateResponse $paymentStateResponse
    ) {
       
        parent::__construct($context);
        $this->wplogger = $wplogger;
        $this->paymentservice = $paymentservice;
        $this->orderservice = $orderservice;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->adminorderservice = $adminorderservice;
        $this->authenticatinservice = $authenticatinservice;
        $this->paymentStateResponse = $paymentStateResponse;
    }
    /**
     * Execute if payment is canceled
     */
    public function execute()
    {
        $this->wplogger->info('worldpay returned admin cancel url');
        $worldPayOrder = $this->_getWorldPayOrder();

        $notice = $this->_getCancellationNoticeForOrder($worldPayOrder->getOrder());
        $this->messageManager->getMessages(true);
        $this->messageManager->addNotice($notice);
        $this->adminorderservice->reactivateAdminQuoteForOrder($worldPayOrder);

        $params = $this->getRequest()->getParams();
        if ($this->authenticatinservice->requestAuthenticated($params)) {
            $this->_applyPaymentUpdate(
                $this->paymentStateResponse->createFromCancelledResponse($params),
                $worldPayOrder
            );
        }

        return $this->_redirectToCreateOrderPage();
    }

    /**
     * Get getWorldPayOrder
     *
     * @return \Sapient\Worldpay\Model\Order
     */
    private function _getWorldPayOrder()
    {
        return $this->orderservice->getByIncrementId($this->_getOrderIncrementId());
    }

    /**
     * Get Cancellation Notice For Order
     *
     * @param array $order
     * @return string
     */
    private function _getCancellationNoticeForOrder($order)
    {
        $incrementId = $order->getIncrementId();
        $message = $incrementId === null ? __('Order Cancelled'): __('Order #'. $incrementId.' Cancelled');
        return $message;
    }

    /**
     * Get Order by ID
     *
     * @return string
     */
    private function _getOrderIncrementId()
    {
        $params = $this->getRequest()->getParams();
        preg_match('/\^(\d+)-/', $params['orderKey'], $matches);
        return $matches[1];
    }

    /**
     * Get apply Payment Update
     *
     * @param Int $paymentState
     * @param Ayyay $order
     * @return string
     */

    private function _applyPaymentUpdate($paymentState, $order)
    {
        try {
            $this->_paymentUpdate = $this->paymentservice
                        ->createPaymentUpdateFromWorldPayResponse($paymentState);
            $this->_paymentUpdate->apply($order->getPayment(), $order);
        } catch (Exception $e) {
             $this->wplogger->error($e->getMessage());
        }
    }

    /**
     * Redirect Create order page
     *
     * @return string
     */
    private function _redirectToCreateOrderPage()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('sales/order_create/index');
        return $resultRedirect;
    }
}
