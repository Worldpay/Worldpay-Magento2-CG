<?php

namespace Sapient\Worldpay\Block;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Checkout\Block\Cart\AbstractCart;
use Sapient\Worldpay\Helper\Data;
use Magento\Customer\Model\Session;
use Magento\Framework\Message\ManagerInterface;
use Sapient\Worldpay\Helper\Recurring;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Session\SessionManagerInterface;

/**
 * Webpayment block
 */
class PaymentRequestApi extends Template
{

    public const PAYMENT_SHIM_URL = 'https://storage.googleapis.com/prshim/v1/payment-shim.js';
    public const CSP_HASH = 'sha256-U2Pr6nr/58DuOrqmOIptLSxY0eHWqp8OVjb169SPqqU=';

    /**
     * @var $instalmentcountries
     */
    protected $httpHeader;
    /**
     * Constructor
     *
     * @param string $context
     * @param string $httpHeader
     * @param array $data
     */
    
    public function __construct(
        Template\Context $context,
        \Magento\Framework\HTTP\Header $httpHeader,
        array $data = []
    ) {

        $this->httpHeader = $httpHeader;
        parent::__construct(
            $context,
            $data
        );
    }
    /**
     * Get User Agent
     *
     * @return string
     */

    public function getUserAgent()
    {
        return $this->httpHeader->getHttpUserAgent();
    }
    /**
     * Get Payment Api Script
     *
     * @return string
     */

    public function getPaymentApiScript()
    {
        $script = '<script src="'.self::PAYMENT_SHIM_URL.'" ';
        $script .= 'integrity="'.self::CSP_HASH.'" src_type="url" ></script>';
        return $script;
    }
}
