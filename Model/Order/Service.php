<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Order;

class Service
{

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutsession;
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $mageorder;
    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $emailsender;
    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $_invoiceService;
    /**
     * @var \Magento\Framework\DB\Transaction
     */
    protected $_transaction;
    /**
     * @var \Sapient\Worldpay\Model\Worldpayment
     */
    protected $worldpaypaymentmodel;
    /**
     * @var \Magento\Sales\Model\Order\CreditmemoFactory
     */
    protected $creditmemoFactory;
    /**
     * @var \Magento\Sales\Model\Order\Invoice
     */
    protected $Invoice;
    /**
     * @var \Magento\Sales\Model\Service\CreditmemoService
     */
    protected $CreditmemoService;
    /**
     * @var \Magento\Sales\Model\Order\Creditmemo
     */
    protected $ordercreditmemo;
    /**
     * @var \Magento\Sales\Api\CreditmemoRepositoryInterface
     */
    protected $creditmemoRepository;
    /**
     * @var \Sapient\Worldpay\Model\ResourceModel\Multishipping\Order\Collection
     */
    protected $multishippingOrderCollection;
    /**
     * Constructor
     *
     * @param \Magento\Sales\Model\Order $mageOrder
     * @param \Magento\Checkout\Model\Session $checkoutsession
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $emailsender
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Framework\DB\Transaction $transaction
     * @param \Sapient\Worldpay\Model\Worldpayment $worldpaypaymentmodel
     * @param \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory
     * @param \Magento\Sales\Model\Order\Invoice $Invoice
     * @param \Magento\Sales\Model\Service\CreditmemoService $CreditmemoService
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @param \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository
     * @param \Sapient\Worldpay\Model\ResourceModel\Multishipping\Order\Collection $multishippingOrderCollection
     */
    public function __construct(
        \Magento\Sales\Model\Order $mageOrder,
        \Magento\Checkout\Model\Session $checkoutsession,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $emailsender,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction,
        \Sapient\Worldpay\Model\Worldpayment $worldpaypaymentmodel,
        \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory,
        \Magento\Sales\Model\Order\Invoice $Invoice,
        \Magento\Sales\Model\Service\CreditmemoService $CreditmemoService,
        \Magento\Sales\Model\Order\Creditmemo $creditmemo,
        \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository,
        \Sapient\Worldpay\Model\ResourceModel\Multishipping\Order\Collection $multishippingOrderCollection
    ) {

        $this->checkoutsession = $checkoutsession;
        $this->mageorder = $mageOrder;
        $this->emailsender = $emailsender;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->worldpaypaymentmodel = $worldpaypaymentmodel;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->Invoice = $Invoice;
        $this->CreditmemoService = $CreditmemoService;
        $this->ordercreditmemo = $creditmemo;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->multishippingOrderCollection = $multishippingOrderCollection;
    }

    /**
     * Get Order By Id
     *
     * @param int $orderId
     * @return \Sapient\Worldpay\Model\Order
     */
    public function getById($orderId)
    {

         return new \Sapient\Worldpay\Model\Order(
             [
                'order' => $this->mageorder->load($orderId)
             ],
             $this->_invoiceService,
             $this->_transaction,
             $this->worldpaypaymentmodel,
             $this->creditmemoFactory,
             $this->Invoice,
             $this->CreditmemoService,
             $this->ordercreditmemo,
             $this->creditmemoRepository
         );
    }

   /**
    * Get Order By incremented Id
    *
    * @param string $incrementId
    * @return \Sapient\Worldpay\Model\Order
    */
    public function getByIncrementId($incrementId)
    {

        return new \Sapient\Worldpay\Model\Order(
            [
                'order' => $this->mageorder->loadByIncrementId($incrementId)
            ],
            $this->_invoiceService,
            $this->_transaction,
            $this->worldpaypaymentmodel,
            $this->creditmemoFactory,
            $this->Invoice,
            $this->CreditmemoService,
            $this->ordercreditmemo,
            $this->creditmemoRepository
        );
    }

    /**
     * If order is success send email and mark order as processing
     */
    public function redirectOrderSuccess()
    {
        $order = $this->getAuthorisedOrder();
        $magentoorder = $order->getOrder();
        $this->emailsender->authorisedEmailSend($magentoorder, true);
        // Start multishipping code
        $worldpaypayment = $order->getWorldPayPayment();
        if ($worldpaypayment->getIsMultishippingOrder()) {
            $in_id = $order->getIncrementId();
            $qte_id = $order->getQuoteId();
            $multishippingOrders = $this->multishippingOrderCollection->getCollectionByOrderAndQuoteId($in_id, $qte_id);
            if (!empty($multishippingOrders)) {
                foreach ($multishippingOrders as $multishippingOrder) {
                    $order_id = $multishippingOrder->getOrderId();
                    $order = $this->getByIncrementId($order_id);
                    $magentoorder = $order->getOrder();
                    $this->emailsender->authorisedEmailSend($magentoorder, true);
                }
            }
        }
        // End multishipping code
    }

    /**
     * Get Authorised Order
     *
     * @return Increament Id
     */
    public function getAuthorisedOrder()
    {
        if ($this->checkoutsession->getauthenticatedOrderId()) {
            return $this->getByIncrementId($this->checkoutsession->getauthenticatedOrderId());
        }
        return false;
    }

    /**
     * Delete currently authorised order from session
     */
    public function removeAuthorisedOrder()
    {
        $this->checkoutsession->unsauthenticatedOrderId();
    }
}
