<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Sapient\Worldpay\Model\Recurring\Subscription;

use Magento\Sales\Model\AbstractModel;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Customer\Model\Address\AddressModelInterface;
use Sapient\Worldpay\Model\Recurring\Subscription;

/**
 * Subscription Address
 */
class Address extends AbstractModel implements AddressModelInterface
{
    /**
     * @var Subscription
     */
    private $subscription;

    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Sapient\Worldpay\Model\ResourceModel\Recurring\Subscription\Address::class);
    }

    /**
     * Set subscription
     *
     * @param Subscription $subscription
     * @return $this
     */
    public function setSubscription(Subscription $subscription)
    {
        $this->subscription = $subscription;
        return $this;
    }

    /**
     * Get subscription
     *
     * @return Subscription
     */
    public function getSubscription()
    {
        return $this->subscription;
    }

    /**
     * Combine values of street lines into a single string
     *
     * @param string[]|string $value
     * @return string
     */
    protected function implodeStreetValue($value)
    {
        if (is_array($value)) {
            $value = trim(implode(PHP_EOL, $value));
        }
        return $value;
    }

    /**
     * Enforce format of the street field
     *
     * @param array|string $key
     * @param string $value
     * @return \Magento\Framework\DataObject
     */
    public function setData($key, $value = null)
    {
        if (is_array($key)) {
            $key = $this->implodeStreetField($key);
        } elseif ($key == OrderAddressInterface::STREET) {
            $value = $this->implodeStreetValue($value);
        }
        return parent::setData($key, $value);
    }

    /**
     * Implode value of the street field, if it is present among other fields
     *
     * @param array $data
     * @return array
     */
    protected function implodeStreetField(array $data)
    {
        if (array_key_exists(OrderAddressInterface::STREET, $data)) {
            $data[OrderAddressInterface::STREET] = $this->implodeStreetValue($data[OrderAddressInterface::STREET]);
        }
        return $data;
    }

    /**
     * Create fields street1, street2, etc.
     *
     * @return $this
     */
    public function explodeStreetAddress()
    {
        $streetLines = $this->getStreet();
        foreach ($streetLines as $lineNumber => $lineValue) {
            $this->setData(OrderAddressInterface::STREET . ($lineNumber + 1), $lineValue);
        }
        return $this;
    }

    /**
     * Get street line by number
     *
     * @param int $number
     * @return string
     */
    public function getStreetLine($number)
    {
        $lines = $this->getStreet();
        return isset($lines[$number - 1]) ? $lines[$number - 1] : '';
    }
}
