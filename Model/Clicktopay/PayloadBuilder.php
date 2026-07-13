<?php
namespace Sapient\Worldpay\Model\Clicktopay;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Sapient\Worldpay\Helper\Data as WorldpayHelper;


class PayloadBuilder
{
    private WorldpayHelper $worldpayHelper;

    public function __construct(
        WorldpayHelper $worldpayHelper
    ) {
        $this->worldpayHelper = $worldpayHelper;
    }

    public function build(Quote $quote): array
    {
        return [
            'clientVersion' => '0.26',
            'targetOrigins' => [rtrim($this->worldpayHelper->getBaseUrl(), '/')],
            'allowedCardNetworks' => ['VISA', 'MASTERCARD'],
            'allowedPaymentTypes' => ['CLICKTOPAY'],
            'country' => $this->getCountryFromQuote($quote),
            'locale' => $this->worldpayHelper->getLocaleFromQuote($quote),
            'data' => [
                'orderInformation' => $this->buildOrderInformation($quote)
            ],
            'captureMandate' => [
                'billingType' => 'FULL',
                'requestEmail' => true,
                'requestPhone' => false,
                'requestShipping' => false,
                'shipToCountries' => [$this->getCountryFromQuote($quote)],
                'showAcceptedNetworkIcons' => false
            ]
        ];
    }

    protected function getCountryFromQuote(Quote $quote): string
    {
        $billingAddress = $quote->getBillingAddress();
        return $billingAddress ? $billingAddress->getCountryId() : 'US';
    }

    protected function buildOrderInformation(Quote $quote): array
    {
        $orderInfo = [
            'amountDetails' => [
                'totalAmount' => number_format($quote->getGrandTotal(), 2, '.', ''),
                'currency' => $quote->getQuoteCurrencyCode()
            ]
        ];

        return $orderInfo;
    }
}

