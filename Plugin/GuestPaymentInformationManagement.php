<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Plugin;

use Magento\Checkout\Model\GuestPaymentInformationManagement as CheckoutGuestPaymentInformationManagement;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\GuestCartManagementInterface;
use \Sapient\Worldpay\Logger\WorldpayLogger;
use Sapient\Worldpay\Model\MethodList;
use Sapient\Worldpay\Helper\CreditCardException;

 /**
  * Initialize  plugin
  *
  * @return void
  */
class GuestPaymentInformationManagement
{
    /**
     * @var GuestCartManagementInterface
     */
    private $cartManagement;
    /**
     * @var WorldpayLogger
     */
    private $logger;
    /**
     * @var MethodList
     */
    private $methodList;
    /**
     * @var bool
     */
    private $checkMethods;
    
    /**
     * GuestPaymentInformationManagement constructor.
     * @param GuestCartManagementInterface $cartManagement
     * @param WorldpayLogger $logger
     * @param MethodList $methodList
     * @param bool $checkMethods
     */
    public function __construct(
        GuestCartManagementInterface $cartManagement,
        WorldpayLogger $logger,
        MethodList $methodList,
        $checkMethods = true
    ) {
        $this->cartManagement = $cartManagement;
        $this->logger = $logger;
        $this->methodList = $methodList;
        $this->checkMethods = $checkMethods;
    }
    /**
     * Data provider for payment information and place order
     *
     * @param CheckoutGuestPaymentInformationManagement $subject
     * @param \Closure $proceed
     * @param string $cartId
     * @param string $email
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress
     * @return int
     * @throws CouldNotSaveException
     */
    public function aroundSavePaymentInformationAndPlaceOrder(
        CheckoutGuestPaymentInformationManagement $subject,
        \Closure $proceed,
        $cartId,
        $email,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        if ($this->checkMethods && !in_array($paymentMethod->getMethod(), $this->methodList->get())) {
            return $proceed($cartId, $email, $paymentMethod, $billingAddress);
        }
        $subject->savePaymentInformation($cartId, $email, $paymentMethod, $billingAddress);
        try {
            $orderId = $this->cartManagement->placeOrder($cartId);
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage());
            throw new CouldNotSaveException(__($exception->getMessage()));
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            throw new CouldNotSaveException(
                __('An error occurred on the server. Please try to place the order again.'),
                $exception
            );
        }
        return $orderId;
    }
}
