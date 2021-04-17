<?php

/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Test\Unit\Model\Authorisation;

use \PHPUnit\Framework\TestCase;
use Sapient\Worldpay\Model\Authorisation\MotoRedirectService;
use Sapient\Worldpay\Model\Mapping\Service;
use Sapient\Worldpay\Model\Request\PaymentServiceRequest;
use Sapient\Worldpay\Logger\WorldpayLogger;
use Sapient\Worldpay\Model\Payment\Service as PaymentService;
use Sapient\Worldpay\Model\Response\RedirectResponse;
use Sapient\Worldpay\Helper\Registry;
use Sapient\Worldpay\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Framework\UrlInterface;
use Sapient\Worldpay\Model\Utilities\PaymentMethods;
use Magento\Payment\Model\InfoInterface;
use Magento\Quote\Api\CartRepositoryInterface;

class MotoRedirectServiceTest extends TestCase
{

    /**
     *
     * @var HostedPaymentPageService
     */
    protected $motoObj;
    protected $checkoutsession;
    protected $mappingservice;
    protected $paymentservicerequest;
    protected $registryhelper;

    protected function setUp(): void
    {
        $this->mappingservice = $this->getMockBuilder(Service::class)
                ->disableOriginalConstructor()
                ->setMethods(['collectRedirectOrderParameters'])
                ->getMock();
        $this->paymentservicerequest = $this->getMockBuilder(PaymentServiceRequest::class)
                ->disableOriginalConstructor()
                ->getMock();
        $wplogger = $this->getMockBuilder(WorldpayLogger::class)
                        ->disableOriginalConstructor()->getMock();
        $paymentService = $this->getMockBuilder(PaymentService::class)
                        ->disableOriginalConstructor()->getMock();
        $redirectresponse = $this->getMockBuilder(RedirectResponse::class)
                        ->disableOriginalConstructor()->setMethods(['checkCurrency'])->getMock();
        $this->registryhelper = $this->getMockBuilder(Registry::class)
                        ->disableOriginalConstructor()
                        ->setMethods(['setworldpayRedirectUrl'])->getMock();
        $this->datahelper = $this->getMockBuilder(Data::class)
                        ->disableOriginalConstructor()->getMock();
        $this->checkoutsession = $this->getMockBuilder(Session::class)
                ->disableOriginalConstructor()
                ->setMethods(['setauthenticatedOrderId', 'setWpRedirecturl'])
                ->getMock();
        $urlInterface = $this->getMockBuilder(UrlInterface::class)
                        ->disableOriginalConstructor()->getMock();
        $paymentMethods = $this->getMockBuilder(PaymentMethods::class)
                        ->disableOriginalConstructor()->getMock();

        $this->motoObj = new MotoRedirectService(
            $this->mappingservice,
            $this->paymentservicerequest,
            $wplogger,
            $paymentService,
            $redirectresponse,
            $this->registryhelper,
            $this->datahelper,
            $this->checkoutsession,
            $urlInterface,
            $paymentMethods
        );
    }

    public function testAuthorizePayment()
    {

        $payment = $this->getMockBuilder(InfoInterface::class)
                ->disableOriginalConstructor()->setMethods(['setIsTransactionPending', 'getOrder'])
                ->getMockForAbstractClass();
        $mageOrder = $payment->expects($this->any())
                ->method('getOrder')
                ->willReturn(180);
        $payment->expects($this->any())
                ->method('setIsTransactionPending')
                ->with(1)
                ->willReturn(true);
        $this->checkoutsession->expects($this->any())
                ->method('setauthenticatedOrderId')
                ->with($mageOrder)
                ->willReturn(true);

        $quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
                ->disableOriginalConstructor()->setMethods(['get'])
                ->getMockForAbstractClass();
        $response = [
            'orderCode' => '000000180-1601543787',
            'merchantCode' => 'SAPIENTNITROECOMMERCEV1',
            'orderDescription' => 'WorldPay Order',
            'currencyCode' => 'EUR',
            'amount' => 8335,
            'paymentType' => 'AMEX-SSL',
            'shopperEmail' => 'roni_cost@example.com',
            'statementNarrative' => '',
            'threeDSecureConfig' => '',
            'tokenRequestConfig' => '',
            'acceptHeader' => '',
            'userAgentHeader' => '',
            'shippingAddress' => 'Veronica Costello,
                                  6146 Honey Bluff Parkway
                                  Calder, Michigan, 49628-7978,
                                  United States,
                                  T: (555) 229-3326',
            'billingAddress' => 'Veronica Costello,
                                  6146 Honey Bluff Parkway
                                  Calder, Michigan, 49628-7978,
                                  United States,
                                  T: (555) 229-3326',
            'method' => 'AMEX-SSL',
            'paymentPagesEnabled' => 'true',
            'installationId' => '1074624',
            'hideAddress' => 'true',
            'shopperId' => '',
            'orderStoreId' => '000000180-1601543787',
            'paymentDetails' => 'SENT_FOR_AUTHORISATION',
            'thirdPartyData' => '',
            'shippingfee' => '0',
            'exponent' => '2'
        ];

        $this->mappingservice->expects($this->any())
                ->method('collectRedirectOrderParameters')
                ->with('ORDER_CODE', $quoteRepository, '', [])
                ->willReturn($response);

        $this->paymentservicerequest->expects($this->any())
                ->method('redirectOrder')
                ->with($response)
                ->willReturn($response);
        $this->assertInstanceOf(MotoRedirectService::class, $this->motoObj);
    }
}
