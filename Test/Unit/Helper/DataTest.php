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
use Sapient\Worldpay\Helper\KlarnaCountries;
use \PHPUnit\Framework\TestCase;
use Sapient\Worldpay\Helper\Data;
use Magento\Quote\Model\Quote;

class DataTest extends TestCase
{
    use \Sapient\Worldpay\Test\Unit\ObjectFactory;
    use \Sapient\Worldpay\Test\Unit\ConsecutiveCalls;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $scopeConfigMock;
    /**
     * @var WorldpayLogger|MockObject
     */
    protected $wploggerMock;
    /**
     * @var Data
     */
    protected $dataObj;
    /**
     * @var SerializerInterface $serializer
     */
    protected $serializer;
    /**
     * @var /Sapient\Worldpay\Helper\KlarnaCountries
     */
    protected $klarnaCountries;
    /**
     * @var mixed
     */
    protected $paymentlist;
    /**
     * @var \Sapient\Worldpay\Helper\Recurring
     */
    protected $recurringHelper;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $quote;
    /**
     * @var string
     */
    protected $KLARNA_PAYNOW_TYPE = 'KLARNA_PAYNOW';
    /**
     * @var array
     */
    protected $KLARNA_PAYNOW_COUNTRIES = '[SE,DE,NL,AT]';
    /**
     * @var string
     */
    protected $KLARNA_PAYLATER_TYPE = 'KLARNA_PAYLATER';
    /**
     * @var array
     */
    protected $KLARNA_PAYLATER_COUNTRIES = '[SE,NO,FI,DE,NL,AT,CH,GB,DK,US]';
    /**
     * @var string
     */
    protected $KLARNA_SLICEIT_TYPE = 'KLARNA_SLICEIT';
    /**
     * @var array
     */
    protected $KLARNA_SLICEIT_COUNTRIES = '[SE,NO,FI,DE,AT,GB,DK,US]';
    /**
     * @var string
     */
    protected $APM_TYPES = 'CHINAUNIONPAY-SSL,IDEAL-SSL,PAYPAL-EXPRESS,PAYPAL-SSL,'
            . 'SOFORT-SSL,GIROPAY-SSL,ALIPAY-SSL,SEPA_DIRECT_DEBIT-SSL,'
            . 'KLARNA-SSL,PRZELEWY-SSL,MISTERCASH-SSL,ACH_DIRECT_DEBIT-SSL';
    /**
     * @var string
     */
    protected $ACH_BANK_ACC_TYPES = 'Checking,Savings,Corporate,Corp Savings';
    /**
     * @var string
     */
    protected $SEPA_MANDATE_TYPES = 'ONE-OFF, RECURRING';
    /**
     * @var string
     */
    protected $MOTO_TYPES = 'AMEX-SSL,VISA-SSL,ECMC-SSL,DISCOVER-SSL,DINERS-SSL,'
            . 'MAESTRO-SSL,AIRPLUS-SSL,AURORE-SSL,CB-SSL,CARTEBLEUE-SSL,'
            . 'DANKORT-SSL,GECAPITAL-SSL,JCB-SSL,LASER-SSL,UATP-SSL,ELO-SSL';
    /**
     * @var array
     */
    protected $ALL_APM_METHODS = [
            'CHINAUNIONPAY-SSL' => 'Union Pay',
            'IDEAL-SSL' => 'iDEAL | Wero',
            //'YANDEXMONEY-SSL' => 'Yandex.Money',
            'PAYPAL-EXPRESS' => 'PayPal',
            'SOFORT-SSL' => 'SoFort EU',
            //'BOLETO-SSL' => 'Boleto Bancairo',
            'ALIPAY-SSL' => 'AliPay',
            'SEPA_DIRECT_DEBIT-SSL' => 'SEPA (One off transactions)',
            'KLARNA-SSL' => 'Klarna',
            'PRZELEWY-SSL' => 'P24',
            'MISTERCASH-SSL' => 'Mistercash/Bancontact',
            'ACH_DIRECT_DEBIT-SSL' => 'ACH Pay'
        ];
    /**
     * @var array
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
                'UATP-SSL' => 'UATP',
                'ELO-SSL' => 'ELO'
        ];
    /**
     * @var string
     */
    protected $GOOGLE_PAYMENT_METHODS = 'AMEX,VISA,DISCOVER,JCB,MASTERCARD';
    /**
     * @var string
     */
    protected $GOOGLE_AUTH_METHODS = 'PAN_ONLY,CRYPTOGRAM_3DS';
    /**
     * @var string
     */
    protected $CC_TYPES = 'AMEX-SSL,VISA-SSL,ECMC-SSL,DISCOVER-SSL,DINERS-SSL,'
            . 'MAESTRO-SSL,AIRPLUS-SSL,AURORE-SSL,CB-SSL,CARTEBLEUE-SSL,'
            . 'DANKORT-SSL,GECAPITAL-SSL,JCB-SSL,LASER-SSL,UATP-SSL,ELO-SSL';
    /**
     * @var array
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
                'UATP-SSL' => 'UATP',
                'ELO-SSL' => 'ELO'
        ];

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->wploggerMock = $this->createStub(WorldpayLogger::class);
        $localeCurrency = $this->createStub(CurrencyInterface::class);
        $this->paymentlist = $this->createStub(PaymentMethods::class);
        $merchantprofile = $this->createStub(Merchantprofile::class);
        $this->checkoutSession = $this->createStub(Session::class);
        $orderFactory = $this->createStub(OrderFactory::class);
        $this->recurringHelper = $this->createStub(Recurring::class);
        $extendedResponseCodes = $this->createStub(ExtendedResponseCodes::class);
        $instalmentconfig = $this->createStub(Instalmentconfig::class);
        $orderCollectionFactory = $this->createStub(CollectionFactory::class);
        $savecard = $this->createStub(SavedTokenFactory::class);
        $currencyexponents = $this->createStub(Currencyexponents::class);
        $this->serializer = $this->createStub(SerializerInterface::class);
        $this->klarnaCountries = $this->createStub(KlarnaCountries::class);

        $this->dataObj = $this->createObject(
            Data::class,
            [
                'wplogger' => $this->wploggerMock,
                'scopeConfig' => $this->scopeConfigMock,
                'localeCurrency' => $localeCurrency,
                'paymentlist' => $this->paymentlist,
                'merchantprofile' => $merchantprofile,
                'checkoutSession' => $this->checkoutSession,
                'orderFactory' => $orderFactory,
                'recurringHelper' => $this->recurringHelper,
                'extendedResponseCodes' => $extendedResponseCodes,
                'instalmentconfig' => $instalmentconfig,
                'orderCollectionFactory' => $orderCollectionFactory,
                'savecard' => $savecard,
                'currencyexponents' => $currencyexponents,
                'serializer' => $this->serializer,
                'klarnaCountries' => $this->klarnaCountries,
            ]
        );
    }

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

    public function testIsApmEnabled()
    {
        $this->scopeConfigMock->expects($this->once())
                ->method('getValue')
                ->with('worldpay/apm_config/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                ->willReturn(true);
        $this->assertEquals(true, $this->dataObj->isApmEnabled());
    }

    public function testGetApmTitle()
    {
        $this->scopeConfigMock->expects($this->once())
                ->method('getValue')
                ->with('worldpay/apm_config/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                ->willReturn('Alternative Payment Methods');
        $this->assertEquals('Alternative Payment Methods', $this->dataObj->getApmTitle());
    }

    public function testGetApmPaymentMethods()
    {
        $this->scopeConfigMock->expects($this->once())
                ->method('getValue')
                ->with('worldpay/apm_config/paymentmethods', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                ->willReturn($this->APM_TYPES);
        $this->assertEquals($this->APM_TYPES, $this->dataObj->getApmPaymentMethods());
    }

    public function testIsKlarnaEnabled()
    {
        $this->scopeConfigMock->expects($this->once())
                ->method('getValue')
                ->with('worldpay/klarna_config/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                ->willReturn(true);
        $this->assertEquals(true, $this->dataObj->isKlarnaEnabled());
    }

    public function testGetKlarnaSliceitType()
    {
        $this->scopeConfigMock->expects($this->exactly(2))
                ->method('getValue')
                ->willReturnCallback($this->consecutiveCalls(
                    [
                    ['worldpay/klarna_config/enabled',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE],
                    ['worldpay/klarna_config/sliceit_config/klarna_sliceit',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE]
                ],
                    [true, $this->KLARNA_SLICEIT_TYPE]
                ));
        $this->assertEquals($this->KLARNA_SLICEIT_TYPE, $this->dataObj->getKlarnaSliceitType());
    }

    public function testGetKlarnaPayLaterType()
    {
        $this->scopeConfigMock->expects($this->exactly(2))
                ->method('getValue')
                ->willReturnCallback($this->consecutiveCalls(
                    [
                    ['worldpay/klarna_config/enabled',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE],
                    ['worldpay/klarna_config/paylater_config/klarna_paylater',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE]
                ],
                    [true, $this->KLARNA_PAYLATER_TYPE]
                ));
        $this->assertEquals($this->KLARNA_PAYLATER_TYPE, $this->dataObj->getKlarnaPayLaterType());
    }

    public function testGetKlarnaPayNowType()
    {
        $this->scopeConfigMock->expects($this->exactly(2))
                ->method('getValue')
                ->willReturnCallback($this->consecutiveCalls(
                    [
                    ['worldpay/klarna_config/enabled',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE],
                    ['worldpay/klarna_config/paynow_config/klarna_paynow',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE]
                ],
                    [true, $this->KLARNA_PAYNOW_TYPE]
                ));
        $this->assertEquals($this->KLARNA_PAYNOW_TYPE, $this->dataObj->getKlarnaPayNowType());
    }

    public function testGetKlarnaSliceitContries()
    {
        $this->scopeConfigMock->expects($this->exactly(4))
                ->method('getValue')
                ->willReturnCallback($this->consecutiveCalls(
                    [
                    ['worldpay/klarna_config/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE],
                    ['worldpay/klarna_config/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE],
                    ['worldpay/klarna_config/sliceit_config/klarna_sliceit',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE],
                    ['worldpay/klarna_config/sliceit_config/sliceit_contries',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE]
                ],
                    [true, true, $this->KLARNA_SLICEIT_TYPE, $this->KLARNA_SLICEIT_COUNTRIES]
                ));
        $this->assertEquals($this->KLARNA_SLICEIT_COUNTRIES, $this->dataObj->getKlarnaSliceitContries());
    }

    public function testGetKlarnaPayLaterContries()
    {
        $this->scopeConfigMock->expects($this->exactly(4))
                ->method('getValue')
                ->willReturnCallback($this->consecutiveCalls(
                    [
                    ['worldpay/klarna_config/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE],
                    ['worldpay/klarna_config/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE],
                    ['worldpay/klarna_config/paylater_config/klarna_paylater',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE],
                    ['worldpay/klarna_config/paylater_config/paylater_contries',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE]
                ],
                    [true, true, $this->KLARNA_PAYLATER_TYPE, $this->KLARNA_PAYLATER_COUNTRIES]
                ));
        $this->assertEquals($this->KLARNA_PAYLATER_COUNTRIES, $this->dataObj->getKlarnaPayLaterContries());
    }

    public function testGetKlarnaPayNowContries()
    {
        $this->scopeConfigMock->expects($this->exactly(4))
                ->method('getValue')
                ->willReturnCallback($this->consecutiveCalls(
                    [
                    ['worldpay/klarna_config/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE],
                    ['worldpay/klarna_config/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE],
                    ['worldpay/klarna_config/paynow_config/klarna_paynow',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE],
                    ['worldpay/klarna_config/paynow_config/paynow_contries',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE]
                ],
                    [true, true, $this->KLARNA_PAYNOW_TYPE, $this->KLARNA_PAYNOW_COUNTRIES]
                ));
        $this->assertEquals($this->KLARNA_PAYNOW_COUNTRIES, $this->dataObj->getKlarnaPayNowContries());
    }

    public function testGetKlarnaSubscriptionDays()
    {
        $array = ['subscription_days' => 30];
        $this->scopeConfigMock->expects($this->never())->method('getValue');
        $this->klarnaCountries->method('getConfigValue')->willReturnCallback(function ($country) use ($array) {
            $this->assertSame('US', $country);
            return $array;
        });
        $this->assertEquals($array['subscription_days'], $this->dataObj->getKlarnaSubscriptionDays('US'));
    }

    public function testGetApmTypes()
    {
        $this->scopeConfigMock->expects($this->exactly(2))
                ->method('getValue')
                ->willReturnMap([
                    ['worldpay/apm_config/paymentmethods', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null, $this->APM_TYPES],
                    ['worldpay/paypal_config/enable_paypal_ssl', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null, false]
                ]);
        $this->paymentlist
                ->method('checkCurrency')
                ->willReturnCallback($this->consecutiveCalls(
                    [
                    ['worldpay_apm','CHINAUNIONPAY-SSL'],
                    ['worldpay_apm','IDEAL-SSL'],
                    ['worldpay_apm','PAYPAL-EXPRESS'],
                    ['worldpay_apm','PAYPAL-SSL'],
                    ['worldpay_apm','SOFORT-SSL'],
                    ['worldpay_apm','GIROPAY-SSL'],
                    ['worldpay_apm','ALIPAY-SSL'],
                    ['worldpay_apm','SEPA_DIRECT_DEBIT-SSL'],
                    ['worldpay_apm','KLARNA-SSL'],
                    ['worldpay_apm','PRZELEWY-SSL'],
                    ['worldpay_apm','MISTERCASH-SSL'],
                    ['worldpay_apm','ACH_DIRECT_DEBIT-SSL']
                ],
                    array_fill(0, 12, true)
                ));
        $this->assertEquals($this->ALL_APM_METHODS, $this->dataObj->getApmTypes('worldpay_apm'));
    }

    public function testGetACHDetails()
    {
        $this->scopeConfigMock->expects($this->exactly(4))
                ->method('getValue')
                ->willReturnMap([
                    ['worldpay/cc_config/integration_mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null, 'Direct'],
                    ['worldpay/apm_config/paymentmethods', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null, $this->APM_TYPES],
                    ['worldpay/paypal_config/enable_paypal_ssl', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null, false],
                    ['worldpay/apm_config/achaccounttypes', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null, $this->ACH_BANK_ACC_TYPES]
                ]);
        $this->paymentlist
                ->method('checkCurrency')
                ->willReturnCallback($this->consecutiveCalls(
                    [
                    ['worldpay_apm','CHINAUNIONPAY-SSL'],
                    ['worldpay_apm','IDEAL-SSL'],
                    ['worldpay_apm','PAYPAL-EXPRESS'],
                    ['worldpay_apm','PAYPAL-SSL'],
                    ['worldpay_apm','SOFORT-SSL'],
                    ['worldpay_apm','GIROPAY-SSL'],
                    ['worldpay_apm','ALIPAY-SSL'],
                    ['worldpay_apm','SEPA_DIRECT_DEBIT-SSL'],
                    ['worldpay_apm','KLARNA-SSL'],
                    ['worldpay_apm','PRZELEWY-SSL'],
                    ['worldpay_apm','MISTERCASH-SSL'],
                    ['worldpay_apm','ACH_DIRECT_DEBIT-SSL']
                ],
                    array_fill(0, 12, true)
                ));
        $this->assertEquals(explode(",", $this->ACH_BANK_ACC_TYPES), $this->dataObj->getACHDetails());
    }

    public function testGetACHBankAccountTypes()
    {
        $this->scopeConfigMock->expects($this->atLeastOnce())
                ->method('getValue')
                ->with('worldpay/apm_config/achaccounttypes', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                ->willReturn($this->ACH_BANK_ACC_TYPES);
        $this->assertEquals($this->ACH_BANK_ACC_TYPES, $this->dataObj->getACHBankAccountTypes());
    }

    public function testGetSEPADetails()
    {
        $this->scopeConfigMock->expects($this->exactly(4))
                ->method('getValue')
                ->willReturnMap([
                    ['worldpay/cc_config/integration_mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null, 'Direct'],
                    ['worldpay/apm_config/paymentmethods', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null, $this->APM_TYPES],
                    ['worldpay/paypal_config/enable_paypal_ssl', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null, false],
                    ['worldpay/apm_config/sepa_mandate_types', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null, $this->SEPA_MANDATE_TYPES]
                ]);
        $this->paymentlist
                ->method('checkCurrency')
                ->willReturnCallback($this->consecutiveCalls(
                    [
                    ['worldpay_apm','CHINAUNIONPAY-SSL'],
                    ['worldpay_apm','IDEAL-SSL'],
                    ['worldpay_apm','PAYPAL-EXPRESS'],
                    ['worldpay_apm','PAYPAL-SSL'],
                    ['worldpay_apm','SOFORT-SSL'],
                    ['worldpay_apm','GIROPAY-SSL'],
                    ['worldpay_apm','ALIPAY-SSL'],
                    ['worldpay_apm','SEPA_DIRECT_DEBIT-SSL'],
                    ['worldpay_apm','KLARNA-SSL'],
                    ['worldpay_apm','PRZELEWY-SSL'],
                    ['worldpay_apm','MISTERCASH-SSL'],
                    ['worldpay_apm','ACH_DIRECT_DEBIT-SSL']
                ],
                    array_fill(0, 12, true)
                ));
        $this->assertEquals($this->SEPA_MANDATE_TYPES, $this->dataObj->getSEPADetails());
    }

    public function testGetSEPAMandateTypes()
    {
        $this->scopeConfigMock->expects($this->atLeastOnce())
                ->method('getValue')
                ->with('worldpay/apm_config/sepa_mandate_types', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                ->willReturn($this->SEPA_MANDATE_TYPES);
        $this->assertEquals($this->SEPA_MANDATE_TYPES, $this->dataObj->getSEPAMandateTypes());
    }

    public function testIsIframeIntegration()
    {
        $this->scopeConfigMock->expects($this->atLeastOnce())
                ->method('getValue')
                ->with('worldpay/hpp_config/hpp_integration', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                ->willReturn('iframe');
        $this->assertEquals(true, $this->dataObj->isIframeIntegration());
    }

    public function testGetRedirectIntegrationMode()
    {
        $this->scopeConfigMock->expects($this->atLeastOnce())
                ->method('getValue')
                ->with('worldpay/hpp_config/hpp_integration', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                ->willReturn('Redirect');
        $this->assertEquals('Redirect', $this->dataObj->getRedirectIntegrationMode());
    }

    public function testGetCustomPaymentEnabled()
    {
        $this->scopeConfigMock->expects($this->atLeastOnce())
                ->method('getValue')
                ->with('worldpay/hpp_config/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                ->willReturn(true);
        $this->assertEquals(true, $this->dataObj->getCustomPaymentEnabled());
    }

    public function testGetInstallationId()
    {
        $this->scopeConfigMock->expects($this->atLeastOnce())
                ->method('getValue')
                ->with('worldpay/hpp_config/installation_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                ->willReturn(1074624);
        $this->assertEquals(1074624, $this->dataObj->getInstallationId());
    }

    public function testGetHideAddress()
    {
        $this->scopeConfigMock->expects($this->atLeastOnce())
                ->method('getValue')
                ->with('worldpay/hpp_config/hideaddress', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                ->willReturn(true);
        $this->assertEquals(true, $this->dataObj->getHideAddress());
    }

    public function testIsMotoEnabled()
    {
        $this->scopeConfigMock->expects($this->once())
                ->method('getValue')
                ->with('worldpay/moto_config/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                ->willReturn(true);
        $this->assertEquals(true, $this->dataObj->isMotoEnabled());
    }

    public function testGetMotoTypes()
    {
        $this->scopeConfigMock->expects($this->atLeastOnce())
                ->method('getValue')
                 ->with('worldpay/moto_config/paymentmethods', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                 ->willReturn($this->MOTO_TYPES);
        $this->assertEquals($this->ALL_MOTO_METHODS, $this->dataObj->getCcTypes('moto_config'));
    }

    public function testGetMotoTitle()
    {
        $this->scopeConfigMock->expects($this->once())
                ->method('getValue')
                ->with('worldpay/moto_config/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                ->willReturn('Mail Order Telephone Order');
        $this->assertEquals('Mail Order Telephone Order', $this->dataObj->getMotoTitle());
    }

    public function testIsGooglePayEnable()
    {
        $this->scopeConfigMock->expects($this->atLeastOnce())
                ->method('getValue')
               ->with(
                   'worldpay/wallets_config/google_pay_wallets_config/enabled',
                   \Magento\Store\Model\ScopeInterface::SCOPE_STORE
               )
               ->willReturn(true);
        $this->assertEquals(true, $this->dataObj->isGooglePayEnable());
    }

    public function testGooglePaymentMethods()
    {
        $this->scopeConfigMock->expects($this->atLeastOnce())
                ->method('getValue')
                ->with(
                    'worldpay/wallets_config/google_pay_wallets_config/paymentmethods',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
                ->willReturn($this->GOOGLE_PAYMENT_METHODS);
        $this->assertEquals($this->GOOGLE_PAYMENT_METHODS, $this->dataObj->googlePaymentMethods());
    }

    public function testGoogleAuthMethods()
    {
        $this->scopeConfigMock->expects($this->atLeastOnce())
                ->method('getValue')
                ->with(
                    'worldpay/wallets_config/google_pay_wallets_config/authmethods',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
                ->willReturn($this->GOOGLE_AUTH_METHODS);
        $this->assertEquals($this->GOOGLE_AUTH_METHODS, $this->dataObj->googleAuthMethods());
    }

    public function testGoogleGatewayMerchantname()
    {
        $this->scopeConfigMock->expects($this->atLeastOnce())
                ->method('getValue')
                ->with(
                    'worldpay/wallets_config/google_pay_wallets_config/gateway_merchantname',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
                ->willReturn('worldpay');
        $this->assertEquals('worldpay', $this->dataObj->googleGatewayMerchantname());
    }

    public function testGoogleMerchantname()
    {
        $this->scopeConfigMock->expects($this->atLeastOnce())
                ->method('getValue')
                ->with(
                    'worldpay/wallets_config/google_pay_wallets_config/google_merchantname',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
                ->willReturn('worldpay');
        $this->assertEquals('worldpay', $this->dataObj->googleMerchantname());
    }

    public function testGoogleMerchantid()
    {
        $this->scopeConfigMock->expects($this->atLeastOnce())
                ->method('getValue')
                ->with(
                    'worldpay/wallets_config/google_pay_wallets_config/google_merchantname',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
                ->willReturn('');
        $this->assertEquals('', $this->dataObj->googleMerchantname());
    }

    public function testGetWalletsTypesGooglePay()
    {
        $activeMethods = ['PAYWITHGOOGLE-SSL' => 'Google Pay',
           'SAMSUNGPAY-SSL' => 'Samsung Pay',
           'APPLEPAY-SSL' => 'Apple Pay'];
        $this->scopeConfigMock->expects($this->atLeastOnce())
                ->method('getValue')
               ->willReturnCallback($this->consecutiveCalls(
                    [
                   ['worldpay/wallets_config/google_pay_wallets_config/enabled',
                   \Magento\Store\Model\ScopeInterface::SCOPE_STORE],
                   ['worldpay/wallets_config/apple_pay_wallets_config/enabled',
                           \Magento\Store\Model\ScopeInterface::SCOPE_STORE],
                   ['worldpay/wallets_config/samsung_pay_wallets_config/enabled',
                   \Magento\Store\Model\ScopeInterface::SCOPE_STORE]
               ],
                    array_fill(0, 3, true)
                ));
        $this->assertEquals($activeMethods, $this->dataObj->getWalletsTypes(''));
    }

    public function testIsWalletsEnabled()
    {
         $this->scopeConfigMock->expects($this->atLeastOnce())
                ->method('getValue')
                 ->with('worldpay/wallets_config/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                 ->willReturn(true);
         $this->assertEquals(true, $this->dataObj->isWalletsEnabled());
    }

    public function testGetWalletsTitle()
    {
        $this->scopeConfigMock->expects($this->atLeastOnce())
                ->method('getValue')
                ->with('worldpay/wallets_config/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                ->willReturn('wallet');
        $this->assertEquals('wallet', $this->dataObj->getWalletsTitle());
    }

    public function testIsApplePayEnable()
    {
        $this->scopeConfigMock->expects($this->atLeastOnce())
                ->method('getValue')
                ->with(
                    'worldpay/wallets_config/apple_pay_wallets_config/enabled',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
                ->willReturn(true);
        $this->assertEquals(true, $this->dataObj->isApplePayEnable());
    }

    public function testAppleMerchantId()
    {
        $this->scopeConfigMock->expects($this->atLeastOnce())
                ->method('getValue')
                ->with(
                    'worldpay/wallets_config/apple_pay_wallets_config/merchant_name',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
                ->willReturn('');
        $this->assertEquals('', $this->dataObj->appleMerchantId());
    }

    public function testisCreditCardEnabled()
    {
        $this->scopeConfigMock->expects($this->once())
               ->method('getValue')
               ->with('worldpay/cc_config/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
               ->willReturn(true);
        $this->assertEquals(true, $this->dataObj->isCreditCardEnabled());
    }

    public function testGetCcTitle()
    {
        $this->scopeConfigMock->expects($this->once())
               ->method('getValue')
               ->with('worldpay/cc_config/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
               ->willReturn('Alternative Payment Methods');
        $this->assertEquals('Alternative Payment Methods', $this->dataObj->getCcTitle());
    }

    public function testGetCcTypes()
    {
        $this->scopeConfigMock->expects($this->atLeastOnce())
                ->method('getValue')
                 ->with('worldpay/cc_config/paymentmethods', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                 ->willReturn($this->CC_TYPES);
        $this->assertEquals($this->ALL_CC_METHODS, $this->dataObj->getCcTypes('cc_config'));
    }

    public function testGetsubscriptionStatus()
    {
        $this->scopeConfigMock->expects($this->never())->method('getValue');
        $this->quote = $this->createStub(Quote::class);
        $this->checkoutSession
                ->method('getQuote')
                ->willReturn($this->quote);
        $this->recurringHelper
                ->method('quoteContainsSubscription')
                ->willReturnCallback(function ($quote) {
                    $this->assertSame($this->quote, $quote);
                    return true;
                });
        $this->assertEquals(true, $this->dataObj->getsubscriptionStatus());
    }
}
