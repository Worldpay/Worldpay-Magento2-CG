<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model;

use Exception;
use Sapient\Worldpay\Helper\CreditCardException;

class Order
{
    /** @var Order */
    private $_order;
    
    /**
     * Constructor
     *
     * @param array $args
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Framework\DB\Transaction $transaction
     * @param \Sapient\Worldpay\Model\Worldpayment $worldpaypaymentmodel
     * @param \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory
     * @param \Magento\Sales\Model\Order\Invoice $Invoice
     * @param \Magento\Sales\Model\Service\CreditmemoService $CreditmemoService
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @param \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository
     */
    public function __construct(
        array $args,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction,
        \Sapient\Worldpay\Model\Worldpayment $worldpaypaymentmodel,
        \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory,
        \Magento\Sales\Model\Order\Invoice $Invoice,
        \Magento\Sales\Model\Service\CreditmemoService $CreditmemoService,
        \Magento\Sales\Model\Order\Creditmemo $creditmemo,
        \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository
    ) {
        $this->_order = $args['order'];
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->worldpaypaymentmodel = $worldpaypaymentmodel;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->CreditmemoService = $CreditmemoService;
        $this->Invoice = $Invoice;
        $this->ordercreditmemo = $creditmemo;
        $this->creditmemoRepository = $creditmemoRepository;
    }

    /**
     * GetOrder
     */
    public function getOrder()
    {
        return $this->_order;
    }

    /**
     * Retrieve Store Id
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->getOrder()->getPayment()->getStoreId();
    }

    /**
     * Retrieve payment Method
     *
     * @return string
     */
    public function getPaymentMethodCode()
    {
        return $this->getOrder()->getPayment()->getMethod();
    }

    /**
     * Get payment Type
     *
     * @return string
     */
    public function getPaymentType()
    {
        return $this->getWorldPayPayment()->getPaymentType();
    }

