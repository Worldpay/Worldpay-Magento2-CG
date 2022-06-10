<?php

/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Test\Unit\Helper;

use Sapient\Worldpay\Logger\WorldpayLogger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Locale\CurrencyInterface;
use Sapient\Worldpay\Model\Utilities\PaymentMethods;
use Sapient\Worldpay\Helper\Merchantprofile;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\OrderFactory;
use Sapient\Worldpay\Helper\Recurring;
use Sapient\Worldpay\Helper\ExtendedResponseCodes;
use Sapient\Worldpay\Helper\Instalmentconfig;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Sapient\Worldpay\Model\SavedTokenFactory;
use Sapient\Worldpay\Helper\Currencyexponents;
use Magento\Framework\Serialize\SerializerInterface;
use \PHPUnit\Framework\TestCase;
use Sapient\Worldpay\Helper\Data;
use Magento\Quote\Model\Quote;

class DataTest extends TestCase
{

/**
 * [$scopeConfigMock description]
 * @var [type]
 */
    protected $scopeConfigMock;
/**
 * [$wploggerMock description]
 * @var [type]
 */
    protected $wploggerMock;
 /**
  * [$dataObj description]
  * @var [type]
  */
    protected $dataObj;
/**
 * [$serializer description]
 * @var [type]
 */
    protected $serializer;
/**
 * [$paymentlist description]
 * @var [type]
 */
    protected $paymentlist;
/**
 * [$recurringHelper description]
 * @var [type]
 */
    protected $recurringHelper;
 /**
  * [$checkoutSession description]
  * @var [type]
  */
    protected $checkoutSession;
/**
 * [$quote description]
 * @var [type]
 */
    protected $quote;
/**
 * [$APM_TYPES description]
 * @var string
 */
    protected $APM_TYPES = 'CHINAUNIONPAY-SSL,IDEAL-SSL,QIWI-SSL,PAYPAL-EXPRESS,'
            . 'SOFORT-SSL,GIROPAY-SSL,ALIPAY-SSL,SEPA_DIRECT_DEBIT-SSL,'
            . 'KLARNA-SSL,PRZELEWY-SSL,MISTERCASH-SSL,ACH_DIRECT_DEBIT-SSL';
/**
 * [$ACH_BANK_ACC_TYPES description]
 * @var string
 */
    protected $ACH_BANK_ACC_TYPES = 'Checking,Savings,Corporate,Corp Savings';
/**
 * [$MOTO_TYPES description]
 * @var string
 */
    protected $MOTO_TYPES = 'AMEX-SSL,VISA-SSL,ECMC-SSL,DISCOVER-SSL,DINERS-SSL,'
            . 'MAESTRO-SSL,AIRPLUS-SSL,AURORE-SSL,CB-SSL,CARTEBLEUE-SSL,'
            . 'DANKORT-SSL,GECAPITAL-SSL,JCB-SSL,LASER-SSL,UATP-SSL';
 /**
  * [$ALL_APM_METHODS description]
  * @var [type]
  */
    protected $ALL_APM_METHODS = [
            'CHINAUNIONPAY-SSL' => 'Union Pay',
            'IDEAL-SSL' => 'IDEAL',
            'QIWI-SSL' => 'Qiwi',
//            'YANDEXMONEY-SSL' => 'Yandex.Money',
            'PAYPAL-EXPRESS' => 'PayPal',
            'SOFORT-SSL' => 'SoFort EU',
            'GIROPAY-SSL' => 'GiroPay',
//            'BOLETO-SSL' => 'Boleto Bancairo',
            'ALIPAY-SSL' => 'AliPay',
            'SEPA_DIRECT_DEBIT-SSL' => 'SEPA (One off transactions)',
            'KLARNA-SSL' => 'Klarna',
            'PRZELEWY-SSL' => 'P24',
            'MISTERCASH-SSL' => 'Mistercash/Bancontact',
            'ACH_DIRECT_DEBIT-SSL' => 'ACH Pay'
        ];
/**
 * [$ALL_MOTO_METHODS description]
 * @var [type]
 */
    protected $ALL_MOTO_METHODS = [
                'AMEX-SSL' => 'American Express',
                'VISA-SSL' => 'Visa',
                'ECMC-SSL' => 'MasterCard',
                'DISCOVER-SSL' => 'Discover',
                'DINERS-SSL' => 'Diners',
                'MAESTRO-SSL' => 'Maestro',
                'AIRPLUS-SSL' => 'AirPlus',
                'AURORE-SSL' => 'Aurore',
                'CB-SSL' => 'Carte Bancaire',
                'CARTEBLEUE-SSL' => 'Carte Bleue',
                'DANKORT-SSL' => 'Dankort',
                'GECAPITAL-SSL' => 'GE Capital',
                'JCB-SSL' => 'Japanese Credit Bank',
                'LASER-SSL' => 'Laser Card',
                'UATP-SSL' => 'UATP'
        ];
/**
 * [$GOOGLE_PAYMENT_METHODS description]
 * @var string
 */
    protected $GOOGLE_PAYMENT_METHODS = 'AMEX,VISA,DISCOVER,JCB,MASTERCARD';
/**
 * [$GOOGLE_AUTH_METHODS description]
 * @var string
 */
    protected $GOOGLE_AUTH_METHODS = 'PAN_ONLY,CRYPTOGRAM_3DS';
/**
 * [$CC_TYPES description]
 * @var string
 */
    protected $CC_TYPES = 'AMEX-SSL,VISA-SSL,ECMC-SSL,DISCOVER-SSL,DINERS-SSL,'
            . 'MAESTRO-SSL,AIRPLUS-SSL,AURORE-SSL,CB-SSL,CARTEBLEUE-SSL,'
            . 'DANKORT-SSL,GECAPITAL-SSL,JCB-SSL,LASER-SSL,UATP-SSL';
    
/**
 * [$ALL_CC_METHODS description]
 * @var [type]
 */
    protected $ALL_CC_METHODS = [
                'AMEX-SSL' => 'American Express',
                'VISA-SSL' => 'Visa',
                'ECMC-SSL' => 'MasterCard',
                'DISCOVER-SSL' => 'Discover',
                'DINERS-SSL' => 'Diners',
                'MAESTRO-SSL' => 'Maestro',
                'AIRPLUS-SSL' => 'AirPlus',
                'AURORE-SSL' => 'Aurore',
                'CB-SSL' => 'Carte Bancaire',
                'CARTEBLEUE-SSL' => 'Carte Bleue',
                'DANKORT-SSL' => 'Dankort',
                'GECAPITAL-SSL' => 'GE Capital',
                'JCB-SSL' => 'Japanese Credit Bank',
                'LASER-SSL' => 'Laser Card',
                'UATP-SSL' => 'UATP'
        ];
/**
 * [setUp description]
 */
    protected function setUp()
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
                        ->disableOriginalConstructor()->getMock();
        $this->wploggerMock = $this->getMockBuilder(WorldpayLogger::class)
                        ->disableOriginalConstructor()->getMock();
        $localeCurrency = $this->getMockBuilder(CurrencyInterface::class)
                        ->disableOriginalConstructor()->getMock();
        $this->paymentlist = $this->getMockBuilder(PaymentMethods::class)
                        ->disableOriginalConstructor()->setMethods(['checkCurrency'])->getMock();
        $merchantprofile = $this->getMockBuilder(Merchantprofile::class)
                        ->disableOriginalConstructor()->getMock();
        $this->checkoutSession = $this->getMockBuilder(Session::class)
                        ->disableOriginalConstructor()->setMethods(['getQuote'])->getMock();
        $orderFactory = $this->getMockBuilder(OrderFactory::class)
                        ->disableOriginalConstructor()->getMock();
        $this->recurringHelper = $this->getMockBuilder(Recurring::class)
                        ->disableOriginalConstructor()->getMock();
        $extendedResponseCodes = $this->getMockBuilder(ExtendedResponseCodes::class)
                        ->disableOriginalConstructor()->getMock();
        $instalmentconfig = $this->getMockBuilder(Instalmentconfig::class)
                        ->disableOriginalConstructor()->getMock();
        $orderCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
                        ->disableOriginalConstructor()->getMock();
        $savecard = $this->getMockBuilder(SavedTokenFactory::class)
                        ->disableOriginalConstructor()->getMock();
        $currencyexponents = $this->getMockBuilder(Currencyexponents::class)
                        ->disableOriginalConstructor()->getMock();
        $this->serializer = $this->getMockBuilder(SerializerInterface::class)
                        ->disableOriginalConstructor()
                        ->setMethods(['serialize', 'unserialize'])
                        ->getMock();
        
