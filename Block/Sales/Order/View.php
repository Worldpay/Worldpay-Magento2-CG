<?php

namespace Sapient\Worldpay\Block\Sales\Order;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;
use Sapient\Worldpay\Model\Worldpayment;
use Sapient\Worldpay\Model\WorldpaymentFactory;
use DateTime;
use Exception;

class View extends Template
{
    protected Registry $registry;
    protected WorldpaymentFactory $worldpaymentFactory;

    public function __construct(
        Context $context,
        Registry $registry,
        WorldpaymentFactory $worldpaymentFactory,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->worldpaymentFactory = $worldpaymentFactory;
        parent::__construct($context, $data);
    }

    public function getOrder(): ?Order
    {
        return $this->registry->registry('current_order');
    }

    public function getWorldpayPaymentDetails(): ?Worldpayment
    {
        $order = $this->getOrder();
        if (!$order || !$order->getId()) {
            return null;
        }

        $orderIncrementId = $order->getIncrementId();
        $worldpayPayment = $this->worldpaymentFactory->create();
        return $worldpayPayment->loadByPaymentId($orderIncrementId);
    }

    public function isWorldpayPayment(): bool
    {
        $order = $this->getOrder();
        if (!$order) {
            return false;
        }

        $paymentMethod = $order->getPayment()->getMethod();
        $worldpayMethods = [
            'worldpay_cc',
            'worldpay_apm',
            'worldpay_moto',
            'worldpay_cc_vault',
            'worldpay_wallets',
            'worldpay_paybylink'
        ];

        return in_array($paymentMethod, $worldpayMethods);
    }

    public function getWorldpayPaymentInfo(): array
    {
        $worldpayDetails = $this->getWorldpayPaymentDetails();
        $paymentInfo = [];

        if (!$worldpayDetails || !$worldpayDetails->getDccOfferAmount()) {
            return $paymentInfo;
        }


        $paymentInfo['dcc_offer_amount'] = $this->formatAmount($worldpayDetails->getDccOfferAmount());
        $paymentInfo['dcc_offer_currency_code'] = $worldpayDetails->getDccOfferCurrencyCode() ?: 'N/A';
        $paymentInfo['dcc_offer_rate'] = $worldpayDetails->getDccOfferRate();
        $paymentInfo['dcc_offer_rate_provider'] = $worldpayDetails->getDccOfferRateProvider() ?: 'N/A';
        $paymentInfo['dcc_offer_markup_rate'] = $worldpayDetails->getDccOfferMarkupRate() ?
            number_format($worldpayDetails->getDccOfferMarkupRate(), 2) : 'N/A';
        $paymentInfo['dcc_offer_acceptance_date'] = $this->formatAcceptanceDate($worldpayDetails->getDccOfferAcceptanceDate());

        return $paymentInfo;
    }

    private function formatAmount(mixed $amount): string
    {
        return $amount ? number_format($amount / 100, 2) : 'N/A';
    }

    private function formatAcceptanceDate(?string $dateString): string
    {
        if (!$dateString || strlen($dateString) !== 8) {
            return 'N/A';
        }

        try {
            $dateObj = DateTime::createFromFormat('Ymd', $dateString);
            return $dateObj ? $dateObj->format('M d, Y') : 'N/A';
        } catch (Exception) {
            return 'N/A';
        }
    }
}
