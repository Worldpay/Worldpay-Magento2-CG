<?php

/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Test\Unit\Model;

use Sapient\Worldpay\Logger\WorldpayLogger;
use Sapient\Worldpay\Helper\Data;
use Magento\Payment\Helper\Data as PaymentHelper;
use Sapient\Worldpay\Model\PaymentMethods\CreditCards as WorldPayCCPayment;
use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Backend\Model\Session\Quote;
use Sapient\Worldpay\Model\SavedTokenFactory;
use Sapient\Worldpay\Model\Utilities\PaymentMethods;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Asset\Source;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Serialize\SerializerInterface;
use \PHPUnit\Framework\TestCase;
use Sapient\Worldpay\Model\WorldpayConfigProvider;

class WorldpayConfigProviderTest extends TestCase
{

    protected $worldpayConfigObj;
    protected $dataHelper;
    protected $paymentmethodutils;
    protected $ALL_APM_METHODS = [
        'CHINAUNIONPAY-SSL' => 'Union Pay',
        'IDEAL-SSL' => 'IDEAL',
        'QIWI-SSL' => 'Qiwi',
        //'YANDEXMONEY-SSL' => 'Yandex.Money',
        'PAYPAL-EXPRESS' => 'PayPal',
        'SOFORT-SSL' => 'SoFort EU',
        'GIROPAY-SSL' => 'GiroPay',
        //'BOLETO-SSL' => 'Boleto Bancairo',
        'ALIPAY-SSL' => 'AliPay',
        'SEPA_DIRECT_DEBIT-SSL' => 'SEPA (One off transactions)',
        'KLARNA-SSL' => 'Klarna',
        'PRZELEWY-SSL' => 'P24',
        'MISTERCASH-SSL' => 'Mistercash/Bancontact',
        'ACH_DIRECT_DEBIT-SSL' => 'ACH Pay'
    ];
    protected $IDEAL_BANKS_INFO = [
        'ING' => 'ING',
        'ABN_AMRO' => 'ABN AMRO',
        'ASN' => 'ASN',
        'RABOBANK' => 'Rabo Bank',
        'SNS' => 'SNS',
        'SNS_REGIO' => 'SNS Regio',
        'TRIODOS' => 'Triodos',
        'VAN_LANSCHOT' => 'Van Lanschot',
        'KNAB' => 'Knab'
    ];
    protected $KLARNA_PAYNOW_TYPE = 'KLARNA_PAYNOW';
    protected $KLARNA_PAYNOW_COUNTRIES = '[SE,DE,NL,AT]';
    protected $KLARNA_PAYLATER_TYPE = 'KLARNA_PAYLATER';
    protected $KLARNA_PAYLATER_COUNTRIES = '[SE,NO,FI,DE,NL,AT,CH,GB,DK,US]';
    protected $KLARNA_SLICEIT_TYPE = 'KLARNA_SLICEIT';
    protected $KLARNA_SLICEIT_COUNTRIES = '[SE,NO,FI,DE,AT,GB,DK,US]';
    
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

    protected function setUp(): void
    {
        $wplogger = $this->getMockBuilder(WorldpayLogger::class)
                        ->disableOriginalConstructor()->getMock();
        $this->dataHelper = $this->getMockBuilder(Data::class)
                        ->disableOriginalConstructor()->getMock();
        $paymentHelper = $this->getMockBuilder(PaymentHelper::class)
                        ->disableOriginalConstructor()->getMock();
        $payment = $this->getMockBuilder(WorldPayCCPayment::class)
                        ->disableOriginalConstructor()->getMock();
        $cart = $this->getMockBuilder(Cart::class)
                        ->disableOriginalConstructor()->getMock();
        $customerSession = $this->getMockBuilder(CustomerSession::class)
                        ->disableOriginalConstructor()->getMock();
        $adminquotesession = $this->getMockBuilder(Quote::class)
                        ->disableOriginalConstructor()->getMock();
        $savedTokenFactory = $this->getMockBuilder(SavedTokenFactory::class)
                        ->disableOriginalConstructor()->getMock();
        $this->paymentmethodutils = $this->getMockBuilder(PaymentMethods::class)
                        ->disableOriginalConstructor()->getMock();
        $backendAuthSession = $this->getMockBuilder(AuthSession::class)
                        ->disableOriginalConstructor()->getMock();
        $assetRepo = $this->getMockBuilder(Repository::class)
                        ->disableOriginalConstructor()->getMock();
        $request = $this->getMockBuilder(RequestInterface::class)
                        ->disableOriginalConstructor()->getMock();
        $assetSource = $this->getMockBuilder(Source::class)
                        ->disableOriginalConstructor()->getMock();
        $localeResolver = $this->getMockBuilder(ResolverInterface::class)
                        ->disableOriginalConstructor()->getMock();
        $serializer = $this->getMockBuilder(SerializerInterface::class)
                        ->disableOriginalConstructor()->getMock();

        $this->worldpayConfigObj = new WorldpayConfigProvider(
            $wplogger,
            $this->dataHelper,
            $paymentHelper,
            $payment,
            $cart,
            $customerSession,
            $adminquotesession,
            $savedTokenFactory,
            $this->paymentmethodutils,
            $backendAuthSession,
            $assetRepo,
            $request,
            $assetSource,
            $localeResolver,
            $serializer
        );
    }

