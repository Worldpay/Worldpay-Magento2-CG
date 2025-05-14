<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sapient\Worldpay\Block\InstantPurchase;

use Magento\Catalog\Block\Product\View as ProductView;
use Magento\Framework\View\Element\Template\Context;
use Magento\InstantPurchase\Model\Config;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Session\SessionManagerInterface;
use Sapient\Worldpay\Helper\ProductOnDemand;

/**
 * Configuration for JavaScript instant purchase button component.
 *
 * @api
 * @since 100.2.0
 */
class Button extends Template
{
    /**
     * @var Config
     */
    private $instantPurchaseConfig;
    /**
     * @var SessionManagerInterface
     */
    protected $session;

    protected ProductView $productView;

    protected ProductOnDemand $productOnDemand;
    /**
     * Constructor
     *
     * @param Context $context
     * @param InstantPurchaseConfig $instantPurchaseConfig
     * @param Session $session
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $instantPurchaseConfig,
        \Magento\Framework\Session\SessionManagerInterface $session,
        ProductView $productView,
        ProductOnDemand $productOnDemand,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->instantPurchaseConfig = $instantPurchaseConfig;
        $this->session = $session;
        $this->productView = $productView;
        $this->productOnDemand = $productOnDemand;
    }

    /**
     * Checks if button enabled.
     *
     * @return bool
     * @since 100.2.0
     */
    public function isEnabled(): bool
    {
        return $this->instantPurchaseConfig->isModuleEnabled($this->getCurrentStoreId()) && !$this->isProductOnDemand();
    }

    /**
     * @inheritdoc
     * @since 100.2.0
     */
    public function getJsLayout(): string
    {
        $buttonText = $this->instantPurchaseConfig->getButtonText($this->getCurrentStoreId());
        $purchaseUrl = $this->getUrl('worldpay/button/placeOrder', ['_secure' => true]);
        // String data does not require escaping here and handled on transport level and on client side
        $this->jsLayout['components']['instant-purchase']['config']['buttonText'] = $buttonText;
        $this->jsLayout['components']['instant-purchase']['config']['purchaseUrl'] = $purchaseUrl;
        $this->jsLayout['components']['instant-purchase']['config']['sessionId'] = $this->session->getSessionId();
        return parent::getJsLayout();
    }

    /**
     * Returns active store view identifier.
     *
     * @return int
     */
    private function getCurrentStoreId(): int
    {
        return $this->_storeManager->getStore()->getId();
    }
     /**
      * Returns session ID
      *
      * @return int
      */

    public function getSessionId()
    {
        return $this->session->getSessionId();
    }

    private function isProductOnDemand(): bool
    {
        $product = $this->productView->getProduct();

        if ($product) {
            return $this->productOnDemand->isProductOnDemand($product);
        }

        return false;
    }
}
