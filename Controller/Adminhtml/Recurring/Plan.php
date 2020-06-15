<?php
/**
 * Copyright © 2020 Worldpay. All rights reserved.
 */

namespace Sapient\Worldpay\Controller\Adminhtml\Recurring;

abstract class Plan extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Catalog::products';

    /**
     * @var \Sapient\Worldpay\Model\Recurring\PlanFactory
     */
    protected $planFactory;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Sapient\Worldpay\Model\Recurring\PlanFactory $planFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Sapient\Worldpay\Model\Recurring\PlanFactory $planFactory
    ) {
        $this->planFactory = $planFactory;
        parent::__construct($context);
    }

    /**
     * Build Plan Code
     *
     * @param string $code
     * @param integer $productId
     * @return string
     */
    protected function buildCode($code, $productId)
    {
        return $productId . '_' . $code;
    }
}
