<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Adminhtml\Order;

class Service
{
    public function __construct(\Magento\Backend\Model\Session\Quote $adminsessionquote,
        \Magento\Sales\Model\AdminOrder\Create $adminordercreate
       ){
        $this->adminsessionquote = $adminsessionquote;
        $this->adminordercreate = $adminordercreate;
    }

    public function reactivateAdminQuoteForOrder($worldPayOrder)
    {
        $mageOrder = $worldPayOrder->getOrder();

        $session = $this->adminsessionquote;
        $mageOrder->setReordered(true);
        $session->setStoreId($mageOrder->getStoreId());
        $session->setCustomerId($mageOrder->getCustomerId());
        $session->setUseOldShippingMethod(true);
        $this->adminordercreate->initFromOrder($mageOrder);
    }
    

}
