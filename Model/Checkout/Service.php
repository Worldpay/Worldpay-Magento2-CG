<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Checkout;

use Magento\Checkout\Model\Cart as CustomerCart;

class Service
{

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutsession,
        CustomerCart $cart,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
    ) {

        $this->checkoutsession = $checkoutsession;
        $this->cart = $cart;
        $this->wplogger = $wplogger;
    }
    
    public function clearSession()
    {
        $this->checkoutsession->clearQuote();
    }

    public function reactivateQuoteForOrder(\Sapient\Worldpay\Model\Order $order)
    {

        $mageOrder = $order->getOrder();
        if ($mageOrder->isObjectNew()) {
            return;
        }

        $this->checkoutsession->restoreQuote();
        $this->cart->save();
        $this->wplogger->info('cart restored');

    }

}
