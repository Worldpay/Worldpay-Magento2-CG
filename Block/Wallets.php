<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sapient\Worldpay\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Address\CustomerAddressDataProvider;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Customer\Model\Session as CustomerSession;

/**
 * Configuration for JavaScript wallets component.
 *
 * @api
 * @since 100.2.0
 */
class Wallets extends \Magento\Catalog\Block\Product\AbstractProduct
{
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
     * Button constructor
     *
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Session\SessionManagerInterface $session
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Helper\Data $helper
     * @param CustomerRepository $customerRepository
     * @param CustomerSession $customerSession
     * @param CustomerAddressDataProvider $customerAddressData
     * @param \Magento\Directory\Helper\Data $directoryData
     * @param \Sapient\Worldpay\Helper\Recurring $recurringHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Session\SessionManagerInterface $session,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Helper\Data $helper,
        CustomerRepository $customerRepository,
        CustomerSession $customerSession,
        CustomerAddressDataProvider $customerAddressData,
        \Magento\Directory\Helper\Data $directoryData,
        \Sapient\Worldpay\Helper\Recurring $recurringHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_scopeConfig = $scopeConfig;
        $this->session = $session;
        $this->worldpayHelper = $helper;
        $this->wplogger = $wplogger;
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->customerAddressData = $customerAddressData;
        $this->directoryData = $directoryData;
        $this->recurringHelper = $recurringHelper;
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
     * @inheritdoc
     * @since 100.2.0
     */
    public function isAppleEnabled(): bool
    {
        return $this->worldpayHelper->isApplePayEnable();
    }

