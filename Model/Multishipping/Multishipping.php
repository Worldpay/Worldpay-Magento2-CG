<?php
/**
 * Sapient 2022
 */

namespace Sapient\Worldpay\Model\Multishipping;

class Multishipping extends \Magento\Framework\Model\AbstractModel
{

    /**
     * @var \Sapient\Worldpay\Helper\Data
     */
    private $worldpayHelper;
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

     /**
      * @var \Sapient\Worldpay\Helper\Multishipping
      */
    protected $multishippingHelper;
    /**
     * @var \Sapient\Worldpay\Helper\Data
     */
    protected $helper;

    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    protected $wplogger;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    
    /**
     * Service constructor.
     *
     * @param \Sapient\Worldpay\Helper\Multishipping $multishippingHelper
     * @param \Sapient\Worldpay\Helper\Data $helper
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     */
    public function __construct(
        \Sapient\Worldpay\Helper\Multishipping $multishippingHelper,
        \Sapient\Worldpay\Helper\Data $helper,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        $this->multishippingHelper = $multishippingHelper;
        $this->helper = $helper;
        $this->wplogger = $wplogger;
        $this->checkoutSession = $checkoutSession;
        $this->jsonHelper = $jsonHelper;
    }
    /**
     * Places a multishipping order
     *
     * @api
     * @param int|null $cartId
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
     *
     * @return mixed|null $result
     */
    public function placeMultishippingOrder(
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        try {
            $quote = $this->helper->getQuote();
            $quoteId = $quote->getId();
            $quote->getPayment()->importData($paymentMethod->getData());
            $cc_type = $paymentMethod['additional_data']['cc_type'];
            if ($paymentMethod['method'] == "worldpay_paybylink" &&
                $paymentMethod['additional_data']['cc_type'] == "ALL") {
                    $this->checkoutSession->setAuthenticationOrderId(
                        $this->checkoutSession->getData('authentication_order_id')
                    );
            }
            $response = $this->multishippingHelper->placeMultishippingOrder($quoteId, $cc_type);
            return $this->jsonHelper->jsonEncode($response);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }
}
