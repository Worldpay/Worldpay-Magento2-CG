<?php


namespace Sapient\Worldpay\Observer\Backend\Sales;

class OrderInvoicePay implements \Magento\Framework\Event\ObserverInterface
{

    
    public function __construct (
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentServiceReq,
        \Sapient\Worldpay\Model\Worldpayment $worldpaypaymentmodel,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->wplogger = $wplogger;
        $this->paymentServiceReq = $paymentServiceReq;
        $this->worldpaypaymentmodel = $worldpaypaymentmodel;
        $this->scopeConfig = $scopeConfig;
        $this->messageManager = $messageManager;
        $this->_request = $request;
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
        
       
         
        $invoice = $this->_request->getPost();
        
       
        if(!isset($invoice['invoice'])) {
            
            return;
        }
        
       
        
        
        
        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();
        
        
            
         // check the payment has been captured already or not.
         $wp = $this->worldpaypaymentmodel->loadByPaymentId($order->getIncrementId());
        
         
         
          $rawXml = $this->paymentServiceReq->inquiry(
            $wp->getMerchantId(),
            $wp->getWorldpayOrderId(),
            $wp->getStoreId(),
            $order->getPaymentMethodCode(),
            $wp->getPaymentType()
        );
          
          
          
        $paymentService = new \SimpleXmlElement($rawXml);
        $lastEvent = $paymentService->xpath('//lastEvent');
        
       
        
       //Check for partial capture
        if(floatval($invoice->getGrandTotal()) !=floatval($order->getGrandTotal())) {
            if ($lastEvent && $lastEvent[0] =='CAPTURED') {
            
            throw new \Magento\Framework\Exception\CouldNotDeleteException(__("Partial Capture is not possible. The Payment is already Captured."));       
                
        }
            
        }
        
       
         
       $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        $autoInvoice = $this->scopeConfig->getValue('worldpay/general_config/capture_automatically', $storeScope);
        $partialCapture = $this->scopeConfig->getValue('worldpay/cc_config/partial_capture', $storeScope);

        
        
         
         
        //check the partial capture is enabled and auto invocie is disabled. if so do the partial capture.
       if($partialCapture && !$autoInvoice) {
           
           //total amount from invoice and order should not be same for partial capture
           if(floatval($invoice->getGrandTotal()) !=floatval($order->getGrandTotal())) {
            
            $this->paymentServiceReq->partialCapture(
                $order,
                $wp,
                $invoice->getGrandTotal()
            );
        }
        
       }else {
           //normal capture
           $paymentMethod ='';
           $this->paymentServiceReq->capture(
               $order,
               $wp,
               $paymentMethod
            );
       }
       
        
    }
}
