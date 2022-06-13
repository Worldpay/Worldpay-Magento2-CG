<?php

/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Test\Unit\Model\Authorisation;

use \PHPUnit\Framework\TestCase;
use Sapient\Worldpay\Model\Authorisation\DirectService;
use Sapient\Worldpay\Model\Mapping\Service;
use Sapient\Worldpay\Model\Request\PaymentServiceRequest;
use Sapient\Worldpay\Logger\WorldpayLogger;
use Sapient\Worldpay\Model\Payment\Service as PaymentService;
use Sapient\Worldpay\Model\Response\DirectResponse;
use Sapient\Worldpay\Helper\Registry;
use Sapient\Worldpay\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Framework\UrlInterface;
use Sapient\Worldpay\Model\Utilities\PaymentMethods;
use Magento\Payment\Model\InfoInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Sapient\Worldpay\Model\Payment\UpdateWorldpaymentFactory;
use Magento\Framework\DataObject\Copy;

class DirectServiceTest extends TestCase
{

    /**
     *
     * @var DirectService
     */
    protected $dirctccObj;
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
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var \Sapient\Worldpay\Model\Payment\UpdateWorldpaymentFactory
     */
    protected $updateWorldPayPayment;

    protected function setUp(): void
    {
        $this->mappingservice = $this->getMockBuilder(Service::class)
                ->disableOriginalConstructor()
                ->setMethods(['collectDirectOrderParameters'])
                ->getMock();
        $this->paymentservicerequest = $this->getMockBuilder(PaymentServiceRequest::class)
                ->disableOriginalConstructor()
                ->getMock();
        $wplogger = $this->getMockBuilder(WorldpayLogger::class)
                        ->disableOriginalConstructor()->getMock();
        
        $directresponse = $this->getMockBuilder(DirectResponse::class)
                        ->disableOriginalConstructor()->setMethods(['checkCurrency'])->getMock();
        
        $updateWorldPayPayment = $this->getMockBuilder(UpdateWorldpaymentFactory::class)
                        ->disableOriginalConstructor()->getMock();
        
        $paymentService = $this->getMockBuilder(PaymentService::class)
                        ->disableOriginalConstructor()->getMock();
        
        $this->registryhelper = $this->getMockBuilder(Registry::class)
                        ->disableOriginalConstructor()
                        ->getMock();
        $this->datahelper = $this->getMockBuilder(Data::class)
                        ->disableOriginalConstructor()->getMock();
        $checkoutSession = $this->checkoutsession = $this->getMockBuilder(Session::class)
                ->disableOriginalConstructor()
                ->getMock();
        $urlInterface = $this->getMockBuilder(UrlInterface::class)
                        ->disableOriginalConstructor()->getMock();
           
        $objectCopyService = $this->getMockBuilder(Copy::class)
                        ->disableOriginalConstructor()->getMock();

        $this->dirctccObj = new DirectService(
            $this->mappingservice,
            $this->paymentservicerequest,
            $wplogger,
            $directresponse,
            $updateWorldPayPayment,
            $paymentService,
            $this->registryhelper,
            $urlInterface,
            $checkoutSession,
            $this->datahelper,
            $objectCopyService
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
                ->method('collectDirectOrderParameters')
                ->with('ORDER_CODE', $quoteRepository, '', [])
                ->willReturn($response);

        $this->assertInstanceOf(DirectService::class, $this->dirctccObj);
    }
}
