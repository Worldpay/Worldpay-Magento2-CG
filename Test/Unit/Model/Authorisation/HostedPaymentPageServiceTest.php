<?php

/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Test\Unit\Model\Authorisation;

use \PHPUnit\Framework\TestCase;
use Sapient\Worldpay\Model\Authorisation\HostedPaymentPageService;
use Sapient\Worldpay\Model\Mapping\Service;
use Sapient\Worldpay\Model\Request\PaymentServiceRequest;
use Sapient\Worldpay\Logger\WorldpayLogger;
use Sapient\Worldpay\Model\Response\RedirectResponse;
use Sapient\Worldpay\Helper\Registry;
use Sapient\Worldpay\Model\Checkout\Hpp\State;
use Magento\Checkout\Model\Session;
use Magento\Framework\UrlInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Quote\Api\CartRepositoryInterface;

class HostedPaymentPageServiceTest extends TestCase
{

    /**
     *
     * @var HostedPaymentPageService
     */
    protected $hppObj;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutsession;
    /**
     * @var \Sapient\Worldpay\Model\Mapping\Service
     */
    protected $mappingservice;
    /**
     * @var \Sapient\Worldpay\Model\Request\PaymentServiceRequest
     */
    protected $paymentservicerequest;
    /**
     * @var \Sapient\Worldpay\Helper\Registry
     */
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
        $redirectresponse = $this->getMockBuilder(RedirectResponse::class)
                        ->disableOriginalConstructor()->setMethods(['checkCurrency'])->getMock();
        $this->registryhelper = $this->getMockBuilder(Registry::class)
                        ->disableOriginalConstructor()
                        ->setMethods(['setworldpayRedirectUrl'])->getMock();
        $hppstate = $this->getMockBuilder(State::class)
                        ->disableOriginalConstructor()->getMock();
        $this->checkoutsession = $this->getMockBuilder(Session::class)
                ->disableOriginalConstructor()
                ->setMethods(['setauthenticatedOrderId', 'setWpRedirecturl'])
                ->getMock();
        $urlInterface = $this->getMockBuilder(UrlInterface::class)
                        ->disableOriginalConstructor()->getMock();

        $this->hppObj = new HostedPaymentPageService(
            $this->mappingservice,
            $this->paymentservicerequest,
            $wplogger,
            $redirectresponse,
            $this->registryhelper,
            $hppstate,
            $this->checkoutsession,
            $urlInterface
        );
    }

    public function testAuthorizePayment()
    {

        $payment = $this->getMockBuilder(InfoInterface::class)
                ->disableOriginalConstructor()->setMethods(['setIsTransactionPending', 'getOrder'])
                ->getMockForAbstractClass();
        $mageOrder = $payment->expects($this->any())
                ->method('getOrder')
                ->willReturn(190);
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
            'orderCode' => '000000177-1601543787',
            'merchantCode' => 'SAPIENTNITROECOMMERCEV1',
            'orderDescription' => 'WorldPay Order',
            'currencyCode' => 'EUR',
            'amount' => 8335,
            'paymentType' => 'ECMC-SSL',
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
            'method' => 'ECMC-SSL',
            'paymentPagesEnabled' => 'true',
            'installationId' => '1074624',
            'hideAddress' => 'true',
            'shopperId' => '',
            'orderStoreId' => '	000000177-1601543787',
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

        $this->registryhelper->expects($this->any())
                ->method('setworldpayRedirectUrl')
                ->with('https://hpp-sandbox.worldpay.com/app/hpp/61-0/payment/start')
                ->willReturn(true);

        $this->checkoutsession->expects($this->any())
                ->method('setWpRedirecturl')
                ->with('https://hpp-sandbox.worldpay.com/app/hpp/61-0/payment/start')
                ->willReturn(true);

        $this->assertInstanceOf(HostedPaymentPageService::class, $this->hppObj);
    }
}
