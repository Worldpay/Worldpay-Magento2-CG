<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Controller\Adminhtml\motoRedirectResult;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Exception;
 
class Success extends \Magento\Backend\App\Action
{
  
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Order\Service $orderservice
    ) { 
       
        parent::__construct($context);
        $this->wplogger = $wplogger;
        $this->orderservice = $orderservice;
        $this->resultJsonFactory = $resultJsonFactory;
    }
 
    public function execute()
    {
         $this->wplogger->info('worldpay returned admin success url');
        $worldPayOrder = $this->_getWorldPayOrder();
        return $this->_redirectToOrderViewPage($worldPayOrder);
    }


    private function _getWorldPayOrder()
    {
        return $this->orderservice->getByIncrementId($this->_getOrderIncrementId());
    }

    private function _getOrderIncrementId()
    {
        $params = $this->getRequest()->getParams();
        preg_match('/\^(\d+)-/', $params['orderKey'], $matches);

        return $matches[1];
    }
    
    private function _redirectToOrderViewPage($worldPayOrder)
    {
        $order = $worldPayOrder->getOrder();
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('sales/order/view', ['order_id' => $order->getId()]);
        return $resultRedirect; 
    }
 
}