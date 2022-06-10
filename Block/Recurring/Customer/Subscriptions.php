<?php
/**
 * Copyright © 2020 Worldpay, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Sapient\Worldpay\Block\Recurring\Customer;

use Sapient\Worldpay\Model\Recurring\Subscription;
use Sapient\Worldpay\Model\ResourceModel\Recurring\Subscription\CollectionFactory;

class Subscriptions extends \Magento\Framework\View\Element\Template
{
    
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var CollectionFactory
     */
    private $subscriptionCollectionFactory;

    /**
     * @var \Sapient\Worldpay\Model\ResourceModel\Recurring\Subscription\Collection
     */
    private $subscriptionCollection;

    /**
     * @var \Sapient\Worldpay\Helper\Recurring
     */
    private $recurringHelper;

    /**
     * Subscriptions block constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param CollectionFactory $subscriptionCollectionFactory
     * @param \Sapient\Worldpay\Helper\Recurring $recurringHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        CollectionFactory $subscriptionCollectionFactory,
        \Sapient\Worldpay\Helper\Recurring $recurringHelper,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->subscriptionCollectionFactory = $subscriptionCollectionFactory;
        $this->recurringHelper = $recurringHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get subscriptions collection
     *
     * @return \Sapient\Worldpay\Model\ResourceModel\Recurring\Subscription\Collection
     */
    public function getSubscriptions()
    {
        if ($this->subscriptionCollection === null) {
            $this->subscriptionCollection = $this->subscriptionCollectionFactory->create()
                ->addCustomerIdFilter($this->customerSession->getCustomerId())
                ->joinPlans(['interval', 'number_of_trial_intervals', 'trial_interval'])
                ->addWebsiteFilter()
                ->addOrder('created_at', \Magento\Framework\Data\Collection::SORT_ORDER_DESC);
        }
        return $this->subscriptionCollection;
    }
     /**
      * Check is subscriptions functionality enabled end date globally
      *
      * @return bool
      */
    public function isEndDateEnabled()
    {
        return $this->recurringHelper->getSubscriptionValue('worldpay/subscriptions/endDate');
    }
    
    /**
     * Retrieve my account labels
     *
     * @param string $labelCode
     * @return array
     */
    public function getMyAccountLabels($labelCode)
    {
        return $this->recurringHelper->getAccountLabelbyCode($labelCode);
    }
    
    /**
     * Prepare layout (initialise pagination block)
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $pagination=$this->getLayout()->createBlock(\Magento\Theme\Block\Html\Pager::class, 'subscriptions_pagination')
            ->setCollection($this->getSubscriptions())
            ->setPath('worldpay/recurring/index');
        $this->setChild('pagination', $pagination);

        return $this;
    }

    /**
     * Get subscription plan interval label
     *
     * @param Subscription $subscription
     * @return string
     */
    public function getSubscriptionIntervalLabel(Subscription $subscription)
    {
        return $this->recurringHelper->getPlanIntervalLabel($subscription->getInterval());
    }

    /**
     * Get trial info
     *
     * @param Subscription $subscription
     * @return string
     */
    public function getSubscriptionTrialLabel(Subscription $subscription)
    {
        return $this->recurringHelper->getSubscriptionTrialLabel($subscription);
    }

    /**
     * Get pagination html
     *
     * @return string
     */
    public function getPaginationHtml()
    {
        return $this->getChildHtml('pagination');
    }

    /**
     * Get order id column value
     *
     * @param Subscription $subscription
     * @return string
     */
    public function getOrderIdLabel(Subscription $subscription)
    {
        if ($subscription->getOriginalOrderId()) {
            return sprintf(
                '<a href="%s" class="order-link"><span>%s</span></a>',
                $this->getUrl('sales/order/view', ['order_id' => $subscription->getOriginalOrderId()]),
                $this->escapeHtml($subscription->getOriginalOrderIncrementId())
            );
        }

        return $this->escapeHtml($subscription->getOriginalOrderIncrementId());
    }

    /**
     * Retrieve edit url
     *
     * @param Subscription $subscription
     * @return string
     */
    public function getEditUrl(Subscription $subscription)
    {
        return $this->getUrl('worldpay/recurring/edit', ['subscription_id'=>$subscription->getId(), '_secure' => true]);
    }
    
    /**
     * Retrieve cancel url
     *
     * @param Subscription $subscription
     * @return string
     */
    public function getCancelUrl(Subscription $subscription)
    {
        return $this->getUrl(
            'worldpay/recurring/cancel',
            [
                'subscription_id' => $subscription->getId(),
                '_secure' => true
            ]
        );
    }
    
    /**
     * Get my account specific exception
     *
     * @param string $exceptioncode
     * @return string
     */
    public function getMyAccountSpecificException($exceptioncode)
    {
        return $this->recurringHelper->getMyAccountExceptions($exceptioncode);
    }
}