    public function testGetApmTypes()
    {
        $this->dataHelper->expects($this->any())
                ->method('getApmTypes')
                ->with('worldpay_apm')
                ->willReturn($this->ALL_APM_METHODS);
        $this->assertEquals($this->ALL_APM_METHODS, $this->worldpayConfigObj->getApmTypes('worldpay_apm'));
    }

    public function testGetApmtitle()
    {
        $this->dataHelper->expects($this->any())
                ->method('getApmTitle')
                ->willReturn('Alternative Payment Methods');
        $this->assertEquals('Alternative Payment Methods', $this->worldpayConfigObj->getApmtitle());
    }

    public function testGetApmIdealBankList()
    {
        $this->dataHelper->expects($this->any())
                ->method('getApmTypes')
                ->with('worldpay_apm')
                ->willReturn($this->ALL_APM_METHODS);
        $this->paymentmethodutils->expects($this->any())
                ->method('idealBanks')
                ->willReturn($this->IDEAL_BANKS_INFO);
        $this->assertEquals($this->IDEAL_BANKS_INFO, $this->worldpayConfigObj->getApmIdealBankList());
    }

    public function testGetKlarnaTypesAndContries()
    {
        $klarnaValues = [$this->KLARNA_SLICEIT_TYPE => $this->KLARNA_SLICEIT_COUNTRIES,
            $this->KLARNA_PAYLATER_TYPE => $this->KLARNA_PAYLATER_COUNTRIES,
            $this->KLARNA_PAYNOW_TYPE => $this->KLARNA_PAYNOW_COUNTRIES];
        $this->dataHelper->expects($this->any())
                ->method('getKlarnaSliceitType')
                ->willReturn($this->KLARNA_SLICEIT_TYPE);
        $this->dataHelper->expects($this->any())
                ->method('getKlarnaPayLaterType')
                ->willReturn($this->KLARNA_PAYLATER_TYPE);
        $this->dataHelper->expects($this->any())
                ->method('getKlarnaPayNowType')
                ->willReturn($this->KLARNA_PAYNOW_TYPE);
        $this->dataHelper->expects($this->any())
                ->method('getKlarnaSliceitContries')
                ->willReturn($this->KLARNA_SLICEIT_COUNTRIES);
        $this->dataHelper->expects($this->any())
                ->method('getKlarnaPayLaterContries')
                ->willReturn($this->KLARNA_PAYLATER_COUNTRIES);
        $this->dataHelper->expects($this->any())
                ->method('getKlarnaPayNowContries')
                ->willReturn($this->KLARNA_PAYNOW_COUNTRIES);
        $this->assertEquals($klarnaValues, $this->worldpayConfigObj->getKlarnaTypesAndContries());
    }
    
    public function testGetWalletstitle()
    {
        $this->dataHelper->expects($this->any())
                ->method('getWalletstitle')
                ->willReturn('wallet');
        $this->assertEquals('wallet', $this->worldpayConfigObj->getWalletstitle());
    }

    public function testGetCcTypes()
    {
        $this->dataHelper->expects($this->any())
                ->method('getCcTypes')
                ->with('worldpay_cc')
                ->willReturn($this->ALL_CC_METHODS);
        $this->assertEquals($this->ALL_CC_METHODS, $this->worldpayConfigObj->getCcTypes('worldpay_cc'));
    }
    
    public function testGetCctitle()
    {
        $this->dataHelper->expects($this->any())
                ->method('getCcTitle')
                ->willReturn('Credit Cards');
        $this->assertEquals('Credit Cards', $this->worldpayConfigObj->getCctitle());
    }
}
