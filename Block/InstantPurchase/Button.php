<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sapient\Worldpay\Block\InstantPurchase;

use Magento\Framework\View\Element\Template\Context;
use Magento\InstantPurchase\Model\Config;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Session\SessionManagerInterface;

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
    protected $_scopeConfig;
    /**
     * @var SessionManagerInterface
     */
    protected $session;

    /**
     * Button constructor.
     * @param Context $context
     * @param Config $instantPurchaseConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $instantPurchaseConfig,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Session\SessionManagerInterface $session,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->instantPurchaseConfig = $instantPurchaseConfig;
        $this->_scopeConfig = $scopeConfig;
        $this->session = $session;
    }

    /**
     * Checks if button enabled.
     *
     * @return bool
     * @since 100.2.0
     */
    public function isEnabled(): bool
    {
        return $this->instantPurchaseConfig->isModuleEnabled($this->getCurrentStoreId());
    }

    /**
     * @inheritdoc
     * @since 100.2.0
     */
    public function getJsLayout(): string
    {
        $buttonText = $this->instantPurchaseConfig->getButtonText($this->getCurrentStoreId());
        $purchaseUrl = $this->getUrl('worldpay/button/placeOrder', ['_secure' => true]);
        $is3DSEnabled = (bool) $this->_scopeConfig->getValue(
            'worldpay/3ds_config/do_3Dsecure',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $is3DS2Enabled = (bool) $this->_scopeConfig->getValue(
            'worldpay/3ds_config/enable_dynamic3DS2',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
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
    
    public function getSessionId()
    {
        return $this->session->getSessionId();
    }
}