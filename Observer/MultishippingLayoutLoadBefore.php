<?php
namespace Sapient\Worldpay\Observer;
   
use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;
use \Magento\Framework\View\LayoutInterface;
use \Sapient\Worldpay\Helper\Data;
use \Sapient\Worldpay\Logger\WorldpayLogger;

class MultishippingLayoutLoadBefore implements ObserverInterface
{
    /**
     * @var \Sapient\Worldpay\Helper\Data $helper
     */
    public $helper;

    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     */
    public $wplogger;

    /**
     * Constructructor
     *
     * @param \Sapient\Worldpay\Helper\Data $helper
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     */
    public function __construct(
        Data $helper,
        WorldpayLogger $wplogger
    ) {
        $this->helper = $helper;
        $this->wplogger = $wplogger;
    }

    /**
     * Change multishipping checkout layout based on configuration
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(Observer $observer)
    {
        $layout = $observer->getEvent()->getLayout();
        $controllerAction = $observer->getEvent()->getFullActionName();
        if ($controllerAction == 'multishipping_checkout_billing') {
            if ($this->helper->isWorldPayEnable() && $this->helper->isMultishippingEnabled()) {
                $layout->getUpdate()->addHandle("wp_multishipping_checkout_billing");
            } else {
                $layout->getUpdate()->addHandle("multishipping_checkout_billing");
            }
        }
    }
}
