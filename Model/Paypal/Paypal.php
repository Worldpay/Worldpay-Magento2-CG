<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Paypal;

use Magento\Sales\Api\OrderRepositoryInterface;
use Sapient\Worldpay\Api\PaypalInterface;
use Sapient\Worldpay\Api\UpdateRecurringTokenInterface;

use Magento\Framework\Registry;
use Sapient\Worldpay\Model\PaymentMethods\PaymentOperations;
use Sapient\Worldpay\Model\Recurring\Order\EditSubscriptionHistoryRepository;
use Sapient\Worldpay\Model\Recurring\Order\Magento;
use Sapient\Worldpay\Model\Recurring\Order\RateCollectorInterfaceFactory;
use Sapient\Worldpay\Model\Recurring\Order\RateRequestFactory;
use Sapient\Worldpay\Model\Recurring\Order\StoreManagerInterface;
use Sapient\Worldpay\Model\Recurring\Order\Subscription;
use Sapient\Worldpay\Model\Recurring\Order\Transactions;

class Paypal implements PaypalInterface
{
    protected $orderRepository;
    protected $paymentOperations;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        PaymentOperations $paymentOperation
    ) {
        $this->orderRepository = $orderRepository;
        $this->paymentOperations = $paymentOperation;
    }


    /**
     * Retrieves the PayPal Id for an order.
     *
     * @return string The PayPal order ID.
     */
    public function getPaypalOrderId(int $orderId): string
    {
        $order = $this->orderRepository->get($orderId);
        $payment = $order->getPayment();
        $paypalOrderId = $payment->getAdditionalInformation('paypal_order_id');

        return $paypalOrderId;
    }

    /**
     * Cancel order by order ID.
     *
     * @return string
     */
    public function approveOrder(int $orderId)
    {
        $order = $this->orderRepository->get($orderId);
        try{
            $this->paymentOperations->approveOrder($order);
            return json_encode([
                'success' => true,
                'message' => 'Payment approved successfully',
            ]);
        } catch (\Exception $exception) {
            return json_encode([
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
