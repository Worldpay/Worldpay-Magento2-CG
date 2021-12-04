<?php

/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Config\Source;

class MotoIntegrationMode implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'moto_direct', 'label' => __('Direct')],
           // ['value' => 'moto_redirect', 'label' => __('Redirect')],
        ];
    }
}
