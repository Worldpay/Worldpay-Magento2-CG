<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Helper;

use Magento\Store\Model\Store;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * MinSaleQty value manipulation helper
 */
class Merchantprofile
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
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Math\Random $mathRandom
     */
    
    /**
     * @var SerializerInterface
     */
    private $serializer;
    
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Math\Random $mathRandom,
        SerializerInterface $serializer
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->mathRandom = $mathRandom;
        $this->serializer = $serializer;
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
            foreach ($value as $payment_type => $merchant_detail) {
                if (!array_key_exists($payment_type, $data)) {
                    $data[$payment_type] =$merchant_detail;
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
                || !array_key_exists('worldpay_payment_method', $row)
                || !array_key_exists('merchant_code', $row)
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
        foreach ($value as $payment_type => $merchant_detail) {
            $resultId = $this->mathRandom->getUniqueHash('_');
            $result[$resultId] = ['worldpay_payment_method' => $payment_type,
                                  'merchant_code' => $merchant_detail['merchant_code'],
                                  'merchant_username' => $merchant_detail['merchant_username'],
                                  'merchant_password' => $merchant_detail['merchant_password'],
                                  'merchant_installation_id' => isset(
                                      $merchant_detail['merchant_installation_id']
                                  ) ? $merchant_detail['merchant_installation_id'] : '' ,
                                 ];
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
                || !array_key_exists('worldpay_payment_method', $row)
                || !array_key_exists('merchant_code', $row)
                || !array_key_exists('merchant_username', $row)
                || !array_key_exists('merchant_password', $row)
            ) {
                continue;
            }
            if (!empty($row['worldpay_payment_method'])
               && !empty($row['merchant_code'])
               && !empty($row['merchant_username'])
               || !empty($row['merchant_password'])
            ) {
                $payment_type = $row['worldpay_payment_method'];
                $rs['merchant_code'] = $row['merchant_code'];
                $rs['merchant_username'] = $row['merchant_username'];
                $rs['merchant_password'] = $row['merchant_password'];
                $rs['merchant_installation_id'] = $row['merchant_installation_id'];
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
     * Retrieve merchant detail value from config
     *
     * @param int $customerGroupId
     * @param null|string|bool|int|Store $store
     * @return float|null
     */
    public function getConfigValue($paymenttype, $store = null)
    {
        $value = $this->scopeConfig->getValue(
            'worldpay/merchant_config/merchant_profile',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        $value = $this->unserializeValue($value);
        if ($this->isEncodedArrayFieldValue($value)) {
            $value = $this->decodeArrayFieldValue($value);
        }

        if (!empty($value[$paymenttype])) {
            return $value[$paymenttype];
        }
    }

    /**
     *  Retrieve all merchant details which is configured in Merchant override setting
     */
    public function getAdditionalMerchantProfiles($store = null)
    {
        $value = $this->scopeConfig->getValue(
            'worldpay/merchant_config/merchant_profile',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        $value = $this->unserializeValue($value);
        if ($this->isEncodedArrayFieldValue($value)) {
            $value = $this->decodeArrayFieldValue($value);
        }

        return $value;
    }
}
