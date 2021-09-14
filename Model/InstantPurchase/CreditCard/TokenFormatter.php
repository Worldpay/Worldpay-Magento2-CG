<?php

namespace Sapient\Worldpay\Model\InstantPurchase\CreditCard;

use Magento\InstantPurchase\PaymentMethodIntegration\PaymentTokenFormatterInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Sapient\Worldpay\Model\ResourceModel\SavedToken;

/**
 * Braintree stored credit card formatter.
 */
class TokenFormatter implements PaymentTokenFormatterInterface
{
     /**
      * @var \Sapient\Worldpay\Model\WorldpaymentFactory
      */
    protected $_worldpaymentFactory;
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

    /**
     * Constructor
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Model\SavedTokenFactory $savedWPFactory
     * @param \Sapient\Worldpay\Helper\Data $wpdata
     * @param SavedToken $savedtoken
     */
    public function __construct(
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\SavedTokenFactory $savedWPFactory,
        \Sapient\Worldpay\Helper\Data $wpdata,
        SavedToken $savedtoken
    ) {
        $this->wplogger = $wplogger;
        $this->savecard = $savedWPFactory;
        $this->wpdata = $wpdata;
        $this->savedtoken = $savedtoken;
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
        if ($this->wpdata->isDynamic3DS2Enabled()) {
            $tokenCode = $paymentToken->getGatewayToken();
            $savedCardDataId = $this->savedtoken->loadByTokenCode($tokenCode);
            $model = $this->savecard->create();
            $model->load($savedCardDataId);
            $formatted = sprintf(
                '%s: %s, %s: %s ,%s: %s, %s: %s',
                __('Credit Card'),
                $ccType,
                __('ending'),
                $details['maskedCC'],
                __('expires'),
                $details['expirationDate'],
                __('bin'),
                $model->getData('bin_number')
            );
        } else {
            $formatted = sprintf(
                '%s: %s, %s: %s ,%s: %s',
                __('Credit Card'),
                $ccType,
                __('ending'),
                $details['maskedCC'],
                __('expires'),
                $details['expirationDate']
            );
        }
        return $formatted;
    }
}
