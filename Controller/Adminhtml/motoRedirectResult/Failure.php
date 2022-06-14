<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Adminhtml\motoRedirectResult;

use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Exception;

/**
 * Redirect to the admin create order page  if order is failed
 */
class Failure extends \Magento\Backend\App\Action
{
  
    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Model\Adminhtml\Order\Service $adminorderservice
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Adminhtml\Order\Service $adminorderservice,
        \Sapient\Worldpay\Model\Order\Service $orderservice
    ) {
       
        parent::__construct($context);
        $this->wplogger = $wplogger;
        $this->orderservice = $orderservice;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->adminorderservice = $adminorderservice;
    }
    
    /**
     * Execute if payment is failed redirect to admin create order page
     */
    public function execute()
    {
        $this->wplogger->info('worldpay returned admin failure url');
        $worldPayOrder = $this->_getWorldPayOrder();
        $notice = $this->_getFailureNoticeForOrder($worldPayOrder->getOrder());
        $this->messageManager->getMessages(true);
        $this->messageManager->addNotice($notice);
        $this->adminorderservice->reactivateAdminQuoteForOrder($worldPayOrder);
        return $this->_redirectToCreateOrderPage();
    }

    /**
     * Get WorldPay Order
     *
     * @return \Sapient\Worldpay\Model\Order
     */
    private function _getWorldPayOrder()
    {
        return $this->orderservice->getByIncrementId($this->_getOrderIncrementId());
    }

    /**
     * Show Failure Notice for order
     *
     * @param string $order
     * @return string
     */
    private function _getFailureNoticeForOrder($order)
    {
        return __('Order #'.$order->getIncrementId().' failed');
    }

    /**
     * Return order increment ID
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
     * Redirect To Create Order Page
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
