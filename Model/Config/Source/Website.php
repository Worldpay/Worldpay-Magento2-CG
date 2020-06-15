<?php
/**
 * Copyright © 2020 Worldpay. All rights reserved.
 */

namespace Sapient\Worldpay\Model\Config\Source;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Directory\Helper\Data;

class Website implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Data
     */
    private $directoryHelper;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Data $directoryHelper
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Data $directoryHelper
    ) {
        $this->storeManager = $storeManager;
        $this->directoryHelper = $directoryHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $websites = [
            [
                'label' => __('All Websites') . ' [' . $this->directoryHelper->getBaseCurrencyCode() . ']',
                'value' => 0,
            ]
        ];

        $websitesList = $this->storeManager->getWebsites();
        if (count($websitesList) > 1) {
            foreach ($websitesList as $website) {
                /** @var \Magento\Store\Model\Website $website */
                $websites[] = [
                    'label' => $website->getName() . ' [' . $website->getBaseCurrencyCode() . ']',
                    'value' => $website->getId(),
                ];
            }
        }

        return $websites;
    }
}
