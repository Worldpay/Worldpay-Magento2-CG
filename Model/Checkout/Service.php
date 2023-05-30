<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Checkout;

use Magento\Checkout\Model\Cart as CustomerCart;

class Service
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutsession;

    /**
     * @var CustomerCart
     */
    private $cart;

    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    private $wplogger;
    /**
     * Constructor
     *
     * @param Session $checkoutsession
     * @param CustomerCart $cart
     * @param WorldpayLogger $wplogger
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
    /**
     * Get clear Session
     */
    public function clearSession()
    {
        $this->checkoutsession->clearQuote();
    }
    /**
     * Get clear Session
     *
     * @param Order $order
     */
    
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