    /**
     * @inheritdoc
     * @since 100.2.0
     */
    public function getJsLayout(): string
    {
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
        $this->jsLayout['components']['wp-wallet-pay']['config']['isGooglePayEnable']  = $this->isEnabled();
        $this->jsLayout['components']['wp-wallet-pay']['config']['env_mode']  = $this->getEnvMode();
        
        $this->jsLayout['components']['wp-wallet-pay']['config']['customerDetails']  = $this->getCustomerData();

        $this->jsLayout['components']['wp-wallet-pay']['config']['googlepayOptions']
        ['allowedCardNetworks']  = explode(',', $this->worldpayHelper->googlePaymentMethods());

        $this->jsLayout['components']['wp-wallet-pay']['config']
        ['googlepayOptions']['allowedCardAuthMethods']
        = explode(',', $this->worldpayHelper->googleAuthMethods());

        $this->jsLayout['components']['wp-wallet-pay']['config']['googlepayOptions']
        ['merchantName'] = $this->worldpayHelper->googleGatewayMerchantname();

        $this->jsLayout['components']['wp-wallet-pay']['config']['googlepayOptions']
        ['tokenizationSpecification']['type'] = 'PAYMENT_GATEWAY';

        $this->jsLayout['components']['wp-wallet-pay']['config']['googlepayOptions']
        ['tokenizationSpecification']['parameters']['gatewayMerchantId']
        = $this->worldpayHelper->googleGatewayMerchantid();
        
        $this->jsLayout['components']['wp-wallet-pay']['config']['googlepayOptions']
        ['tokenizationSpecification']['parameters']['gateway']  =  $this->worldpayHelper->googleMerchantname();

        $this->jsLayout['components']['wp-wallet-pay']['config']['googlepayOptions']
        ['gpay_button_popup_text']  = $this->getGooglePayPopupPlaceOrderText();

        $this->jsLayout['components']['wp-wallet-pay']['config']['googlepayOptions']
        ['isgooglepayenabledonpdp']  = $this->isGooglePayEnableonPdp();

        $this->jsLayout['components']['wp-wallet-pay']['config']['googlepayOptions']
        ['gpaybutton_color']  = $this->worldpayHelper->getGpayButtonColor();

        $this->jsLayout['components']['wp-wallet-pay']['config']['googlepayOptions']
        ['gpaybutton_type']  = $this->worldpayHelper->getGpayButtonType();

        $this->jsLayout['components']['wp-wallet-pay']['config']['googlepayOptions']
        ['gpaybutton_locale']  = $this->worldpayHelper->getGpayButtonLocale();
        
        $this->jsLayout['components']['wp-wallet-pay']['config']['purchaseUrl'] = $purchaseUrl;
        $this->jsLayout['components']['wp-wallet-pay']['config']['sessionId']   = $this->session->getSessionId();
        $this->jsLayout['components']['wp-wallet-pay']['config']['store_code'] = $this->getCurrentStoreCode();

        $this->jsLayout['components']['wp-wallet-pay']['config']
        ['countriesHtml'] = $this->getCountriesHtml(null, 'country_id', 'country', 'Country');

        $this->jsLayout['components']['wp-wallet-pay']['config']
        ['billingCountryHtml']
        = $this->getCountriesHtml(null, 'billing_country_id', 'billing_country', 'Billing Country');

        $this->jsLayout['components']['wp-wallet-pay']['config']
        ['regionJson']   = $this->getRegionJson();
        // Apple Pay config
        $this->jsLayout['components']['wp-wallet-pay']['config']
        ['applepayOptions']['isApplePayEnable']  = $this->isAppleEnabled();

        $this->jsLayout['components']['wp-wallet-pay']['config']
        ['applepayOptions']['appleMerchantId']  =  $this->worldpayHelper->appleMerchantId();

        $this->jsLayout['components']['wp-wallet-pay']['config']
        ['applepayOptions']['isApplePayEnableonPdp']  =  $this->isApplePayEnableonPdp();

        $this->jsLayout['components']['wp-wallet-pay']['config']
        ['applepayOptions']['applePayPopUpButtonText']  =  $this->getApplePayPopupPlaceOrderText();

        $this->jsLayout['components']['wp-wallet-pay']['config']
        ['applepayOptions']['applePayButtonColor']  =  $this->worldpayHelper->getApplePayButtonColor();

        $this->jsLayout['components']['wp-wallet-pay']['config']
        ['applepayOptions']['applePayButtonType']  =  $this->worldpayHelper->getApplePayButtonType();

        $this->jsLayout['components']['wp-wallet-pay']['config']
        ['applepayOptions']['applePayButtonLocale']  =  $this->worldpayHelper->getApplePayButtonLocale();

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
     * @inheritdoc
     */
    private function getCurrentStoreCode()
    {
        return $this->_storeManager->getStore()->getCode();
    }

    /**
     * @inheritdoc
     */
    public function getSessionId()
    {
        return $this->session->getSessionId();
    }
    
    /**
     * Get WP mode
     */
    public function getEnvMode()
    {
        $mode = $this->worldpayHelper->getEnvironmentMode();
        if ($mode == 'Test Mode') {
            return "TEST";
        } else {
            return "PRODUCTION";
        }
    }
    
    /**
     * @inheritdoc
     */
    public function getCustomerData(): array
    {
        $customerData = [];
        if ($this->isCustomerLoggedIn()) {
            $customer = $this->getCustomer();
            $customerData = $customer->__toArray();
           // $customerData['addresses'] = $this->customerAddressData->getAddressDataByCustomer($customer);
        }
        return $customerData;
    }

    /**
     * @inheritdoc
     */
    public function isCustomerLoggedIn()
    {
        return $this->customerSession->isLoggedIn();
    }

    /**
     * @inheritdoc
     */
    public function getCustomer(): CustomerInterface
    {
        return $this->customerRepository->getById($this->customerSession->getCustomerId());
    }

    /**
     * @inheritdoc
     */
    public function getCountriesHtml($defValue = null, $name = 'country_id', $id = 'country', $title = 'Country')
    {
        return $this->getLayout()->createBlock(
            \Magento\Directory\Block\Data::class
        )
            ->getCountryHtmlSelect($defValue, $name, $id, $title);
    }

    /**
     * @inheritdoc
     */
    public function getRegionJson()
    {
        return $this->directoryData->getRegionJson();
    }

    /**
     * Check if Google pay is enabled on PDP or not
     */
    public function isGooglePayEnableonPdp()
    {
        return $this->worldpayHelper->isGooglePayEnableonPdp();
    }

    /**
     * Get google pay button popup text options
     */
    public function getGooglePayPopupPlaceOrderText()
    {
        return $this->worldpayHelper->getGooglePayPopupPlaceOrderText();
    }

    /**
     * Check if Apple pay is enabled on PDP or not
     */
    public function isApplePayEnableonPdp()
    {
        return $this->worldpayHelper->isApplePayEnableonPdp();
    }

    /**
     * Get Apple pay button popup text
     */
    public function getApplePayPopupPlaceOrderText()
    {
        return $this->worldpayHelper->getApplePayPopupPlaceOrderText();
    }
    /**
     *  Check if subscription is enabled
     */
    public function isSubscriptionsEnabled()
    {
        return $this->recurringHelper->getSubscriptionValue('worldpay/subscriptions/active')
                && in_array($this->getProduct()->getTypeId(), $this->recurringHelper->getAllowedProductTypeIds());
    }
    /**
     *  Is wallets enabled
     */
    public function isWalletsEnabled()
    {
        return $this->worldpayHelper->isWalletsEnabled();
    }

    /**
     * @inheritdoc
     */
    public function getSubscriptionPlans()
    {
        return $this->recurringHelper->getProductSubscriptionPlans($this->getProduct());
    }

    /**
     * @inheritdoc
     */
    public function hasPlans()
    {
        return count($this->getSubscriptionPlans());
    }

    /**
     * @inheritdoc
     */
    public function isSubscriptionProduct()
    {
        if (!($this->recurringHelper->getSubscriptionValue('worldpay/subscriptions/active')
            && $this->isSubscriptionsEnabled()
            && $this->getProduct()->getWorldpayRecurringEnabled()
            && $this->hasPlans())
        ) {
            return false;
        }
        return true;
    }
}
