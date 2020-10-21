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

    protected $_scopeConfig = null;

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

    public function _toHtml()
    {
        if ($this->worldpayHelper->isWorldPayEnable() && $this->checkSubscriptionTabToBeEnabled()) {
            return parent::_toHtml();
        } else {
            return '';
        }
    }

    public function checkSubscriptionTabToBeEnabled()
    {
        if ($this->helper->getSubscriptionValue('worldpay/subscriptions/active') ||
                !empty($this->subscriptionconfig->getSubscriptions()->getData())) {
            return true;
        }
    }
}
