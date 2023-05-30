<?php

/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Helper;

use Magento\Store\Model\Store;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * MinSaleQty value manipulation helper
 */
class Instalmentconfig
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
      * @var instalmentTypes
      */
    protected $instalmentTypes;
    
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $json;
    
    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Math\Random $mathRandom
     * @param \Sapient\Worldpay\Model\Config\Source\InstalmentTypes $instalmentTypes
     * @param SerializerInterface $serializer
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     */

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Math\Random $mathRandom,
        \Sapient\Worldpay\Model\Config\Source\InstalmentTypes $instalmentTypes,
        SerializerInterface $serializer,
        \Magento\Framework\Serialize\Serializer\Json $json
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->mathRandom = $mathRandom;
        $this->instalmentTypes = $instalmentTypes;
        $this->serializer = $serializer;
        $this->json = $json;
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
            foreach ($value as $instalment_type => $instalment_country) {
                if (!array_key_exists($instalment_type, $data)) {
                    $data[$instalment_type] = $instalment_country;
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
    public function unserializeValue($value)
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
    public function isEncodedArrayFieldValue($value)
    {
        if (!is_array($value)) {
            return false;
        }
        unset($value['__empty']);
        foreach ($value as $row) {
            if (!is_array($row) || !array_key_exists(
                'worldpay_instalment_type',
                $row
            ) || !array_key_exists('country', $row)
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
            $result[$resultId] = ['worldpay_instalment_type' => $payment_type,
                'country' => $merchant_detail['country'],
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
    public function decodeArrayFieldValue(array $value)
    {
        $result = [];
        unset($value['__empty']);
        foreach ($value as $row) {
            if (!is_array($row) || !array_key_exists(
                'worldpay_instalment_type',
                $row
            ) || !array_key_exists('country', $row)
            ) {
                continue;
            }
            if (!empty($row['worldpay_instalment_type']) && !empty($row['country'])
            ) {
                $payment_type = $row['worldpay_instalment_type'];
                $rs['country'] = $row['country'];
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
     * Retrieve intsalment type value from config
     *
     * @param null|string|bool|int $instalmenttype
     * @param null|string|bool|int|Store $store
     * @return float|null
     */
    public function getConfigValue($instalmenttype, $store = null)
    {
        $value = $this->scopeConfig->getValue(
            'worldpay/lat_america_payments/instalment_config',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        $value->$this->unserializeValue($value);
        if ($this->isEncodedArrayFieldValue($value)) {
            $value = $this->decodeArrayFieldValue($value);
        }

        if (!empty($value[$instalmenttype])) {
            return $value[$instalmenttype];
        }
    }

    /**
     * Retrieve intsalment country from config
     *
     * @param null|Store $store
     * @return array|null
     */
    public function getConfigCountries($store = null)
    {

        $countrieslist = [];
        $value = $this->getSerializedConfigValue('worldpay/lat_america_payments/instalment_config');
        if (is_array($value) || is_object($value)) {
            foreach ($value as $instalmenttype => $instalmentCountry) {
                foreach ($instalmentCountry as $key => $data) {
                    array_push($countrieslist, $data);
                }
            }
            return $countrieslist;
        }
    }

    /**
     * Retrieve intsalment type country from config
     *
     * @param null|string|bool|int $countryid
     * @param null|string|bool|int|Store $store
     * @return float|null
     */
    public function getConfigTypeForCountry($countryid, $store = null)
    {

        $value = $this->getSerializedConfigValue('worldpay/lat_america_payments/instalment_config');
        if (is_array($value) || is_object($value)) {
            foreach ($value as $instalmenttype => $instalmentCountry) {
                foreach ($instalmentCountry as $key => $data) {
                    $countries = $data;
                    if (!empty($data) && in_array($countryid, $data)) {
                        $result = $this->instalmentTypes->toOptionArray();
                        $instalmentTypeCountry = $this->getInstalmentTypeForCountry($result, $instalmenttype);
                        return $instalmentTypeCountry;
                    }
                }
            }
        }
    }
    /**
     * Get config value
     *
     * @param string $result
     * @param string $instalmenttype
     * @return mixed
     */

    private function getInstalmentTypeForCountry($result, $instalmenttype)
    {
        foreach ($result as $types => $instalmentdata) {
            foreach ($instalmentdata as $type => $instamentno) {
                if ($type == $instalmenttype) {
                    return $instalmentdata[$type];
                }
            }
        }
        return true;
    }

    /**
     * Get config value
     *
     * @param string $configPath
     * @param string $store
     * @return mixed
     */
    public function getSerConfigValue($configPath, $store = null)
    {
        return $this->scopeConfig->getValue(
            $configPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check if value is a serialized string
     *
     * @param string $value
     * @return boolean
     */
    private function isSerialized($value)
    {
        return (boolean) preg_match('/^((s|i|d|b|a|O|C):|N;)/', $value);
    }

    /**
     * Get serialized config value temporarily solution to get unserialized config value should be deprecated in 2.3.x
     *
     * @param strin $configPath
     * @param string $store
     * @return mixed
     */
    public function getSerializedConfigValue($configPath, $store = null)
    {
        $value = $this->getSerConfigValue($configPath, $store);

        if (empty($value)) {
            return false;
        }

        if ($this->isSerialized($value)) {
            $unserializer = $this->serializer;
        } else {
            $unserializer = $this->json;
        }

        return $unserializer->unserialize($value);
    }
}
