<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sapient\Worldpay\Block\ApplePay;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Template;
use \Sapient\Worldpay\Logger\WorldpayLogger;
use Magento\Customer\Model\Context as CustomerContext;

/**
 * Configuration for JavaScript ApplePay button component.
 *
 * @api
 * @since 100.2.0
 */
class Button extends Template
{
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
        $this->wplogger = $wplogger;
    }

    /**
     * Checks if button enabled.
     *
     * @return bool
     * @since 100.2.0
     */
    public function isEnabled(): bool
    {
        return $this->worldpayHelper->isApplePayEnable();
    }

    /**
     * Check if Apple pay is enabled on PDP or not
     */
    public function isApplePayEnableonPdp()
    {
        return $this->worldpayHelper->isApplePayEnableonPdp();
    }

    /**
     * Get Apple pay Button Type
     */
    public function isApplePayButtonType()
    {
        return $this->worldpayHelper->getApplePayButtonType();
    }

    /**
     * Get Apple pay Button Color
     */
    public function isApplePayButtonColor()
    {
        return $this->worldpayHelper->getApplePayButtonColor();
    }

    /**
     * Get Apple pay Button Locale or not
     */
    public function isApplePayButtonLocale()
    {
        return $this->worldpayHelper->getApplePayButtonLocale();
    }
}
