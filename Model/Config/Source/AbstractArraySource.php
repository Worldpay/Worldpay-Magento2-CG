<?php
/**
 * Copyright © 2020 Worldpay. All rights reserved.
 */

namespace Sapient\Worldpay\Model\Config\Source;

abstract class AbstractArraySource implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @inheritdoc
     */
    abstract public function toOptionArray();

    /**
     * ToOptionHash
     *
     * @return array
     */
    public function toOptionHash()
    {
        $result = [];
        foreach ($this->toOptionArray() as $item) {
            $result[$item['value']] = $item['label'];
        }

        return $result;
    }
}
