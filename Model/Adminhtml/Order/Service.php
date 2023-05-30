<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Adminhtml\Order;

class Service
{
    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $adminsessionquote;

    /**
     * @var \Magento\Sales\Model\AdminOrder\Create
     */
    protected $adminordercreate;
    /**
     * Service constructor
     *
     * @param \Magento\Backend\Model\Session\Quote $adminsessionquote
     * @param \Magento\Sales\Model\AdminOrder\Create $adminordercreate
     */
    public function __construct(
        \Magento\Backend\Model\Session\Quote $adminsessionquote,
        \Magento\Sales\Model\AdminOrder\Create $adminordercreate
    ) {
        $this->adminsessionquote = $adminsessionquote;
        $this->adminordercreate = $adminordercreate;
    }

    /**
     * Reactivate admin quote for order
     *
     * @param array $worldPayOrder
     * @return mixed
     */
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
