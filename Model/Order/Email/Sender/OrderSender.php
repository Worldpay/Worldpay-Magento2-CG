<?php
/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Model\Order\Email\Sender;

use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Container\OrderIdentity;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Sales\Model\Order\Email\SenderBuilderFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\DataObject;
use Sapient\Worldpay\Model\AuthorisedOrderEmailFactory;
use Sapient\Worldpay\Model\Worldpayment;

class OrderSender extends \Magento\Sales\Model\Order\Email\Sender\OrderSender
{
    public const XML_PATH_EMAIL_GUEST_TEMPLATE = 'wp_sales_email_order_guest_template';
    public const XML_PATH_EMAIL_TEMPLATE = 'wp_sales_email_order_template';
    public const XML_PATH_AUTHORISED_EMAIL_GUEST_TEMPLATE = 'wp_auth_sales_email_order_guest_template';
    public const XML_PATH_AUTHORISED_EMAIL_TEMPLATE = 'wp_auth_sales_email_order_template';

    /**
     * @var  Worldpayment
     */
    public $worldpaypaymentmodel;

     /**
      * @var  \Magento\Framework\App\Config\ScopeConfigInterface
      */
    public $scopeConfig;

    private AuthorisedOrderEmailFactory $authorisedOrderEmailFactory;

    public function __construct(
        Template $templateContainer,
        OrderIdentity $identityContainer,
        SenderBuilderFactory $senderBuilderFactory,
        \Psr\Log\LoggerInterface $logger,
        Renderer $addressRenderer,
        PaymentHelper $paymentHelper,
        Worldpayment $worldpaypaymentmodel,
        OrderResource $orderResource,
        \Magento\Framework\App\Config\ScopeConfigInterface $globalConfig,
        ManagerInterface $eventManager,
        AuthorisedOrderEmailFactory $authorisedOrderEmailFactory,
    ) {
        parent::__construct(
            $templateContainer,
            $identityContainer,
            $senderBuilderFactory,
            $logger,
            $addressRenderer,
            $paymentHelper,
            $orderResource,
            $globalConfig,
            $eventManager
        );
        $this->worldpaypaymentmodel = $worldpaypaymentmodel;
        $this->scopeConfig = $globalConfig;
        $this->authorisedOrderEmailFactory = $authorisedOrderEmailFactory;
    }

    /**
     * Send
     *
     * @param Order $order
     * @param int|bool $forceSyncMode
     */

    public function send(Order $order, $forceSyncMode = false)
    {
        $worldPayPayment = $this->worldpaypaymentmodel->loadByPaymentId($order->getIncrementId());
        $isRecurringOrder = $worldPayPayment->getData('is_recurring_order');
        if ($isRecurringOrder && !$this->scopeConfig->getValue('worldpay/subscriptions/enable_email')) {
               $order->setSendEmail(null);
               $order->setEmailSent(null);
               $this->orderResource->saveAttribute($order, 'email_sent');
               $this->orderResource->saveAttribute($order, 'send_email');
               return false;
        } else {
            return parent::send($order, $forceSyncMode);
        }
    }

     /**
      * Prepare email template with variables
      *
      * @param Order $order
      * @return void
      */
    protected function prepareTemplate(Order $order)
    {
        $transport = [
            'order' => $order,
            'order_id' => $order->getId(),
            'billing' => $order->getBillingAddress(),
            'payment_html' => $this->getPaymentHtml($order),
            'store' => $order->getStore(),
            'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
            'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
            'created_at_formatted' => $order->getCreatedAtFormatted(2),
            'order_data' => [
                'customer_name' => $order->getCustomerName(),
                'is_not_virtual' => $order->getIsNotVirtual(),
                'email_customer_note' => $order->getEmailCustomerNote(),
                'frontend_status_label' => $order->getFrontendStatusLabel()
            ]
        ];
        $transportObject = new DataObject($transport);

        /**
         * Event argument `transport` is @deprecated. Use `transportObject` instead.
         */
        $this->eventManager->dispatch(
            'email_order_set_template_vars_before',
            ['sender' => $this, 'transport' => $transportObject, 'transportObject' => $transportObject]
        );

        $this->templateContainer->setTemplateVars($transportObject->getData());

        $worldPayPayment = $this->worldpaypaymentmodel->loadByPaymentId($order->getIncrementId());
        $isRedirectOrder = $worldPayPayment->getData('payment_model');
        $wpPaymentStatus = $worldPayPayment->getData('payment_status');

        if ($isRedirectOrder &&
            $wpPaymentStatus == \Sapient\Worldpay\Model\Payment\StateInterface::STATUS_SENT_FOR_AUTHORISATION) {

            $this->templateContainer->setTemplateOptions($this->getTemplateOptions());

            if ($order->getCustomerIsGuest()) {
                $templateId = self::XML_PATH_EMAIL_GUEST_TEMPLATE;
                $customerName = $order->getBillingAddress()->getName();
            } else {
                $templateId = self::XML_PATH_EMAIL_TEMPLATE;
                $customerName = $order->getCustomerName();
            }

            $this->identityContainer->setCustomerName($customerName);
            $this->identityContainer->setCustomerEmail($order->getCustomerEmail());
            $this->templateContainer->setTemplateId($templateId);

        } else {
            parent::prepareTemplate($order);
        }
    }

