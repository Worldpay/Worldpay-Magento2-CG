<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sapient\Worldpay\Model\Checkout;

use Magento\Checkout\Model\Cart as CustomerCart;

class Service
{

    /**
     * Service constructor
     *
     * @param \Magento\Checkout\Model\Session $checkoutsession
     * @param CustomerCart $cart
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     */
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
