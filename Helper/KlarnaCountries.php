<?php
/**
 * @copyright 2020 Sapient
 */
namespace Sapient\Worldpay\Helper;

use Magento\Store\Model\Store;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * MinSaleQty value manipulation helper
 */
class KlarnaCountries
{
    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Math\Random
     */
    protected $mathRandom;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var \Sapient\Worldpay\Model\Config\Source\KlarnaCountries
     */
    protected $klarnaCountries;
    
    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Math\Random $mathRandom
     * @param SerializerInterface $serializer
     * @param \Sapient\Worldpay\Model\Config\Source\KlarnaCountries $klarnaCountries
     */

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Math\Random $mathRandom,
        SerializerInterface $serializer,
        \Sapient\Worldpay\Model\Config\Source\KlarnaCountries $klarnaCountries
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->mathRandom = $mathRandom;
        $this->serializer = $serializer;
        $this->klarnaCountries = $klarnaCountries;
    }

    /**
     * Generate a storable representation of a value
     *
     * @param int|float|string|array $value
     * @return string
     */
    protected function serializeValue($value)
    {

        if (is_array($value)) {
            $data = [];
            foreach ($value as $payment_type => $subscription_detail) {
                if (!array_key_exists($payment_type, $data)) {
                    $data[$payment_type] = $subscription_detail;
                }
            }

            return $this->serializer->serialize($data);
        } else {
            return '';
        }
    }

    /**
     * Create a value from a storable representation
     *
     * @param int|float|string $value
     * @return array
     */
    protected function unserializeValue($value)
    {
        if (is_string($value) && !empty($value) && $value != 'a:0:{}') {
            return $this->serializer->unserialize($value);
        } else {
            return [];
        }
    }

    /**
     * Check whether value is in form retrieved by _encodeArrayFieldValue()
     *
     * @param string|array $value
     * @return bool
     */
    protected function isEncodedArrayFieldValue($value)
    {
        if (!is_array($value)) {
            return false;
        }
        unset($value['__empty']);
        foreach ($value as $row) {
            if (!is_array($row)
                || !array_key_exists('worldpay_klarna_subscription', $row)
                || !array_key_exists('subscription_days', $row)
            ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Encode value to be used in \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
     *
     * @param array $value
     * @return array
     */
    protected function encodeArrayFieldValue(array $value)
    {
        $result = [];
        unset($value['__empty']);
        foreach ($value as $payment_type => $subscription_detail) {
            $resultId = $this->mathRandom->getUniqueHash('_');
            $existCountry = array_search($payment_type, array_column($this->klarnaCountries->toOptionArray(), 'value'));
            if ($existCountry) {
                $result[$resultId] = ['worldpay_klarna_subscription' => $payment_type,
                                  'subscription_days' => $subscription_detail['subscription_days'],
                                 ];
            }
        }
        return $result;
    }

    /**
     * Decode value from used in \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
     *
     * @param array $value
     * @return array
     */
    protected function decodeArrayFieldValue(array $value)
    {
        $result = [];
        unset($value['__empty']);
        foreach ($value as $row) {
            if (!is_array($row)
                || !array_key_exists('worldpay_klarna_subscription', $row)
                || !array_key_exists('subscription_days', $row)
            ) {
                continue;
            }
            if (!empty($row['worldpay_klarna_subscription'])
               && !empty($row['subscription_days'])
            ) {
                $payment_type = $row['worldpay_klarna_subscription'];
                $rs['subscription_days'] = $row['subscription_days'];
                $result[$payment_type] = $rs;
            }
        }
        return $result;
    }

    /**
     * Make value readable by \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
     *
     * @param string|array $value
     * @return array
     */
    public function makeArrayFieldValue($value)
    {
        $value = $this->unserializeValue($value);
        if (!$this->isEncodedArrayFieldValue($value)) {
            $value = $this->encodeArrayFieldValue($value);
        }
        return $value;
    }

    /**
     * Make value ready for store
     *
     * @param string|array $value
     * @return string
     */
    public function makeStorableArrayFieldValue($value)
    {
        if ($this->isEncodedArrayFieldValue($value)) {
            $value = $this->decodeArrayFieldValue($value);
        }
        $value = $this->serializeValue($value);

        return $value;
    }

    /**
     * Retrieve klarna subscription value from config
     *
     * @param null|string|bool|int $countrycode
     * @param null|string|bool|int|Store $store
     * @return float|null
     */
    public function getConfigValue($countrycode, $store = null)
    {
        $value = $this->scopeConfig->getValue(
            'worldpay/klarna_config/paylater_config/paylater_days_config/subscription_days',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        $value = $this->unserializeValue($value);
        if ($this->isEncodedArrayFieldValue($value)) {
            $value = $this->decodeArrayFieldValue($value);
        }

        if (!empty($value[$countrycode])) {
            return $value[$countrycode];
        }
    }
}
