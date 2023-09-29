<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Worldpay Multishipping PayByLink Shipment address
 *
 */
namespace Sapient\Worldpay\Block\Multishipping\Paybylink\Email;

/**
 * Sales Order Email items.
 *
 * @api
 * @since 100.0.2
 */
class Addresses extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Magento\Customer\Model\Address\Config
     */
    protected $_addressConfig = null;

    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    protected $wplogger;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;
     /**
      * constructor
      *
      * @param \Magento\Backend\Block\Template\Context $context
      * @param  \Magento\Customer\Model\Address\Config $addressConfig
      * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
      * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
      * @param array $data
      */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Customer\Model\Address\Config $addressConfig,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        array $data = []
    ) {
        $this->wplogger = $wplogger;
        $this->_addressConfig = $addressConfig;
        $this->quoteRepository = $quoteRepository;
        parent::__construct($context, $data);
    }

    /**
     * Get Multishipping Shipment Addresses
     */
    public function getQuote()
    {
        $quote_id = $this->getData('quote_id');
        $quote = $this->quoteRepository->get($quote_id);
        return $quote;
    }

    /**
     * Format Shipping Address
     *
     * @param array $address
     * @return array
     */
    public function getFormatAddressByCode($address)
    {
        $renderer = $this->_addressConfig->getFormatByCode('html')->getRenderer();
        return $renderer->renderArray($address);
    }
}
