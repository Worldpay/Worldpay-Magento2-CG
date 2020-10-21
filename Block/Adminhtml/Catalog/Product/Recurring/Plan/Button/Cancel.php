<?php
/**
 * Copyright © 2020 Worldpay. All rights reserved.
 */

namespace Sapient\Worldpay\Block\Adminhtml\Catalog\Product\Recurring\Plan\Button;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Sapient\Worldpay\Ui\DataProvider\Product\Form\Modifier\RecurringPlans;
use Sapient\Worldpay\Helper\Recurring as RecurringHelper;

class Cancel implements ButtonProviderInterface
{
    /**
     * @var RecurringHelper
     */
    private $recurringHelper;
    
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
            'label' => $this->recurringHelper->getAdminLabels('AD13'),
            'data_attribute' => [
                'mage-init' => [
                    'Magento_Ui/js/form/button-adapter' => [
                        'actions' => [
                            [
                                'targetName' => 'product_form.product_form.' . RecurringPlans::CODE_RECURRING_DATA
                                    . '.' . RecurringPlans::CODE_ADD_PLAN_MODAL,
                                'actionName' => 'toggleModal'
                            ]
                        ]
                    ]
                ]
            ],
            'on_click' => ''
        ];
    }
}
