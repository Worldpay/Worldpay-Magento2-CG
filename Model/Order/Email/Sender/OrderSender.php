<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of OrderSender
 *
 * @author regchowt
 */

namespace Sapient\Worldpay\Model\Order\Email\Sender;

use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Container\OrderIdentity;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Framework\Event\ManagerInterface;

class OrderSender extends \Magento\Sales\Model\Order\Email\Sender\OrderSender
{

    public function __construct(
        Template $templateContainer,
        OrderIdentity $identityContainer,
        \Magento\Sales\Model\Order\Email\SenderBuilderFactory $senderBuilderFactory,
        \Psr\Log\LoggerInterface $logger,
        Renderer $addressRenderer,
        PaymentHelper $paymentHelper,
        \Sapient\Worldpay\Model\Worldpayment $worldpaypaymentmodel,
        OrderResource $orderResource,
        \Magento\Framework\App\Config\ScopeConfigInterface $globalConfig,
        ManagerInterface $eventManager
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
    }

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
            return parent::send($order, $forceSyncMode = false);
             
        }
        return false;
    }
}
