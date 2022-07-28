<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sapient\Worldpay\Block\GooglePay;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Template;

/**
 * Configuration for JavaScript instant purchase button component.
 *
 * @api
 * @since 100.2.0
 */
class Button extends Template
{
    
    /**
     * @var GOOGLE_PAY_DEFAULT_LOGO_PATH
     */
    private const GOOGLE_PAY_DEFAULT_LOGO_PATH = 'googlepay/logo/';
    /**
     * @var Config
     */
    private $instantPurchaseConfig;
    /**
     * @var scopeConfig
     */
    protected $_scopeConfig;
    /**
     * @var SessionManagerInterface
     */
    protected $session;

    /**
     * Button constructor.
     * @param Context $context
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Helper\Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->worldpayHelper = $helper;
    }

    /**
     * Checks if button enabled.
     *
     * @return bool
     * @since 100.2.0
     */
    public function isEnabled(): bool
    {
        return $this->worldpayHelper->isGooglePayEnable();
    }

    /**
     * Check if Google pay is enabled on PDP or not
     */
    public function isGooglePayEnableonPdp()
    {
        return $this->worldpayHelper->isGooglePayEnableonPdp();
    }
}
