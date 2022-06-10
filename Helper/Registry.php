<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Helper;

class Registry extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var registry
     */
    protected $_registry;

    /**
     * Recurring constructor.
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
     * RemoveAllData
     *
     * @return string
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
     * GetworldpayRedirectUrl
     *
     * @return string
     */
    public function getworldpayRedirectUrl()
    {
        return $this->getDataFromRegistry('worldpayRedirectUrl');
    }

    /**
     * SetworldpayRedirectUrl
     *
     * @param string $data
     * @return string
     */
    public function setworldpayRedirectUrl($data)
    {
        return $this->addDataToRegistry('worldpayRedirectUrl', $data);
    }

    /**
     * AddDataToRegistry
     *
     * @param string $name
     * @param string $data
     * @return string
     */
    public function addDataToRegistry($name, $data)
    {
        $this->removeDataFromRegistry($name);

        $this->_registry->register($name, $data);

        return $this;
    }

    /**
     * GetDataFromRegistry
     *
     * @param string $name
     * @return string
     */
    public function getDataFromRegistry($name)
    {
        if ($data = $this->_registry->registry($name)) {
            return $data;
        }

        return false;
    }

    /**
     * RemoveDataFromRegistry
     *
     * @param string $name
     * @return string
     */
    public function removeDataFromRegistry($name)
    {
        $this->_registry->unregister($name);

        return $this;
    }
}
