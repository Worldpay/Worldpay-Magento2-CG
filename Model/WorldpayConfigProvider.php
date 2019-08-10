<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Sapient\Worldpay\Model\PaymentMethods\CreditCards as WorldPayCCPayment;
use Magento\Checkout\Model\Cart;
use Sapient\Worldpay\Model\SavedTokenFactory;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Asset\Source;

/**
 * Configuration provider for worldpayment rendering payment page.
 */
class WorldpayConfigProvider implements ConfigProviderInterface
{
    const CC_VAULT_CODE = "worldpay_cc_vault";
    /**
     * @var string[]
     */
    protected $methodCodes = [
        'worldpay_cc',
        'worldpay_apm',
        'worldpay_wallets'
    ];

    /**
     * @var array
     */
    private $icons = [];

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    protected $methods = [];
    /**
     * @var \Sapient\Worldpay\Model\PaymentMethods\Creditcards
     */
    protected $payment ;
    /**
     * @var \Sapient\Worldpay\Helper\Data
     */
    protected $worldpayHelper;
    /**
     * @var Magento\Checkout\Model\Cart
     */
    protected $cart;
    /**
     * @var \Sapient\Worldpay\Logger\WorldpayLogger
     */
    protected $wplogger;

    /**
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Helper\Data $helper
     * @param PaymentHelper $paymentHelper
     * @param WorldPayCCPayment $payment
     * @param Cart $cart
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Backend\Model\Session\Quote $adminquotesession
     * @param SavedTokenFactory $savedTokenFactory
     * @param \Magento\Backend\Model\Auth\Session $backendAuthSession
     */
    public function __construct(
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Helper\Data $helper,
        PaymentHelper $paymentHelper,
        WorldPayCCPayment $payment,
        Cart $cart,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Backend\Model\Session\Quote $adminquotesession,
        SavedTokenFactory $savedTokenFactory,
        \Sapient\Worldpay\Model\Utilities\PaymentMethods $paymentmethodutils,
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        Repository $assetRepo,
        RequestInterface $request,
        Source $assetSource
        ) {

            $this->wplogger = $wplogger;
            foreach ($this->methodCodes as $code) {
                $this->methods[$code] = $paymentHelper->getMethodInstance($code);
            }
            $this->cart = $cart;
            $this->payment = $payment;
            $this->worldpayHelper = $helper;
            $this->customerSession = $customerSession;
            $this->backendAuthSession = $backendAuthSession;
            $this->adminquotesession = $adminquotesession;
            $this->paymentmethodutils = $paymentmethodutils;
            $this->savedTokenFactory = $savedTokenFactory;
            $this->assetRepo = $assetRepo;
            $this->request = $request;
            $this->assetSource = $assetSource;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = [];
        $params = array('_secure' => $this->request->isSecure());
        foreach ($this->methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['payment']['total'] = $this->cart->getQuote()->getGrandTotal();
                $config['payment']['minimum_amount'] = $this->payment->getMinimumAmount();
                if ($code=='worldpay_cc') {
                    $config['payment']['ccform']["availableTypes"][$code] = $this->getCcTypes();
                } else if ($code=='worldpay_wallets') {
                    $config['payment']['ccform']["availableTypes"][$code] = $this->getWalletsTypes($code);
                } 
                else {
                    $config['payment']['ccform']["availableTypes"][$code] = $this->getApmTypes($code);
                }
                $config['payment']['ccform']["hasVerification"][$code] = true;
                $config['payment']['ccform']["hasSsCardType"][$code] = false;
                $config['payment']['ccform']["months"][$code] = $this->getMonths();
                $config['payment']['ccform']["years"][$code] = $this->getYears();
                $config['payment']['ccform']["cvvImageUrl"][$code] = $this->assetRepo->getUrlWithParams('Sapient_Worldpay::images/cc/cvv.png', $params);
                $config['payment']['ccform']["ssStartYears"][$code] = $this->getStartYears();
                $config['payment']['ccform']['intigrationmode'] = $this->getIntigrationMode();
                $config['payment']['ccform']['cctitle'] = $this->getCCtitle();
                $config['payment']['ccform']['isCvcRequired'] = $this->getCvcRequired();
                $config['payment']['ccform']['cseEnabled'] = $this->worldpayHelper->isCseEnabled();

                if ($config['payment']['ccform']['cseEnabled']) {
                    $config['payment']['ccform']['csePublicKey'] = $this->worldpayHelper->getCsePublicKey();
                }

                $config['payment']['ccform']['is3DSecureEnabled'] = $this->worldpayHelper->is3DSecureEnabled();
                $config['payment']['ccform']['savedCardList'] = $this->getSaveCardList();
                $config['payment']['ccform']['saveCardAllowed'] = $this->worldpayHelper->getSaveCard();
                $config['payment']['ccform']['apmtitle'] = $this->getApmtitle();
                $config['payment']['ccform']['walletstitle'] = $this->getWalletstitle();
                $config['payment']['ccform']['paymentMethodSelection'] = $this->getPaymentMethodSelection();
                $config['payment']['ccform']['paymentTypeCountries'] = $this->paymentmethodutils->getPaymentTypeCountries();
                $config['payment']['ccform']['savedCardCount'] = count($this->getSaveCardList());
                $config['payment']['ccform']['apmIdealBanks'] = $this->getApmIdealBankList();
                $config['payment']['ccform']['wpicons'] = $this->getIcons();

                $config['payment']['ccform']['isGooglePayEnable'] = $this->worldpayHelper->isGooglePayEnable();
                $config['payment']['ccform']['googlePaymentMethods'] = $this->worldpayHelper->googlePaymentMethods();
                $config['payment']['ccform']['googleAuthMethods'] = $this->worldpayHelper->googleAuthMethods();
                $config['payment']['ccform']['googleGatewayMerchantname'] = $this->worldpayHelper->googleGatewayMerchantname();
                $config['payment']['ccform']['googleGatewayMerchantid'] = $this->worldpayHelper->googleGatewayMerchantid();
                $config['payment']['ccform']['googleMerchantname'] = $this->worldpayHelper->googleMerchantname();
                $config['payment']['ccform']['googleMerchantid'] = $this->worldpayHelper->googleMerchantid();
                if ($this->worldpayHelper->getEnvironmentMode()=='Live Mode'){
                   $config['payment']['general']['environmentMode'] = "PRODUCTION";
                }else{
                    $config['payment']['general']['environmentMode'] = "TEST";
                }
                
                // 3DS2 Configurations
                $config['payment']['ccform']['isDynamic3DS2Enabled'] = $this->worldpayHelper->isDynamic3DS2Enabled();
                $config['payment']['ccform']['isJwtIssuer'] = $this->worldpayHelper->isJwtIssuer();
                $config['payment']['ccform']['isOrganisationalUnitId'] = $this->worldpayHelper->isOrganisationalUnitId();
                $config['payment']['ccform']['isTestDdcUrl'] = $this->worldpayHelper->isTestDdcUrl();
                $config['payment']['ccform']['isProductionDdcUrl'] = $this->worldpayHelper->isProductionDdcUrl();
                $config['payment']['ccform']['isRiskData'] = $this->worldpayHelper->isRiskData();
                $config['payment']['ccform']['isAuthenticationMethod'] = $this->worldpayHelper->isAuthenticationMethod();
                $config['payment']['ccform']['isTestChallengeUrl'] = $this->worldpayHelper->isTestChallengeUrl();
                $config['payment']['ccform']['isProductionChallengeUrl'] = $this->worldpayHelper->isProductionChallengeUrl();
                $config['payment']['ccform']['isChallengePreference'] = $this->worldpayHelper->isChallengePreference();
                $config['payment']['ccform']['isChallengeWindowSize'] = $this->worldpayHelper->isChallengeWindowSize();
            }
        }
        return $config;
    }

    /**
     * Get Saved card List of customer
     */
    public function getSaveCardList()
    {
        $savedCardsList = array();
        if ($this->customerSession->isLoggedIn() || $this->backendAuthSession->isLoggedIn()) {
            $savedCardsList = $this->savedTokenFactory->create()->getCollection()
           ->addFieldToFilter('customer_id', $this->customerSession->getCustomerId())->getData();
        }
        return $savedCardsList;
    }

    /**
     * @return boolean
     */
    public function getIsSaveCardAllowed()
    {
        if ($this->worldpayHelper->getSaveCard()) {
            return true;
        }
        return false;
    }

    /**
     * @return String
     */
    public function getIntigrationMode()
    {
        return $this->worldpayHelper->getCcIntegrationMode();
    }

    /**
     * @return Array
     */
    public function getCcTypes($paymentconfig = "cc_config")
    {
        $options = $this->worldpayHelper->getCcTypes($paymentconfig);
        if (!empty($this->getSaveCardList()) || !empty($this->getSaveCardListForAdminOrder($this->adminquotesession->getCustomerId()))) {
             $options['savedcard'] = 'Use Saved Card';
        }
        return $options;
     }

    /**
     * @return Array
     */
    public function getApmTypes($code)
    {
        return $this->worldpayHelper->getApmTypes($code);
    }
    
    /**
     * @return Array
     */
    public function getWalletsTypes($code)
    {
        return $this->worldpayHelper->getWalletsTypes($code);
    }

    public function getMonths()
    {
        return array(
            "01" => "01 - January",
            "02" => "02 - February",
            "03" => "03 - March",
            "04" => "04 - April",
            "05" => "05 - May",
            "06" => "06 - June",
            "07" => "07 - July",
            "08" => "08 - August",
            "09" => "09 - September",
            "10"=> "10 - October",
            "11"=> "11 - November",
            "12"=> "12 - December"
        );
    }

    /**
     * @return Array
     */
    public function getYears()
    {
        $years = array();
        for ($i=0; $i<=10; $i++) {
            $year = (string)($i+date('Y'));
            $years[$year] = $year;
        }
        return $years;
    }

    /**
     * @return Array
     */
    public function getStartYears()
    {
        $years = array();
        for ($i=5; $i>=0; $i--) {
            $year = (string)(date('Y')-$i);
            $years[$year] = $year;
        }
        return $years;
    }

    /**
     * @return String
     */
    public function getCCtitle()
    {
        return $this->worldpayHelper->getCcTitle();
    }

    /**
     * @return String
     */
    public function getApmtitle()
    {
        return $this->worldpayHelper->getApmTitle();
    }
    
    /**
     * @return String
     */
    public function getWalletstitle()
    {
        return $this->worldpayHelper->getWalletstitle();
    }

    /**
     * @return boolean
     */
    public function getCvcRequired()
    {
        return $this->worldpayHelper->isCcRequireCVC();
    }

    /**
     * @return string
     */
    public function getPaymentMethodSelection()
    {
        return $this->worldpayHelper->getPaymentMethodSelection();
    }

    /**
     * @return string
     */
    public function getSaveCardListForAdminOrder($customer)
    {
        $savedCardsList = array();
        if ($this->customerSession->isLoggedIn() || $this->backendAuthSession->isLoggedIn()) {
            $savedCardsList = $this->savedTokenFactory->create()->getCollection()
           ->addFieldToFilter('customer_id', $customer)->getData();
        }
        return $savedCardsList;
    }
    public function getApmIdealBankList(){
        $apmPaymentTypes = $this->getApmTypes('worldpay_apm');
        if (array_key_exists("IDEAL-SSL",$apmPaymentTypes)) {
            $data = $this->paymentmethodutils->idealBanks();
            return $data;
        }
        return array();
    }

    public function getIcons()
    {
        if (!empty($this->icons)) {
            return $this->icons;
        }

        $types = $this->worldpayHelper->getCcTypes();
        $types['VISA_DEBIT-SSL'] = 'Visa debit';
        $apmTypes = $this->worldpayHelper->getApmTypes('worldpay_apm');
        $walletsTypes = $this->worldpayHelper->getWalletsTypes('worldpay_wallets');
        $allTypePayments = array_unique (array_merge ($types, $apmTypes));
        foreach (array_keys($allTypePayments) as $code) {
            if (!array_key_exists($code, $this->icons)) {
                $asset = $this->createAsset('Sapient_Worldpay::images/cc/' . strtolower($code) . '.png');
                $placeholder = $this->assetSource->findSource($asset);
                if ($placeholder) {
                    list($width, $height) = getimagesize($asset->getSourceFile());
                    $this->icons[$code] = [
                        'url' => $asset->getUrl(),
                        'width' => $width,
                        'height' => $height
                    ];
                }
            }
        }
        return $this->icons;
    }
    /**
     * Create a file asset that's subject of fallback system
     *
     * @param string $fileId
     * @param array $params
     * @return \Magento\Framework\View\Asset\File
     */
    public function createAsset($fileId, array $params = [])
    {
        $params = array_merge(['_secure' => $this->request->isSecure()], $params);
        return $this->assetRepo->createAsset($fileId, $params);
    }

}
