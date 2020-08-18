<?php

namespace Sapient\Worldpay\Model\InstantPurchase\CreditCard;

use Magento\InstantPurchase\PaymentMethodIntegration\PaymentTokenFormatterInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;

/**
 * Braintree stored credit card formatter.
 */
class TokenFormatter implements PaymentTokenFormatterInterface
{
    /**
     * Most used credit card types
     * @var array
     */
    public static $baseCardTypes = [
        'AMEX-SSL' => 'American Express',
        'VISA-SSL' => 'Visa',
        'ECMC-SSL' => 'MasterCard',
        'DISCOVER-SSL' => 'Discover',
        'JCB-SSL' => 'Japanese Credit Bank',
        'CARTEBLEUE-SSL' => 'Carte Bleue',
        'MAESTRO-SSL' => 'Maestro',
        'DANKORT-SSL' => 'Dankort',
        'CB-SSL' => 'Carte Bancaire',
        'DINERS-SSL' => 'Diners',
    ];

    public function __construct(
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
    ) {
        $this->wplogger = $wplogger;
    }

    /**
     * @inheritdoc
     */
    public function formatPaymentToken(PaymentTokenInterface $paymentToken): string
    {
        $details = json_decode($paymentToken->getTokenDetails() ?: '{}', true);
        if (!isset($details['type'], $details['maskedCC'], $details['expirationDate'])) {
            throw new \InvalidArgumentException('Invalid Worldpay credit card token details.');
        }

        if (isset(self::$baseCardTypes[$details['type']])) {
            $ccType = self::$baseCardTypes[$details['type']];
        } else {
            $ccType = $details['type'];
        }

        $formatted = sprintf(
            '%s: %s, %s: %s (%s: %s)',
            __('Credit Card'),
            $ccType,
            __('ending'),
            $details['maskedCC'],
            __('expires'),
            $details['expirationDate']
        );
        return $formatted;
    }
}
