<?php
/**
 * Copyright © 2020 Worldpay. All rights reserved.
 */

namespace Sapient\Worldpay\Controller\Adminhtml\Recurring\Plan;

use Magento\Framework\Controller\ResultFactory;

class Validate extends \Sapient\Worldpay\Controller\Adminhtml\Recurring\Plan
{
    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $response = ['error' => false];

        $planCode = $this->getRequest()->getParam('code');
        $productId = $this->getRequest()->getParam('product_id');
        $planCode = $this->buildCode($planCode, $productId);
        if (strlen($planCode) > 25) {
            $response['error'] = true;
            $response['messages'][] = __('Plan code should not exceed 25 characters.');
            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($response);
        }

        $plan = $this->planFactory->create()->load($planCode, 'code');
        if ($plan->getId()) {
            $response['error'] = true;
            $response['messages'][] = __('Plan with such code already exists');
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($response);
    }
}
