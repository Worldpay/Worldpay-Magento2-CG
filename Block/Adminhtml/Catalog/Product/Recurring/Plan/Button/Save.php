<?php
/**
 * Copyright © 2020 Worldpay. All rights reserved.
 */

namespace Sapient\Worldpay\Block\Adminhtml\Catalog\Product\Recurring\Plan\Button;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Sapient\Worldpay\Helper\Recurring as RecurringHelper;

class Save implements ButtonProviderInterface
{
    
    /**
     * @var RecurringHelper
     */
    private $recurringHelper;
    /**
     * Constructor
     *
     * @param RecurringHelper $recurringHelper
     */

    public function __construct(
        RecurringHelper $recurringHelper
    ) {
        $this->recurringHelper = $recurringHelper;
    }
    /**
     * @inheritdoc
     */
    public function getButtonData()
    {
        return [
            'label' => $this->recurringHelper->getAdminLabels('AD14'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => ['button' => ['event' => 'save']],
                'form-role' => 'save',
            ]
        ];
    }
}