    public function prepareTemplateForAuthorisedOrder($order, $successFlag): void
    {
        $emailSub = __("Your payment has been confirmed with the bank and order has been processed successfully");
        $authSuccessMsg = __("Once your package ships we will send you a tracking number.");
        if (!$successFlag) {
            $emailSub = __("Your payment has been declined by the bank and order has been cancelled");
            $authSuccessMsg = $emailSub;
        }
        $transport = [
            'order' => $order,
            'order_id' => $order->getId(),
            'billing' => $order->getBillingAddress(),
            'payment_html' => $this->getPaymentHtml($order),
            'store' => $order->getStore(),
            'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
            'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
            'created_at_formatted' => $order->getCreatedAtFormatted(2),
            'email_sub'=> $emailSub,
            'auth_success_message'=> $authSuccessMsg,
            'order_data' => [
                'customer_name' => $order->getCustomerName(),
                'is_not_virtual' => $order->getIsNotVirtual(),
                'email_customer_note' => $order->getEmailCustomerNote(),
                'frontend_status_label' => $order->getFrontendStatusLabel()
            ]
        ];
        $transportObject = new DataObject($transport);

        /**
         * Event argument `transport` is @deprecated. Use `transportObject` instead.
         */
        $this->eventManager->dispatch(
            'email_order_set_template_vars_before',
            ['sender' => $this, 'transport' => $transportObject, 'transportObject' => $transportObject]
        );
        $this->templateContainer->setTemplateVars($transportObject->getData());
        $this->templateContainer->setTemplateOptions($this->getTemplateOptions());
        if ($order->getCustomerIsGuest()) {
            $templateId = self::XML_PATH_AUTHORISED_EMAIL_GUEST_TEMPLATE;
            $customerName = $order->getBillingAddress()->getName();
        } else {
            $templateId = self::XML_PATH_AUTHORISED_EMAIL_TEMPLATE;
            $customerName = $order->getCustomerName();
        }
        $this->identityContainer->setCustomerName($customerName);
        $this->identityContainer->setCustomerEmail($order->getCustomerEmail());
        $this->templateContainer->setTemplateId($templateId);
    }

    public function authorisedEmailSend(Order $order, bool $successFlag): bool
    {
        $this->identityContainer->setStore($order->getStore());
        if (!$this->identityContainer->isEnabled()) {
            return false;
        }
        $this->prepareTemplateForAuthorisedOrder($order, $successFlag);
        /** @var SenderBuilder $sender */
        $sender = $this->getSender();
        try {
            if (!$this->globalConfig->getValue('sales_email/general/async_sending')) {
                $sender->send();
            } else {
                $authorisedOrderEmailModel = $this->authorisedOrderEmailFactory->create();
                $authorisedOrderEmailModel->setOrderIncrementId($order->getIncrementId());
                $authorisedOrderEmailModel->setSuccessFlag($successFlag);
                $authorisedOrderEmailModel->setAttemptCount(0);
                $authorisedOrderEmailModel->save();
            }
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Place Order confirm email for redirect mode full page
     *
     * @param array $order
     * @return
     */
    public function fullPageRedirectOrderEmail(order $order)
    {
        $this->identityContainer->setStore($order->getStore());
        if (!$this->identityContainer->isEnabled()) {
            return false;
        }
        $this->prepareTemplate($order);
        /** @var SenderBuilder $sender */
        $sender = $this->getSender();
        try {
            $sender->send();
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
        return true;
    }
}
