<?php

/**
 * Sapient 2022
 */

namespace Sapient\Worldpay\Api;

interface MultishippingInterface
{
    /**
     * Place a multishipping order
     *
     * @api
     * @param int|null $cartId
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface $billingAddress
     *
     * @return mixed|null $result
     */
    public function placeMultishippingOrder(
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        ?\Magento\Quote\Api\Data\AddressInterface $billingAddress
    );
}
