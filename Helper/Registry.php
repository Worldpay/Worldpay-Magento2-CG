<?php
/**
 * Copyright © 2020 Worldpay. All rights reserved.
 */
namespace Sapient\Worldpay\Helper;

class Registry extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Registry objects
     *
     * @var \Magento\Framework\Registry
     */
    protected $_registry;
    /**
     * Registry constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Registry $registry
    ) {

         $this->_registry = $registry;
         parent::__construct($context);
    }

    /**
     * Remove all datas
     *
     * @return $this
     */
    public function removeAllData()
    {
        $keys = [
            'worldpayRedirectUrl',
        ];

        foreach ($keys as $key) {
            $this->removeDataFromRegistry($key);
        }

        return $this;
    }

    /**
     * Get worldpay redirect url
     *
     * @return string
     */
    public function getworldpayRedirectUrl()
    {
        return $this->getDataFromRegistry('worldpayRedirectUrl');
    }

    /**
     * Set worldpay redirect url
     *
     * @param string|array $data
     * @return $this
     */
    public function setworldpayRedirectUrl($data)
    {
        return $this->addDataToRegistry('worldpayRedirectUrl', $data);
    }

    /**
     * Add data to registry
     *
     * @param string $name
     * @param array|string $data
     * @return $this
     */
    public function addDataToRegistry($name, $data)
    {
        $this->removeDataFromRegistry($name);

        $this->_registry->register($name, $data);

        return $this;
    }

    /**
     * Get data from registry
     *
     * @param string $name
     * @return mixed
     */
    public function getDataFromRegistry($name)
    {
        if ($data = $this->_registry->registry($name)) {
            return $data;
        }

        return false;
    }

    /**
     * Remove data from registry
     *
     * @param string $name
     * @return $this
     */
    public function removeDataFromRegistry($name)
    {
        $this->_registry->unregister($name);

        return $this;
    }
}
