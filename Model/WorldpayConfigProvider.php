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
use Magento\Framework\Locale\Bundle\DataBundle;
use Magento\Framework\Serialize\SerializerInterface;

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
     * Locale model
     *
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $localeResolver;
    
    /**
     * @var SerializerInterface
     */
    private $serializer;
    
    /**
     * @param \Sapient\Worldpay\Logger\WorldpayLogger $wplogger
     * @param \Sapient\Worldpay\Helper\Data $helper
     * @param PaymentHelper $paymentHelper
     * @param WorldPayCCPayment $payment
     * @param Cart $cart
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Backend\Model\Session\Quote $adminquotesession
     * @param SavedTokenFactory $savedTokenFactory
     * @param \Sapient\Worldpay\Model\Utilities\PaymentMethods $paymentmethodutils
     * @param \Magento\Backend\Model\Auth\Session $backendAuthSession
     * @param Repository $assetRepo
     * @param RequestInterface $request
     * @param Source $assetSource
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param SerializerInterface $serializer
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
        Source $assetSource,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        SerializerInterface $serializer
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
            $this->localeResolver = $localeResolver;
            $this->serializer = $serializer;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = [];
        $params = ['_secure' => $this->request->isSecure()];
        foreach ($this->methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['payment']['total'] = $this->cart->getQuote()->getGrandTotal();
                $config['payment']['minimum_amount'] = $this->payment->getMinimumAmount();
                if ($code=='worldpay_cc') {
                    $config['payment']['ccform']["availableTypes"][$code] = $this->getCcTypes();
                } elseif ($code=='worldpay_wallets') {
                    $config['payment']['ccform']["availableTypes"][$code] = $this->getWalletsTypes($code);
                } else {
                    $config['payment']['ccform']["availableTypes"][$code] = $this->getApmTypes($code);
                }
                $config['payment']['ccform']["hasVerification"][$code] = true;
                $config['payment']['ccform']["hasSsCardType"][$code] = false;
                $config['payment']['ccform']["months"][$code] = $this->getMonths();
                $config['payment']['ccform']["years"][$code] = $this->getYears();
                $config['payment']['ccform']["cvvImageUrl"][$code] = $this->assetRepo->
                        getUrlWithParams('Sapient_Worldpay::images/cc/cvv.png', $params);
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
                $config['payment']['ccform']['tokenizationAllowed'] = $this->worldpayHelper->getTokenization();
                $config['payment']['ccform']['storedCredentialsAllowed'] = $this->worldpayHelper->
                        getStoredCredentials();
                $config['payment']['ccform']['disclaimerMessage'] = $this->worldpayHelper->getDisclaimerMessage();
                $config['payment']['ccform']['isDisclaimerMessageEnabled'] = $this->worldpayHelper
                        ->isDisclaimerMessageEnable();
                $config['payment']['ccform']['isDisclaimerMessageMandatory'] = $this->worldpayHelper
                        ->isDisclaimerMessageMandatory();
                $config['payment']['ccform']['apmtitle'] = $this->getApmtitle();
                $config['payment']['ccform']['walletstitle'] = $this->getWalletstitle();
                $config['payment']['ccform']['samsungServiceId'] = $this->getSamsungServiceId();
                $config['payment']['ccform']['paymentMethodSelection'] = $this->getPaymentMethodSelection();
                $config['payment']['ccform']['paymentTypeCountries'] = $this->
                        paymentmethodutils->getPaymentTypeCountries();
                $config['payment']['ccform']['savedCardCount'] = count($this->getSaveCardList());
                $config['payment']['ccform']['apmIdealBanks'] = $this->getApmIdealBankList();
                $config['payment']['ccform']['wpicons'] = $this->getIcons();

                $config['payment']['ccform']['isGooglePayEnable'] = $this->worldpayHelper->isGooglePayEnable();
                $config['payment']['ccform']['googlePaymentMethods'] = $this->worldpayHelper->googlePaymentMethods();
                $config['payment']['ccform']['googleAuthMethods'] = $this->worldpayHelper->googleAuthMethods();
                $config['payment']['ccform']['googleGatewayMerchantname'] = $this->worldpayHelper->
                        googleGatewayMerchantname();
                $config['payment']['ccform']['googleGatewayMerchantid'] = $this->worldpayHelper->
                        googleGatewayMerchantid();
                $config['payment']['ccform']['googleMerchantname'] = $this->worldpayHelper->googleMerchantname();
                $config['payment']['ccform']['googleMerchantid'] = $this->worldpayHelper->googleMerchantid();
                $config['payment']['ccform']['appleMerchantid'] = $this->worldpayHelper->appleMerchantId();
                
                if ($this->worldpayHelper->getEnvironmentMode()=='Live Mode') {
                    $config['payment']['general']['environmentMode'] = "PRODUCTION";
                } else {
                    $config['payment']['general']['environmentMode'] = "TEST";
                }
                
                // 3DS2 Configurations
                $config['payment']['ccform']['isDynamic3DS2Enabled'] = $this->worldpayHelper->
                        isDynamic3DS2Enabled();
                $config['payment']['ccform']['jwtEventUrl'] = $this->worldpayHelper->getJwtEventUrl();
                $config['payment']['ccform']['isJwtApiKey'] = $this->worldpayHelper->isJwtApiKey();
                $config['payment']['ccform']['isJwtIssuer'] = $this->worldpayHelper->isJwtIssuer();
                $config['payment']['ccform']['isOrganisationalUnitId'] = $this->worldpayHelper->
                        isOrganisationalUnitId();
                $config['payment']['ccform']['isTestDdcUrl'] = $this->worldpayHelper->isTestDdcUrl();
                $config['payment']['ccform']['isProductionDdcUrl'] = $this->worldpayHelper->isProductionDdcUrl();
                $config['payment']['ccform']['isRiskData'] = $this->worldpayHelper->isRiskData();
                $config['payment']['ccform']['isAuthenticationMethod'] = $this->worldpayHelper->
                        isAuthenticationMethod();
                $config['payment']['ccform']['isTestChallengeUrl'] = $this->worldpayHelper->isTestChallengeUrl();
                $config['payment']['ccform']['isProductionChallengeUrl'] = $this->worldpayHelper->
                        isProductionChallengeUrl();
                $config['payment']['ccform']['isChallengePreference'] = $this->worldpayHelper->
                        isChallengePreference();
                $config['payment']['ccform']['isChallengeWindowSize'] = $this->worldpayHelper->
                        getChallengeWindowSize();
                
                // Subscription Status
                $config['payment']['ccform']['isSubscribed'] = $this->worldpayHelper->getsubscriptionStatus();
                
                $config['payment']['ccform']['myaccountexceptions'] = $this->getMyAccountException();

                $config['payment']['ccform']['creditcardexceptions'] = $this->getCreditCardException();
                $config['payment']['ccform']['generalexceptions'] = $this->getGeneralException();
                //Latin America Payments
                $config['payment']['ccform']['isCPFEnabled'] = $this->worldpayHelper->isCPFEnabled();
                $config['payment']['ccform']['isInstalmentEnabled'] = $this->worldpayHelper->isInstalmentEnabled();
                $config['payment']['ccform']['latAmCountries'] = $this->worldpayHelper->getConfigCountries();
                //ACH Pay
                $config['payment']['ccform']['achdetails'] = $this->worldpayHelper->getACHDetails();
                //Prime Routing
                $config['payment']['ccform']['isPrimeRoutingEnabled'] = $this->worldpayHelper->isPrimeRoutingEnabled();
                
                // Custom label configuration
                $config['payment']['ccform']['myaccountlabels'] = $this->getMyAccountLabels();
                $config['payment']['ccform']['checkoutlabels'] = $this->getCheckoutLabels();
                $config['payment']['ccform']['adminlabels'] = $this->getAdminLabels();
            }
        }
        return $config;
    }

    /**
     * Get Saved card List of customer
     */
    public function getSaveCardList()
    {
        $savedCardsList = [];
        $isSavedCardEnabled = $this->getIsSaveCardAllowed();
        $tokenType = $this->worldpayHelper->getMerchantTokenization() ? 'merchant' : 'shopper';
        if ($isSavedCardEnabled && ($this->customerSession->isLoggedIn() || $this->backendAuthSession->isLoggedIn())) {
            $savedCardsList = $this->savedTokenFactory->create()->getCollection()
            ->addFieldToFilter('customer_id', $this->customerSession->getCustomerId())
            ->addFieldToFilter('token_type', $tokenType)->getData();
        }
        return $savedCardsList;
    }

    /**
     * IsSaveCardAllowed
     *
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
     * Get the integration mode selected
     *
     * @return String
     */
    public function getIntigrationMode()
    {
        return $this->worldpayHelper->getCcIntegrationMode();
    }

    /**
     * Get cc types
     *
     * @param string $paymentconfig
     * @return Array
     */
    public function getCcTypes($paymentconfig = "cc_config")
    {
        $options = $this->worldpayHelper->getCcTypes($paymentconfig);
        $isSavedCardEnabled = $this->getIsSaveCardAllowed();
        if ($isSavedCardEnabled && (!empty($this->getSaveCardList()) || !empty($this->
                getSaveCardListForAdminOrder($this->adminquotesession->getCustomerId())))) {
             $options['savedcard'] = $this->worldpayHelper->getCheckoutLabelbyCode('CO13');
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

    /**
     * Retrieve list of months translation
     *
     * @return array
     * @api
     */
    public function getMonths()
    {
        $data = [];
        $months = (new DataBundle())->get(
            $this->localeResolver->getLocale()
        )['calendar']['gregorian']['monthNames']['format']['wide'];
        foreach ($months as $key => $value) {
            $monthNum = ++$key < 10 ? '0' . $key : $key;
            $data[$key] = $monthNum . ' - ' . $value;
        }
        return $data;
    }

    /**
     * Years
     *
     * @return Array
     */
    public function getYears()
    {
        $years = [];
        for ($i=0; $i<=10; $i++) {
            $year = (string)($i+date('Y'));
            $years[$year] = $year;
        }
        return $years;
    }

    /**
     * Get start years
     *
     * @return Array
     */
    public function getStartYears()
    {
        $years = [];
        for ($i=5; $i>=0; $i--) {
            $year = (string)(date('Y')-$i);
            $years[$year] = $year;
        }
        return $years;
    }

    /**
     * Get cc title
     *
     * @return String
     */
    public function getCCtitle()
    {
        return $this->worldpayHelper->getCcTitle();
    }

    /**
     * Get apm title
     *
     * @return String
     */
    public function getApmtitle()
    {
        return $this->worldpayHelper->getApmTitle();
    }
    
    /**
     * Get wallet title
     *
     * @return String
     */
    public function getWalletstitle()
    {
        return $this->worldpayHelper->getWalletstitle();
    }
    
    /**
     * Get samsung service id
     *
     * @return String
     */
    public function getSamsungServiceId()
    {
        return $this->worldpayHelper->getSamsungServiceId();
    }

    /**
     * Check if cvc is enabled
     *
     * @return boolean
     */
    public function getCvcRequired()
    {
        return $this->worldpayHelper->isCcRequireCVC();
    }

    /**
     * Payment method selected
     *
     * @return string
     */
    public function getPaymentMethodSelection()
    {
        return $this->worldpayHelper->getPaymentMethodSelection();
    }

    /**
     * Saved card lists for a customer
     *
     * @param int $customer
     * @return string
     */
    public function getSaveCardListForAdminOrder($customer)
    {
        $savedCardsList = [];
        $tokenType = $this->worldpayHelper->getMerchantTokenization() ? 'merchant' : 'shopper';
        if ($this->customerSession->isLoggedIn() || $this->backendAuthSession->isLoggedIn()) {
            $savedCardsList = $this->savedTokenFactory->create()->getCollection()
            ->addFieldToFilter('customer_id', $customer)
            ->addFieldToFilter('token_type', $tokenType)->getData();
        }
        return $savedCardsList;
    }
   
    /**
     * List of ideal banks
     *
     * @return array
     */
    public function getApmIdealBankList()
    {
        $apmPaymentTypes = $this->getApmTypes('worldpay_apm');
        if (array_key_exists("IDEAL-SSL", $apmPaymentTypes)) {
            $data = $this->paymentmethodutils->idealBanks();
            return $data;
        }
        return [];
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
        
        $allTypePayments = array_unique(array_merge($types, $apmTypes));
        
        $allTypePayments = array_unique(array_merge($allTypePayments, $walletsTypes));
        
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
    
    /**
     * Credit card exceptions
     *
     * @return array
     */
    public function getCreditCardException()
    {
        $ccdata= $this->unserializeValue($this->worldpayHelper->getCreditCardException());
        $result=[];
        $data=[];
        if (is_array($ccdata) || is_object($ccdata)) {
            foreach ($ccdata as $key => $value) {

                $result['exception_code']=$key;
                $result['exception_messages'] = $value['exception_messages'];
                $result['exception_module_messages'] = $value['exception_module_messages'];
                array_push($data, $result);
            
            }
        }
        return $data;
    }
    
    /**
     * General exceptions
     *
     * @return array
     */
    public function getGeneralException()
    {
        $generaldata=$this->unserializeValue($this->worldpayHelper->getGeneralException());
        $result=[];
        $data=[];
        if (is_array($generaldata) || is_object($generaldata)) {
            foreach ($generaldata as $key => $value) {

                $result['exception_code']=$key;
                $result['exception_messages'] = $value['exception_messages'];
                $result['exception_module_messages'] = $value['exception_module_messages'];
                array_push($data, $result);
            
            }
        }
        return $data;
    }
    
     /**
      * Check if cpf is enabled
      *
      * @return boolean
      */
    public function cpfEnabled()
    {
        if ($this->worldpayHelper->isCPFEnabled()) {
            return true;
        }
        return false;
    }
    
     /**
      * Check if instalments is enabled
      *
      * @return boolean
      */
    public function instalmentEnabled()
    {
        if ($this->worldpayHelper->isInstalmentEnabled()) {
            return true;
        }
        return false;
    }
    
    /**
     * Get My account exceptions
     *
     * @return array
     */
    public function getMyAccountException()
    {
        $generaldata=$this->unserializeValue($this->worldpayHelper->getMyAccountException());
        $result=[];
        $data=[];
        if (is_array($generaldata) || is_object($generaldata)) {
            foreach ($generaldata as $key => $value) {

                $result['exception_code']=$key;
                $result['exception_messages'] = $value['exception_messages'];
                $result['exception_module_messages'] = $value['exception_module_messages'];
                array_push($data, $result);
            
            }
        }
        return $data;
    }
    
    public function getInstalmentValues($countryId)
    {
        return $this->worldpayHelper->getInstalmentValues($countryId);
    }
    
    /**
     * Unserialize value
     *
     * @param string $value
     * @return array
     */
    protected function unserializeValue($value)
    {
        if (is_string($value) && !empty($value)) {
            return $this->serializer->unserialize($value);
        } else {
            return [];
        }
    }
    
    /**
     * Saved cards for a customer
     *
     * @return array
     */
    public function getSaveCardListForMyAccount()
    {
        $savedCardsList = [];
        $tokenType = $this->worldpayHelper->getMerchantTokenization() ? 'merchant' : 'shopper';
        if ($this->customerSession->isLoggedIn() || $this->backendAuthSession->isLoggedIn()) {
            $savedCardsList = $this->savedTokenFactory->create()->getCollection()
            ->addFieldToFilter('customer_id', $this->customerSession->getCustomerId())
            ->addFieldToFilter('token_type', $tokenType)->getData();
        }
        return $savedCardsList;
    }
    
    /**
     * Method to get my account labels
     *
     * @return array
     */
    public function getMyAccountLabels()
    {
        $generaldata=$this->unserializeValue($this->worldpayHelper->getMyAccountLabels());
        $result=[];
        $data=[];
        if (is_array($generaldata) || is_object($generaldata)) {
            foreach ($generaldata as $key => $value) {

                $result['wpay_label_code']=$key;
                $result['wpay_label_desc'] = $value['wpay_label_desc'];
                $result['wpay_custom_label'] = $value['wpay_custom_label'];
                array_push($data, $result);
            
            }
        }
        return $data;
    }
    
    /**
     * Method to get checkout labels
     *
     * @return array
     */
    public function getCheckoutLabels()
    {
        $generaldata=$this->unserializeValue($this->worldpayHelper->getCheckoutLabels());
        $result=[];
        $data=[];
        if (is_array($generaldata) || is_object($generaldata)) {
            foreach ($generaldata as $key => $value) {

                $result['wpay_label_code']=$key;
                $result['wpay_label_desc'] = $value['wpay_label_desc'];
                $result['wpay_custom_label'] = $value['wpay_custom_label'];
                array_push($data, $result);
            
            }
        }
        return $data;
    }
    
    /**
     * Method to get admin labels
     *
     * @return array
     */
    public function getAdminLabels()
    {
        $generaldata=$this->unserializeValue($this->worldpayHelper->getAdminLabels());
        $result=[];
        $data=[];
        if (is_array($generaldata) || is_object($generaldata)) {
            foreach ($generaldata as $key => $value) {

                $result['wpay_label_code']=$key;
                $result['wpay_label_desc'] = $value['wpay_label_desc'];
                $result['wpay_custom_label'] = $value['wpay_custom_label'];
                array_push($data, $result);
            
            }
        }
        return $data;
    }
}