    /**
     * Set order status as processing
     */
    public function setOrderAsProcessing()
    {
        $mageOrder = $this->getOrder();
        $mageOrder->load($mageOrder->getId());
        if ($mageOrder->getState() == \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT) {
            $mageOrder->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true);
            $mageOrder->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
            $mageOrder->save();
        }
    }

    /**
     * Capture
     */
    public function capture()
    {
        if (!$this->_canInvoice()) {
            return;
        }

        $this->_invoiceOrder();
    }

     /**
      * Cancel order
      */
    public function cancel()
    {
        $mageOrder = $this->getOrder();

        if ($mageOrder->canCancel()) {
            $mageOrder->cancel()->save();
        }
    }

    /**
     * Get Payment
     *
     * @return Magento\Sales\Model\Order\Payment
     */
    public function getPayment()
    {
        return $this->getOrder()->getPayment();
    }

    /**
     * Get payment status
     *
     * @return string
     */
    public function getPaymentStatus()
    {
        return $this->getWorldPayPayment()->getPaymentStatus();
    }

    /**
     * CanInvoice
     */
    private function _canInvoice()
    {
        return $this->getOrder()->canInvoice();
    }

    /**
     * Invoice Order
     */
    private function _invoiceOrder()
    {
        $order = $this->getOrder();

        $order->setIsInProcess(true);
        $order->addStatusToHistory(
            \Sapient\Worldpay\Model\Payment\Update\Base::STATUS_PROCESSING,
            'Payment successfully received from Worldpay'
        );
        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true);

        $invoice = $this->_invoiceService->prepareInvoice($order);
        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
        $invoice->register();
        $invoice->save();

        $transactionSave = $this->_transaction->addObject(
            $invoice
        )->addObject(
            $invoice->getOrder()
        );
        $transactionSave->save();
    }

    /**
     * Has worldpay payment
     */
    public function hasWorldPayPayment()
    {
        if (!$this->getOrder()->getPayment()) {
            return false;
        }

        return $this->getWorldPayPayment()->getId() !== null;
    }

    /**
     * GetWorldPayPayment
     *
     * @return Sapient/Worldpay/Model/Worldpayment
     */
    public function getWorldPayPayment()
    {
        if ($this->getOrder()->isObjectNew()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(sprintf('Order with id "%s" does not exist.', $this->getOrder()->getId()))
            );
        }

        return $this->worldpaypaymentmodel->loadByPaymentId($this->getOrder()->getIncrementId());
    }

    /**
     * Set order status as pending
     */
    public function pendingPayment()
    {
        $mageOrder = $this->getOrder();
        $mageOrder->setState(
            \Magento\Sales\Model\Order::STATE_NEW,
            true,
            'Customer authentication successful. Pending funds transfer confirmation from the gateway.'
        );
        $mageOrder->setStatus('pending');
        $mageOrder->save();
    }

    /**
     * Mark Credit Memo as refunded
     *
     * @param string $reference
     * @param string $comment
     */
    public function refund($reference, $comment)
    {

        if (!$reference) {
            return;
        }

        $creditmemo = $this->ordercreditmemo;
        $creditmemo->load($reference, 'increment_id');

        if ($creditmemo->getOrder()->getId() != $this->getOrder()->getId()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('WorldPay refund ERROR: Credit Memo does not match Order. Reference'. $reference)
            );
        }

        if ($creditmemo->getState() == \Magento\Sales\Model\Order\Creditmemo::STATE_OPEN) {
            $this->_markRefunded($creditmemo, $comment);
        }
    }

    /**
     * Handle the refund request, usually issued from WorldPay panel and triggered by notification.
     * Create Credit Memo, register and mark it as refunded.
     * Deals with the full order or remainder refund only.
     *
     * @param string|int $amount
     * @param string $comment
     */
    public function refundFull($amount, $comment)
    {
        if (!$amount) {
            return;
        }
        if ($this->_orderUnrefundedEquals($amount) && $this->_canRefundFull()) {
            $this->_createCreditMemos($comment);
        }
    }

    /**
     * Order unrefund equals
     *
     * @param string|int $amount
     */
    private function _orderUnrefundedEquals($amount)
    {
        $amount /= 100;
        return abs(
            $this->getOrder()->getGrandTotal()
            - $this->getOrder()->getTotalRefunded()
            - $amount
        ) < 0.001;
    }

    /**
     * CanRefundFull
     *
     * @return boolean
     */
    private function _canRefundFull()
    {
        $invoiceCollection = $this->getOrder()->getInvoiceCollection();

        if ($invoiceCollection->count() === 0) {
            return false;
        }

        foreach ($invoiceCollection as $invoice) {
            if (!$invoice->canRefund()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create credit memos
     *
     * @param string $comment
     */
    private function _createCreditMemos($comment)
    {
        $order = $this->getOrder();

        $invoices = $order->getInvoiceCollection();
        foreach ($invoices as $invoice) {
            $invoiceincrementid = $invoice->getIncrementId();
            $invoiceobj =  $this->Invoice->loadByIncrementId($invoiceincrementid);
            $creditmemo = $this->creditmemoFactory->createByOrder($order);
            $creditmemo->setInvoice($invoiceobj);
            $this->CreditmemoService->refund($creditmemo);
            $this->_markRefunded($creditmemo, $comment);
        }
    }

    /**
     * Mark Refunded
     *
     * @param string $creditmemo
     * @param string $comment
     */
    private function _markRefunded($creditmemo, $comment)
    {
        $creditmemo->setState(\Magento\Sales\Model\Order\Creditmemo::STATE_REFUNDED);
        $order = $creditmemo->getOrder();
        $order->addStatusHistoryComment($comment);

         $transactionSave = $this->_transaction->addObject(
             $creditmemo
         )->addObject(
             $creditmemo->getOrder()
         );
        $transactionSave->save();
    }

    /**
     * Cancel Refund
     *
     * @param string $reference
     * @param string $comment
     */
    public function cancelRefund($reference, $comment)
    {
        if (!$reference) {
            return;
        }

        $creditmemo = $this->ordercreditmemo;
        $creditmemo->load($reference, 'increment_id');

        if ($creditmemo->getOrder()->getId() != $this->getOrder()->getId()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('WorldPay refund ERROR: Credit Memo does not match Order. Reference'. $reference)
            );
        }

        if ($creditmemo->getState() == \Magento\Sales\Model\Order\Creditmemo::STATE_OPEN) {
            $this->_cancelCreditmemo($creditmemo, $comment);
        }
    }

    /**
     * Cancel Credit memo
     *
     * @param string $creditmemo
     * @param string $comment
     */
    private function _cancelCreditmemo($creditmemo, $comment = null)
    {
        if ($creditmemo && $creditmemo->canCancel()) {
             $creditmemo->setState(\Magento\Sales\Model\Order\Creditmemo::STATE_CANCELED);
            $order = $creditmemo->getOrder();
            if ($comment) {
                $order->addStatusHistoryComment($comment);
            }
            $this->_deductOrderTotals($order, $creditmemo);

            $transactionSave = $this->_transaction->addObject(
                $creditmemo
            )->addObject(
                $creditmemo->getOrder()
            );
            $transactionSave->save();
        }
    }

    /**
     * Cancel Magento Credit memo
     *
     * @param int $id
     * @return boolean
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function cancelMagentoCreditMemo($id)
    {
        try {
            $creditmemo = $this->creditmemoRepository->get($id);
            $creditmemo->setState(\Magento\Sales\Model\Order\Creditmemo::STATE_CANCELED);
            $creditmemo->setStatus(\Magento\Sales\Model\Order\Creditmemo::STATE_CANCELED);
            foreach ($creditmemo->getAllItems() as $item) {
                $item->cancel();
            }
            $this->creditmemoRepository->save($creditmemo);
        } catch (Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
        return true;
    }

    /**
     * Deduct order totals
     *
     * @param object $order
     * @param object $creditmemo
     */
    private function _deductOrderTotals($order, $creditmemo)
    {

        if (!$order->dataHasChangedFor('total_refunded')) {
            $order->setTotalRefunded($order->getTotalRefunded() - $creditmemo->getGrandTotal());
            foreach ($creditmemo->getAllItems() as $item) {
                $orderItem = $item->getOrderItem();
                if ($orderItem->getBaseAmountRefunded() > 0) {
                    $orderItem->setAmountRefunded($orderItem->getAmountRefunded() - $item->getRowTotal());
                    $orderItem->setBaseAmountRefunded($orderItem->getBaseAmountRefunded() - $item->getBaseRowTotal());
                    $orderItem->save();
                }
            }
        }
        if (!$order->dataHasChangedFor('base_total_refunded')) {
            $order->setBaseTotalRefunded($order->getBaseTotalRefunded() - $creditmemo->getBaseGrandTotal());
        }
    }
}
