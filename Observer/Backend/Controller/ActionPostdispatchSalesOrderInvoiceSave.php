<?php


namespace Sapient\Worldpay\Observer\Backend\Controller;

class ActionPostdispatchSalesOrderInvoiceSave implements \Magento\Framework\Event\ObserverInterface
{

    protected $orderManagement;
    
    public function __construct(
    \Magento\Sales\Api\OrderManagementInterface $orderManagement
    ) {
        $this->orderManagement = $orderManagement;
    }

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
       
       $uri =  preg_split ("/\//", $_SERVER['REQUEST_URI']);  
        
       $orderId = $uri[6];
       
        
         try {
        $this->orderManagement->cancel($orderId);
        
            } catch (\Exception $e) {
                echo $e->errorMessage();
            }
        
    }
}
