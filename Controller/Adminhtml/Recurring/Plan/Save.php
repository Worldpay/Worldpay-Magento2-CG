<?php
/**
 * Copyright © 2020 Worldpay. All rights reserved.
 */

namespace Sapient\Worldpay\Controller\Adminhtml\Recurring\Plan;

use Magento\Framework\Controller\ResultFactory;

class Save extends \Sapient\Worldpay\Controller\Adminhtml\Recurring\Plan
{
    /**
     * @var \Sapient\Payment\Ui\DataProvider\Product\Form\Modifier\Data\RecurringPlans
     */
    private $planGridDataProvider;

    /**
     * Save controller action constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Sapient\Worldpay\Model\Recurring\PlanFactory $planFactory
     * @param \Sapient\Worldpay\Ui\DataProvider\Product\Form\Modifier\Data\RecurringPlans $planGridDataProvider
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Sapient\Worldpay\Model\Recurring\PlanFactory $planFactory,
        \Sapient\Worldpay\Ui\DataProvider\Product\Form\Modifier\Data\RecurringPlans $planGridDataProvider,
        \Magento\Framework\Locale\FormatInterface $localeFormat
    ) {
        parent::__construct($context, $planFactory);
        $this->planGridDataProvider = $planGridDataProvider;
        $this->localeFormat = $localeFormat;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $productId = $this->getRequest()->getParam('product_id');
        if ($data && $productId) {
            $data['code'] = $this->buildCode(
                isset($data['code']) ? $data['code'] : '',
                $productId
            );
            if (array_key_exists('interval_amount', $data) && !is_numeric($data['interval_amount'])) {
                $data['interval_amount'] = $this->localeFormat->getNumber($data['interval_amount']);
            }
            if (array_key_exists('number_of_payments', $data) && !$data['number_of_payments']) {
                unset($data['number_of_payments']);
            }
            if (array_key_exists('number_of_trial_intervals', $data) && !$data['number_of_trial_intervals']) {
                unset($data['number_of_trial_intervals']);
            }
            if (!array_key_exists('number_of_trial_intervals', $data) && array_key_exists('trial_interval', $data)) {
                unset($data['trial_interval']);
            }
            $model = $this->planFactory->create();
            $model->addData($data);
            $model->setProductId($productId);

            try {
                $model->save();
                return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData([
                    'error' => false,
                    'plan_data' => $this->planGridDataProvider->preparePlanData($model)
                ]);
            } catch (\Exception $e) {
                return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData([
                    'error' => true,
                    'messages' => [$e->getMessage()]
                ]);
            }
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData([
            'error' => true,
            'messages' => [__('Something went wrong, please reload the page')]
        ]);
    }
}
