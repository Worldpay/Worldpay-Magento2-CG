<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Sapient\Worldpay\Block;

use Magento\Framework\App\DefaultPathInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Sapient\Worldpay\Block\Recurring\Customer\Subscriptions;
use Sapient\Worldpay\Helper\Recurring;
use Sapient\Worldpay\Helper\Data;

/**
 * Description of SubscriptionsLink
 *
 * @author aatrai
 */
class SubscriptionsLink extends \Magento\Framework\View\Element\Html\Link\Current
{

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface|null
     */
    protected $_scopeConfig = null;
    
    /**
     * SubscriptionsLink constructor
     *
     * @param Context $context
     * @param Subscriptions $subscriptionconfig
     * @param Recurring $helper
     * @param DefaultPathInterface $defaultPath
     * @param Data $worldpayHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Subscriptions $subscriptionconfig,
        Recurring $helper,
        DefaultPathInterface $defaultPath,
        Data $worldpayHelper,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath);
        $this->subscriptionconfig = $subscriptionconfig;
        $this->helper = $helper;
        $this->worldpayHelper = $worldpayHelper;
    }

    /**
     * Render the block if needed
     *
     * @return string
     */
    public function _toHtml()
    {
        if ($this->worldpayHelper->isWorldPayEnable() && $this->checkSubscriptionTabToBeEnabled()) {
            return parent::_toHtml();
        } else {
            return '';
        }
    }

    /**
     * Check if the subscription tab is enabled?
     *
     * @return bool
     */
    public function checkSubscriptionTabToBeEnabled()
    {
        if ($this->helper->getSubscriptionValue('worldpay/subscriptions/active') ||
                !empty($this->subscriptionconfig->getSubscriptions()->getData())) {
            return true;
        }
    }
}