        $this->dataObj = new Data(
            $this->wploggerMock,
            $this->scopeConfigMock,
            $localeCurrency,
            $this->paymentlist,
            $merchantprofile,
            $this->checkoutSession,
            $orderFactory,
            $this->recurringHelper,
            $extendedResponseCodes,
            $instalmentconfig,
            $orderCollectionFactory,
            $savecard,
            $currencyexponents,
            $this->serializer
        );
    }
/**
 * [testIsWorldPayEnable description]
 * @return [type] [description]
 */
    public function testIsWorldPayEnable()
    {
        $this->scopeConfigMock->expects($this->once())
                ->method('getValue')
                ->with(
                    'worldpay/general_config/enable_worldpay',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
                ->willReturn(true);
        $this->assertEquals(true, $this->dataObj->isWorldPayEnable());
    }
/**
 * [testIsApmEnabled description]
 * @return [type] [description]
 */
    public function testIsApmEnabled()
    {
        $this->scopeConfigMock->expects($this->once())
                ->method('getValue')
                ->with('worldpay/apm_config/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                ->willReturn(true);
        $this->assertEquals(true, $this->dataObj->isApmEnabled());
    }
/**
 * [testGetApmTitle description]
 * @return [type] [description]
 */
    public function testGetApmTitle()
    {
        $this->scopeConfigMock->expects($this->once())
                ->method('getValue')
                ->with('worldpay/apm_config/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                ->willReturn('Alternative Payment Methods');
        $this->assertEquals('Alternative Payment Methods', $this->dataObj->getApmTitle());
    }
/**
 * [testGetApmPaymentMethods description]
 * @return [type] [description]
 */
    public function testGetApmPaymentMethods()
    {
        $this->scopeConfigMock->expects($this->once())
                ->method('getValue')
                ->with('worldpay/apm_config/paymentmethods', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                ->willReturn($this->APM_TYPES);
        $this->assertEquals($this->APM_TYPES, $this->dataObj->getApmPaymentMethods());
    }
/**
 * [testGetApmTypes description]
 * @return [type] [description]
 */
    public function testGetApmTypes()
    {
        $this->scopeConfigMock->expects($this->any())
                 ->method('getValue')
                 ->with('worldpay/apm_config/paymentmethods', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                 ->willReturn($this->APM_TYPES);
         $this->paymentlist->expects($this->any())
                 ->method('checkCurrency')
                 ->withConsecutive(
                     ['worldpay_apm','CHINAUNIONPAY-SSL'],
                     ['worldpay_apm','IDEAL-SSL'],
                     ['worldpay_apm','QIWI-SSL'],
                     ['worldpay_apm','PAYPAL-EXPRESS'],
                     ['worldpay_apm','SOFORT-SSL'],
                     ['worldpay_apm','GIROPAY-SSL'],
                     ['worldpay_apm','ALIPAY-SSL'],
                     ['worldpay_apm','SEPA_DIRECT_DEBIT-SSL'],
                     ['worldpay_apm','KLARNA-SSL'],
                     ['worldpay_apm','PRZELEWY-SSL'],
                     ['worldpay_apm','MISTERCASH-SSL'],
                     ['worldpay_apm','ACH_DIRECT_DEBIT-SSL']
                 )
                 ->willReturn(true);
         $this->assertEquals($this->ALL_APM_METHODS, $this->dataObj->getApmTypes('worldpay_apm'));
    }
/**
 * [testGetACHDetails description]
 * @return [type] [description]
 */
    public function testGetACHDetails()
    {
        $this->scopeConfigMock->expects($this->any())
                ->method('getValue')
                ->withConsecutive(
                    ['worldpay/cc_config/integration_mode',\Magento\Store\Model\ScopeInterface::SCOPE_STORE],
                    ['worldpay/apm_config/paymentmethods',\Magento\Store\Model\ScopeInterface::SCOPE_STORE],
                    ['worldpay/apm_config/achaccounttypes',\Magento\Store\Model\ScopeInterface::SCOPE_STORE]
                )
                ->will($this->onConsecutiveCalls('Direct', $this->APM_TYPES, $this->ACH_BANK_ACC_TYPES));

         $this->paymentlist->expects($this->any())
                 ->method('checkCurrency')
                 ->withConsecutive(
                     ['worldpay_apm','CHINAUNIONPAY-SSL'],
                     ['worldpay_apm','IDEAL-SSL'],
                     ['worldpay_apm','QIWI-SSL'],
                     ['worldpay_apm','PAYPAL-EXPRESS'],
                     ['worldpay_apm','SOFORT-SSL'],
                     ['worldpay_apm','GIROPAY-SSL'],
                     ['worldpay_apm','ALIPAY-SSL'],
                     ['worldpay_apm','SEPA_DIRECT_DEBIT-SSL'],
                     ['worldpay_apm','KLARNA-SSL'],
                     ['worldpay_apm','PRZELEWY-SSL'],
                     ['worldpay_apm','MISTERCASH-SSL'],
                     ['worldpay_apm','ACH_DIRECT_DEBIT-SSL']
                 )
                 ->willReturn(true);
         $this->assertEquals(explode(",", $this->ACH_BANK_ACC_TYPES), $this->dataObj->getACHDetails());
    }
/**
 * [testGetACHBankAccountTypes description]
 * @return [type] [description]
 */
    public function testGetACHBankAccountTypes()
    {
        $this->scopeConfigMock->expects($this->any())
                ->method('getValue')
                ->with('worldpay/apm_config/achaccounttypes', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                ->willReturn($this->ACH_BANK_ACC_TYPES);
        $this->assertEquals($this->ACH_BANK_ACC_TYPES, $this->dataObj->getACHBankAccountTypes());
    }
/**
 * [testIsIframeIntegration description]
 * @return [type] [description]
 */
    public function testIsIframeIntegration()
    {
        $this->scopeConfigMock->expects($this->any())
                ->method('getValue')
                ->with('worldpay/hpp_config/hpp_integration', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                ->willReturn('iframe');
        $this->assertEquals(true, $this->dataObj->isIframeIntegration());
    }
/**
 * [testGetRedirectIntegrationMode description]
 * @return [type] [description]
 */
    public function testGetRedirectIntegrationMode()
    {
        $this->scopeConfigMock->expects($this->any())
                ->method('getValue')
                ->with('worldpay/hpp_config/hpp_integration', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                ->willReturn('Redirect');
        $this->assertEquals('Redirect', $this->dataObj->getRedirectIntegrationMode());
    }
/**
 * [testGetCustomPaymentEnabled description]
 * @return [type] [description]
 */
    public function testGetCustomPaymentEnabled()
    {
        $this->scopeConfigMock->expects($this->any())
                ->method('getValue')
                ->with('worldpay/hpp_config/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                ->willReturn(true);
        $this->assertEquals(true, $this->dataObj->getCustomPaymentEnabled());
    }
/**
 * [testGetInstallationId description]
 * @return [type] [description]
 */
    public function testGetInstallationId()
    {
        $this->scopeConfigMock->expects($this->any())
                ->method('getValue')
                ->with('worldpay/hpp_config/installation_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                ->willReturn(1074624);
        $this->assertEquals(1074624, $this->dataObj->getInstallationId());
    }
/**
 * [testGetHideAddress description]
 * @return [type] [description]
 */
    public function testGetHideAddress()
    {
        $this->scopeConfigMock->expects($this->any())
                ->method('getValue')
                ->with('worldpay/hpp_config/hideaddress', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                ->willReturn(true);
        $this->assertEquals(true, $this->dataObj->getHideAddress());
    }
/**
 * [testIsMotoEnabled description]
 * @return [type] [description]
 */
    public function testIsMotoEnabled()
    {
        $this->scopeConfigMock->expects($this->once())
                ->method('getValue')
                ->with('worldpay/moto_config/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                ->willReturn(true);
        $this->assertEquals(true, $this->dataObj->isMotoEnabled());
    }
/**
 * [testGetMotoTypes description]
 * @return [type] [description]
 */
    public function testGetMotoTypes()
    {
        $this->scopeConfigMock->expects($this->any())
                 ->method('getValue')
                 ->with('worldpay/moto_config/paymentmethods', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                 ->willReturn($this->MOTO_TYPES);
         $this->paymentlist->expects($this->any())
                 ->method('checkCurrency')
                 ->withConsecutive(
                     ['worldpay_cc','AMEX-SSL'],
                     ['worldpay_cc','VISA-SSL'],
                     ['worldpay_cc','ECMC-SSL'],
                     ['worldpay_cc','CB-SSL'],
                     ['worldpay_cc','CARTEBLEUE-SSL'],
                     ['worldpay_cc','DANKORT-SSL'],
                     ['worldpay_cc','DINERS-SSL'],
                     ['worldpay_cc','DISCOVER-SSL'],
                     ['worldpay_cc','JCB-SSL'],
                     ['worldpay_cc','MAESTRO-SSL']
                 )
                 ->willReturn(true);
         $this->assertEquals($this->ALL_MOTO_METHODS, $this->dataObj->getCcTypes('moto_config'));
    }
/**
 * [testGetMotoTitle description]
 * @return [type] [description]
 */
    public function testGetMotoTitle()
    {
        $this->scopeConfigMock->expects($this->once())
                ->method('getValue')
                ->with('worldpay/moto_config/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                ->willReturn('Mail Order Telephone Order');
        $this->assertEquals('Mail Order Telephone Order', $this->dataObj->getMotoTitle());
    }
/**
 * [testIsGooglePayEnable description]
 * @return [type] [description]
 */
    public function testIsGooglePayEnable()
    {
        $this->scopeConfigMock->expects($this->any())
               ->method('getValue')
               ->with(
                   'worldpay/wallets_config/google_pay_wallets_config/enabled',
                   \Magento\Store\Model\ScopeInterface::SCOPE_STORE
               )
               ->willReturn(true);
        $this->assertEquals(true, $this->dataObj->isGooglePayEnable());
    }
/**
 * [testGooglePaymentMethods description]
 * @return [type] [description]
 */
    public function testGooglePaymentMethods()
    {
        $this->scopeConfigMock->expects($this->any())
                ->method('getValue')
                ->with(
                    'worldpay/wallets_config/google_pay_wallets_config/paymentmethods',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
                ->willReturn($this->GOOGLE_PAYMENT_METHODS);
        $this->assertEquals($this->GOOGLE_PAYMENT_METHODS, $this->dataObj->googlePaymentMethods());
    }
/**
 * [testGoogleAuthMethods description]
 * @return [type] [description]
 */
    public function testGoogleAuthMethods()
    {
        $this->scopeConfigMock->expects($this->any())
                ->method('getValue')
                ->with(
                    'worldpay/wallets_config/google_pay_wallets_config/authmethods',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
                ->willReturn($this->GOOGLE_AUTH_METHODS);
        $this->assertEquals($this->GOOGLE_AUTH_METHODS, $this->dataObj->googleAuthMethods());
    }
 /**
  * [testGoogleGatewayMerchantname description]
  * @return [type] [description]
  */
    public function testGoogleGatewayMerchantname()
    {
        $this->scopeConfigMock->expects($this->any())
                ->method('getValue')
                ->with(
                    'worldpay/wallets_config/google_pay_wallets_config/gateway_merchantname',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
                ->willReturn('worldpay');
        $this->assertEquals('worldpay', $this->dataObj->googleGatewayMerchantname());
    }
/**
 * [testGoogleMerchantname description]
 * @return [type] [description]
 */
    public function testGoogleMerchantname()
    {
        $this->scopeConfigMock->expects($this->any())
                ->method('getValue')
                ->with(
                    'worldpay/wallets_config/google_pay_wallets_config/google_merchantname',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
                ->willReturn('worldpay');
        $this->assertEquals('worldpay', $this->dataObj->googleMerchantname());
    }
 /**
  * [testGoogleMerchantid description]
  * @return [type] [description]
  */
    public function testGoogleMerchantid()
    {
        $this->scopeConfigMock->expects($this->any())
                ->method('getValue')
                ->with(
                    'worldpay/wallets_config/google_pay_wallets_config/google_merchantname',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
                ->willReturn('');
        $this->assertEquals('', $this->dataObj->googleMerchantname());
    }
/**
 * [testGetWalletsTypesGooglePay description]
 * @return [type] [description]
 */
    public function testGetWalletsTypesGooglePay()
    {
        $activeMethods = ['PAYWITHGOOGLE-SSL' => 'Google Pay',
           'SAMSUNGPAY-SSL' => 'Samsung Pay',
           'APPLEPAY-SSL' => 'Apple Pay'];
        $this->scopeConfigMock->expects($this->any())
               ->method('getValue')
               ->withConsecutive(
                   ['worldpay/wallets_config/google_pay_wallets_config/enabled',
                   \Magento\Store\Model\ScopeInterface::SCOPE_STORE],
                   ['worldpay/wallets_config/samsung_pay_wallets_config/enabled',
                   \Magento\Store\Model\ScopeInterface::SCOPE_STORE],
                   ['worldpay/wallets_config/apple_pay_wallets_config/enabled',
                   \Magento\Store\Model\ScopeInterface::SCOPE_STORE]
               )
               ->willReturn(true);
        $this->assertEquals($activeMethods, $this->dataObj->getWalletsTypes(''));
    }
 /**
  * [testIsWalletsEnabled description]
  * @return [type] [description]
  */
    public function testIsWalletsEnabled()
    {
         $this->scopeConfigMock->expects($this->any())
                 ->method('getValue')
                 ->with('worldpay/wallets_config/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                 ->willReturn(true);
         $this->assertEquals(true, $this->dataObj->isWalletsEnabled());
    }
/**
 * [testGetWalletsTitle description]
 * @return [type] [description]
 */
    public function testGetWalletsTitle()
    {
        $this->scopeConfigMock->expects($this->any())
                ->method('getValue')
                ->with('worldpay/wallets_config/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                ->willReturn('wallet');
        $this->assertEquals('wallet', $this->dataObj->getWalletsTitle());
    }
 /**
  * [testIsApplePayEnable description]
  * @return [type] [description]
  */
    public function testIsApplePayEnable()
    {
        $this->scopeConfigMock->expects($this->any())
                ->method('getValue')
                ->with(
                    'worldpay/wallets_config/apple_pay_wallets_config/enabled',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
                ->willReturn(true);
        $this->assertEquals(true, $this->dataObj->isApplePayEnable());
    }
 /**
  * [testAppleMerchantId description]
  * @return [type] [description]
  */
    public function testAppleMerchantId()
    {
        $this->scopeConfigMock->expects($this->any())
                ->method('getValue')
                ->with(
                    'worldpay/wallets_config/apple_pay_wallets_config/merchant_name',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
                ->willReturn('');
        $this->assertEquals('', $this->dataObj->appleMerchantId());
    }
/**
 * [testisCreditCardEnabled description]
 * @return [type] [description]
 */
    public function testisCreditCardEnabled()
    {
        $this->scopeConfigMock->expects($this->once())
               ->method('getValue')
               ->with('worldpay/cc_config/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
               ->willReturn(true);
        $this->assertEquals(true, $this->dataObj->isCreditCardEnabled());
    }
 /**
  * [testGetCcTitle description]
  * @return [type] [description]
  */
    public function testGetCcTitle()
    {
        $this->scopeConfigMock->expects($this->once())
               ->method('getValue')
               ->with('worldpay/cc_config/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
               ->willReturn('Alternative Payment Methods');
        $this->assertEquals('Alternative Payment Methods', $this->dataObj->getCcTitle());
    }
 /**
  * [testGetCcTypes description]
  * @return [type] [description]
  */
    public function testGetCcTypes()
    {
        $this->scopeConfigMock->expects($this->any())
                 ->method('getValue')
                 ->with('worldpay/cc_config/paymentmethods', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                 ->willReturn($this->CC_TYPES);
         $this->paymentlist->expects($this->any())
                 ->method('checkCurrency')
                 ->withConsecutive(
                     ['worldpay_cc','AMEX-SSL'],
                     ['worldpay_cc','VISA-SSL'],
                     ['worldpay_cc','ECMC-SSL'],
                     ['worldpay_cc','CB-SSL'],
                     ['worldpay_cc','CARTEBLEUE-SSL'],
                     ['worldpay_cc','DANKORT-SSL'],
                     ['worldpay_cc','DINERS-SSL'],
                     ['worldpay_cc','DISCOVER-SSL'],
                     ['worldpay_cc','JCB-SSL'],
                     ['worldpay_cc','MAESTRO-SSL']
                 )
                 ->willReturn(true);
         $this->assertEquals($this->ALL_CC_METHODS, $this->dataObj->getCcTypes('cc_config'));
    }
 /**
  * [testGetsubscriptionStatus description]
  * @return [type] [description]
  */
    public function testGetsubscriptionStatus()
    {
        $this->quote = $this->getMockBuilder(Quote::class)
                        ->disableOriginalConstructor()->getMock();
        $this->checkoutSession->expects($this->any())
                ->method('getQuote')
                ->willReturn($this->quote);
        $this->recurringHelper->expects($this->any())
                ->method('quoteContainsSubscription')
                ->with($this->quote)
                ->willReturn(true);
        $this->assertEquals(true, $this->dataObj->getsubscriptionStatus());
    }
}
