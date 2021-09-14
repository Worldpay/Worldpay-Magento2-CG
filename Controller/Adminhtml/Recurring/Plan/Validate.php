<?php
/**
 * Copyright © 2020 Worldpay. All rights reserved.
 */

namespace Sapient\Worldpay\Controller\Adminhtml\Recurring\Plan;

use Magento\Framework\Controller\ResultFactory;
use Sapient\Worldpay\Helper\GeneralException;

class Validate extends \Sapient\Worldpay\Controller\Adminhtml\Recurring\Plan
{

    protected $helper;
    protected $storeManager;
    
    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Sapient\Worldpay\Model\Recurring\PlanFactory $planFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param GeneralException $helper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Sapient\Worldpay\Model\Recurring\PlanFactory $planFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Sapient\Worldpay\Helper\GeneralException $helper
    ) {
        $this->helper = $helper;
        $this->storeManager =$storeManager;
        parent::__construct($context, $planFactory);
    }
    
    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $response = ['error' => false];
        $planCode = $this->getRequest()->getParam('code');
        $productId = $this->getRequest()->getParam('product_id');
        $planCode = $this->buildCode($planCode, $productId);
        $data=$this->getRequest()->getPostValue();
        $store=$this->storeManager->getWebsite($data['website_id'])->getCode();
        if (strlen($planCode) > 25) {
            $response['error'] = true;
            $response['messages'][] = __($this->helper->getConfigValue('ACAM1', $store, 'website'));
            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($response);
        }

        $plan = $this->planFactory->create()->load($planCode, 'code');
        if ($plan->getId()) {
            $response['error'] = true;
            $response['messages'][] = __($this->helper->getConfigValue('ACAM2', $store, 'website'));
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($response);
    }
}
