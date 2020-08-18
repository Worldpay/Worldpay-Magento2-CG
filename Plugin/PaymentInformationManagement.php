<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Plugin;

use Magento\Checkout\Model\PaymentInformationManagement as CheckoutPaymentInformationManagement;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartManagementInterface;
use Sapient\Worldpay\Model\MethodList;
use \Sapient\Worldpay\Logger\WorldpayLogger;
use Sapient\Worldpay\Helper\CreditCardException;

/**
 * Class PaymentInformationManagement helps to manage WP payment actions
 */
class PaymentInformationManagement
{
    /**
     * @var CartManagementInterface
     */
    private $cartManagement;
    /**
     * @var LoggerInterface
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
     * PaymentInformationManagement constructor.
     * @param CartManagementInterface $cartManagement
     * @param LoggerInterface $logger
     * @param MethodList $methodList
     * @param bool $checkMethods
     */
    public function __construct(
        CartManagementInterface $cartManagement,
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
     * @param CheckoutPaymentInformationManagement $subject
     * @param \Closure $proceed
     * @param $cartId
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress
     * @return int
     * @throws CouldNotSaveException
     */
    public function aroundSavePaymentInformationAndPlaceOrder(
        CheckoutPaymentInformationManagement $subject,
        \Closure $proceed,
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        if ($this->checkMethods && !in_array($paymentMethod->getMethod(), $this->methodList->get())) {
            return $proceed($cartId, $paymentMethod, $billingAddress);
        }
        $subject->savePaymentInformation($cartId, $paymentMethod, $billingAddress);
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
