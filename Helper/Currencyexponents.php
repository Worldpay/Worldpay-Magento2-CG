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
class Currencyexponents
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
    
    /**
     * Currencyexponents constructor
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Math\Random $mathRandom
     * @param SerializerInterface $serializer
     */
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
                || !array_key_exists('currency_code', $row)
                || !array_key_exists('currency', $row)
                || !array_key_exists('exponent', $row)
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
        foreach ($value as $exceptionkey => $exceptiondetail) {
            $resultId = $this->mathRandom->getUniqueHash('_');
            $result[$resultId] = [
                                  'currency_code' => $exceptionkey,
                                  'currency' => $exceptiondetail['currency'],
                                  'exponent' => $exceptiondetail['exponent'],
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
                || !array_key_exists('currency_code', $row)
                || !array_key_exists('currency', $row)
                || !array_key_exists('exponent', $row)
            ) {
                continue;
            }
            if (!empty($row['currency_code'])
               && !empty($row['currency'])
               || !empty($row['exponent'])
            ) {
                $payment_type = $row['currency_code'];
                $rs['currency'] = $row['currency'];
                $rs['exponent'] = $row['exponent'];
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
     * @param string $currencycode
     * @param null|string|bool|int|Store $store
     * @return float|null
     */
    public function getConfigValue($currencycode, $store = null)
    {
        $value = $this->scopeConfig->getValue(
            'worldpay/miscellaneous/currency_codes',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        $value = $this->unserializeValue($value);
        if ($this->isEncodedArrayFieldValue($value)) {
            $value = $this->decodeArrayFieldValue($value);
        }

        if (is_array($value) || is_object($value)) {
            foreach ($value as $key => $valuepair) {
                if ($key == $currencycode) {
                    return $valuepair['exponent'];
                }
            }
        }
    }
}
