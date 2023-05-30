<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Sapient\Worldpay\Block\Checkout\Multishipping;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Quote\Model\Quote\Address\Total\Collector;
use Magento\Store\Model\ScopeInterface;

class Billing extends \Magento\Multishipping\Block\Checkout\Billing
{
    /**
     * @var \Magento\Multishipping\Model\Checkout\Type\Multishipping
     */
    protected $_multishipping;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Payment\Model\Method\SpecificationInterface
     */
    protected $paymentSpecification;

    /**
     * Block alias fallback
     */
    public const DEFAULT_TYPE = 'default';

    /**
     * @var \Magento\Tax\Helper\Data
     */
    protected $_taxHelper;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Quote\Model\Quote\TotalsCollector
     */
    protected $totalsCollector;

    /**
     * @var \Magento\Quote\Model\Quote\TotalsReader
     */
    protected $totalsReader;

    /**
     * @var \Sapient\Worldpay\Helper\Data
     */
    protected $wpHelper;
    /**
     * @var CheckoutHelper
     */
    protected $checkoutHelper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param \Magento\Payment\Model\Checks\SpecificationFactory $methodSpecificationFactory
     * @param \Magento\Multishipping\Model\Checkout\Type\Multishipping $multishipping
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Payment\Model\Method\SpecificationInterface $paymentSpecification
     * @param \Magento\Tax\Helper\Data $taxHelper
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector
     * @param \Magento\Quote\Model\Quote\TotalsReader $totalsReader
     * @param CheckoutHelper $checkoutHelper
     * @param \Sapient\Worldpay\Helper\Data $wpHelper
     * @param array $data
     * @param array $additionalChecks
     */

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Payment\Model\Checks\SpecificationFactory $methodSpecificationFactory,
        \Magento\Multishipping\Model\Checkout\Type\Multishipping $multishipping,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Payment\Model\Method\SpecificationInterface $paymentSpecification,
        \Magento\Tax\Helper\Data $taxHelper,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector,
        \Magento\Quote\Model\Quote\TotalsReader $totalsReader,
        CheckoutHelper $checkoutHelper,
        \Sapient\Worldpay\Helper\Data $wpHelper,
        array $data = [],
        array $additionalChecks = []
    ) {
        $this->_multishipping = $multishipping;
        $this->_checkoutSession = $checkoutSession;
        $this->paymentSpecification = $paymentSpecification;
        $this->checkoutHelper = $checkoutHelper;
        $data['checkoutHelper'] = $this->checkoutHelper;
        $this->_taxHelper = $taxHelper;
        $data['taxHelper'] = $this->_taxHelper;
        $this->wpHelper = $wpHelper;
        parent::__construct(
            $context,
            $paymentHelper,
            $methodSpecificationFactory,
            $multishipping,
            $checkoutSession,
            $paymentSpecification,
            $data,
            $additionalChecks
        );
        $this->priceCurrency = $priceCurrency;
        $data['taxHelper'] = $this->_taxHelper;
        $this->_isScopePrivate = true;
        $this->totalsCollector = $totalsCollector;
        $this->totalsReader = $totalsReader;
    }
    /**
     * Prepare Layout
     */
    protected function _prepareLayout()
    {
        $this->setTemplate('Sapient_Worldpay::multishipping/billing/billing.phtml');
        $this->pageConfig->addBodyClass('worldpay-multishipping');
        return parent::_prepareLayout();
    }
    /**
     * Get multishipping checkout model
     *
     * @return \Magento\Multishipping\Model\Checkout\Type\Multishipping
     */
    public function getCheckout()
    {
        return $this->_multishipping;
    }
    /**
     * Get total price
     *
     * @return float
     */
    public function getTotal()
    {
        return $this->getCheckout()->getQuote()->getGrandTotal();
    }
    /**
     * Get shipping addresses
     *
     * @return array
     */
    public function getShippingAddresses()
    {
        return $this->getCheckout()->getQuote()->getAllShippingAddresses();
    }
    /**
     * Render total block
     *
     * @param  mixed $totals
     * @param  null  $colspan
     * @return string
     */
    public function renderTotals($totals, $colspan = null)
    {
        // check if the shipment is multi shipment
        $totals = $this->getMultishippingTotals($totals);

        // sort totals by configuration settings
        $totals = $this->sortTotals($totals);

        if ($colspan === null) {
            $colspan = 3;
        }
        $totals = $this->getChildBlock(
            'totals'
        )->setTotals(
            $totals
        )->renderTotals(
            '',
            $colspan
        ) . $this->getChildBlock(
            'totals'
        )->setTotals(
            $totals
        )->renderTotals(
            'footer',
            $colspan
        );
        return $totals;
    }

    /**
     * Overwrite the total value of shipping amount for viewing purpose
     *
     * @param  array $totals
     * @return mixed
     * @throws \Exception
     */
    private function getMultishippingTotals($totals)
    {
        if (isset($totals['shipping']) && !empty($totals['shipping'])) {
            $total = $totals['shipping'];
            $shippingMethod = $total->getAddress()->getShippingMethod();
            if (isset($shippingMethod) && !empty($shippingMethod)) {
                $shippingRate = $total->getAddress()->getShippingRateByCode($shippingMethod);
                $shippingPrice = $shippingRate->getPrice();
            } else {
                $shippingPrice = $total->getAddress()->getShippingAmount();
            }
            /**
             * @var \Magento\Store\Api\Data\StoreInterface
             */
            $store = $this->getQuote()->getStore();
            $amountPrice = $store->getBaseCurrency()
                ->convert($shippingPrice, $store->getCurrentCurrencyCode());
            $total->setBaseShippingAmount($shippingPrice);
            $total->setShippingAmount($amountPrice);
            $total->setValue($amountPrice);
        }
        return $totals;
    }
    /**
     * Sort total information based on configuration settings.
     *
     * @param array $totals
     * @return array
     */
    private function sortTotals($totals): array
    {
        $sortedTotals = [];
        $sorts = $this->_scopeConfig->getValue(
            Collector::XML_PATH_SALES_TOTALS_SORT,
            ScopeInterface::SCOPE_STORES
        );

        $sorted = [];
        foreach ($sorts as $code => $sortOrder) {
            $sorted[$sortOrder] = $code;
        }
        ksort($sorted);

        foreach ($sorted as $code) {
            if (isset($totals[$code])) {
                $sortedTotals[$code] = $totals[$code];
            }
        }

        $notSorted = array_diff(array_keys($totals), array_keys($sortedTotals));
        foreach ($notSorted as $code) {
            $sortedTotals[$code] = $totals[$code];
        }

        return $sortedTotals;
    }
    /**
     * Get shipping address totals
     *
     * @param  Address $address
     * @return mixed
     */
    public function getShippingAddressTotals($address)
    {
        $totals = $address->getTotals();
        foreach ($totals as $total) {
            if ($total->getCode() == 'grand_total') {
                if ($address->getAddressType() == Address::TYPE_BILLING) {
                    $total->setTitle(__('Total'));
                } else {
                    $total->setTitle(__('Total for this address'));
                }
            }
        }
        return $totals;
    }
    /**
     * Get shipping address items
     *
     * @param  Address $address
     * @return array
     */
    public function getShippingAddressItems($address): array
    {
        return $address->getAllVisibleItems();
    }
    /**
     * Return row-level item html
     *
     * @param  \Magento\Framework\DataObject $item
     * @return string
     */
    public function getRowItemHtml(\Magento\Framework\DataObject $item)
    {
        $type = $this->_getItemType($item);
        $renderer = $this->_getRowItemRenderer($type)->setItem($item);
        $this->_prepareItem($renderer);
        return $renderer->toHtml();
    }
    /**
     * Retrieve renderer block for row-level item output
     *
     * @param  string $type
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    protected function _getRowItemRenderer($type)
    {
        $renderer = $this->getItemRenderer($type);
        if ($renderer !== $this->getItemRenderer(self::DEFAULT_TYPE)) {
            $renderer->setTemplate($this->getRowRendererTemplate());
        }
        return $renderer;
    }
    /**
     * Get the Edit shipping address URL
     *
     * @param  Address $address
     * @return string
     */
    public function getEditShippingAddressUrl($address)
    {
        return $this->getUrl('*/checkout_address/editShipping', ['id' => $address->getCustomerAddressId()]);
    }
    /**
     * Creates anchor name for address Id.
     *
     * @param int $addressId
     * @return string
     */
    public function getAddressAnchorName(int $addressId): string
    {
        return 'a' . $addressId;
    }
    /**
     * Returns all stored errors.
     *
     * @return array
     */
    public function getAddressErrors(): array
    {
        if (empty($this->addressErrors)) {
            $this->addressErrors = $this->session->getAddressErrors(true);
        }

        return $this->addressErrors ?? [];
    }
    /**
     * Retrieve virtual product collection array
     *
     * @return array
     */
    public function getVirtualItems()
    {
        return $this->getBillingAddress()->getAllVisibleItems();
    }
    /**
     * Get billin address totals
     *
     * @return mixed
     */
    public function getBillinAddressTotals()
    {
        return $this->getBillingAddressTotals();
    }
    /**
     * Get billing address totals
     *
     * @return mixed
     * @since 100.2.3
     */
    public function getBillingAddressTotals()
    {
        $address = $this->getQuote()->getBillingAddress();
        return $this->getShippingAddressTotals($address);
    }
    /**
     * Check if worldpay is enabled
     */
    public function isWorldpayEnable()
    {
        return $this->wpHelper->isWorldPayEnable();
    }
    /**
     * Get worldpay method code
     */
    public function getWorldpayMethodsCode()
    {
        return [
            'worldpay_cc',
            'worldpay_apm',
            'worldpay_wallets'
        ];
    }
    /**
     * Get Worldpay Methods
     */
    public function getWorldpayMethods()
    {
        $allMethods = $this->getMethods();
        $wpMethods = $this->getWorldpayMethodsCode();
        $wpPaymentMethods = [];
        foreach ($allMethods as $method) {
            if (in_array($method->getCode(), $wpMethods)) {
                $wpPaymentMethods[] = $method;
            }
        }
        return $wpPaymentMethods;
    }
    /**
     * Get other Payment methods
     */
    public function getOtherPaymentMethods()
    {
        $allMethods = $this->getMethods();
        $wpMethods = $this->getWorldpayMethodsCode();
        $otherPaymentMethods = [];
        foreach ($allMethods as $method) {
            if (!in_array($method->getCode(), $wpMethods)) {
                $otherPaymentMethods[] = $method;
            }
        }
        return $otherPaymentMethods;
    }
}
