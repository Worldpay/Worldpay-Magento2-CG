<?php

/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Model\Config\Source;

class KlarnaCountries implements \Magento\Framework\Option\ArrayInterface
{
    protected $_countryCollectionFactory;
    
    protected $_scopeConfig;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Data $directoryHelper
     */
    public function __construct(
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_countryCollectionFactory = $countryCollectionFactory;
    }
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $countryCollection = $this->getCountries();
        foreach ($countryCollection as $key => $value) {
            if (empty($value['value'])) {
                unset($countryCollection[$key]);
            }
        }
        $optionArray = $countryCollection;
        return $optionArray;
    }
    
    public function getCountryCollection()
    {
        $collection = $this->_countryCollectionFactory->create()->loadByStore();
        return $collection;
    }
    
    /**
     * Retrieve list of klarna countries
     *
     * @return array
     */
    public function getTopDestinations()
    {
        $destinations = $this->_scopeConfig->getValue(
            'worldpay/klarna_config/klarna_countries_config/klarna_contries',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return !empty($destinations) ? explode(',', $destinations) : [];
    }
    
    /**
     * Retrieve list of countries in array option
     *
     * @return array
     */
    public function getCountries()
    {
        return $options = $this->getCountryCollection()
            ->addCountryCodeFilter($this->getTopDestinations())
            ->toOptionArray();
    }
}
