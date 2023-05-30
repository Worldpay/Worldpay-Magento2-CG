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
  * Redirect to the admin view order page if order is failed
  */
class Success extends \Magento\Backend\App\Action
{
    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    protected $wplogger;

    /**
     * @var \Sapient\Worldpay\Model\Order\Service
     */

    protected $orderservice;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Model\Order\Service $orderservice
     */
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
    
    /**
     * Execute if payment is success redirect to admin view order page
     */
    public function execute()
    {
         $this->wplogger->info('worldpay returned admin success url');
        $worldPayOrder = $this->_getWorldPayOrder();
        return $this->_redirectToOrderViewPage($worldPayOrder);
    }

    /**
     * Get Worldpay Order
     *
     * @return \Sapient\Worldpay\Model\Order
     */
    private function _getWorldPayOrder()
    {
        return $this->orderservice->getByIncrementId($this->_getOrderIncrementId());
    }

    /**
     * Get Order Increment Id
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
     * Redirect to order view page
     *
     * @param array $worldPayOrder
     * @return string
     */
    private function _redirectToOrderViewPage($worldPayOrder)
    {
        $order = $worldPayOrder->getOrder();
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('sales/order/view', ['order_id' => $order->getId()]);
        return $resultRedirect;
    }
}
