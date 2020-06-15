<?php
/**
 * Copyright © 2020 Worldpay. All rights reserved.
 */

namespace Sapient\Worldpay\Model\ResourceModel\Recurring\Plan;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * Collection constructor.
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    /**
     * Define model and resource model, set default order
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Sapient\Worldpay\Model\Recurring\Plan', 'Sapient\Worldpay\Model\ResourceModel\Recurring\Plan');

        $this->addOrder('sort_order', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);
    }

    /**
     * Filter collection by product
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param bool $addWebsiteFilter
     * @return $this
     */
    public function addProductFilter(\Magento\Catalog\Model\Product $product, $addWebsiteFilter = true)
    {
        $this->addProductIdFilter($product->getId());
        if ($addWebsiteFilter) {
            $this->addWebsiteFilter($product->getStore()->getWebsiteId());
        }
        return $this;
    }

    /**
     * Filter collection by product id
     *
     * @param $productId
     * @return $this
     */
    public function addProductIdFilter($productId)
    {
        if ($productId) {
            $this->addFieldToFilter('main_table.product_id', $productId);
        }

        return $this;
    }

    /**
     * Filter collection by website
     *
     * @param null|int|array $websiteId
     * @param bool $includeDefault
     * @return $this
     */
    public function addWebsiteFilter($websiteId = null, $includeDefault = true)
    {
        if ($websiteId === null) {
            $websiteId = $this->storeManager->getStore()->getWebsiteId();
        }

        if (!is_array($websiteId)) {
            $websiteId = [$websiteId];
        }

        if ($includeDefault && !in_array(0, $websiteId, true)) {
            $websiteId[] = 0;
        }

        $this->addFieldToFilter('main_table.website_id', ['in' => $websiteId]);

        return $this;
    }

    /**
     * Add active filter
     *
     * @param bool $inverse
     * @return $this
     */
    public function addActiveFilter($inverse = false)
    {
        $this->addFieldToFilter('main_table.active', (int)(!$inverse));

        return $this;
    }
}
