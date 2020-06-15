<?php
/**
 * Copyright © 2020 Worldpay. All rights reserved.
 */

namespace Sapient\Worldpay\Block\Adminhtml\Catalog\Product\Recurring\Plan\Button;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class Save implements ButtonProviderInterface
{
    /**
     * @inheritdoc
     */
    public function getButtonData()
    {
        return [
            'label' => __('Save Plan'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => ['button' => ['event' => 'save']],
                'form-role' => 'save',
            ]
        ];
    }
}
