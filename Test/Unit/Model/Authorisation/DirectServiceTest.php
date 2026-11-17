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
    use \Sapient\Worldpay\Test\Unit\ObjectFactory;
    /** @var Data */
    protected $datahelper;

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
        $this->mappingservice = $this->createStub(Service::class);
        $this->paymentservicerequest = $this->createStub(PaymentServiceRequest::class);
        $wplogger = $this->createStub(WorldpayLogger::class);
        
        $directresponse = $this->createStub(DirectResponse::class);
        
        $updateWorldPayPayment = $this->createStub(UpdateWorldpaymentFactory::class);
        
        $paymentService = $this->createStub(PaymentService::class);
        
        $this->registryhelper = $this->createStub(Registry::class);
        $this->datahelper = $this->createStub(Data::class);
        $checkoutSession = $this->checkoutsession = $this->createStub(Session::class);
        $urlInterface = $this->createStub(UrlInterface::class);
           
        $objectCopyService = $this->createStub(Copy::class);

        $this->dirctccObj = $this->createObject(
            DirectService::class,
            [
                'mappingservice' => $this->mappingservice,
                'paymentservicerequest' => $this->paymentservicerequest,
                'wplogger' => $wplogger,
                'directResponse' => $directresponse,
                'updateWorldPayPayment' => $updateWorldPayPayment,
                'paymentservice' => $paymentService,
                'registryhelper' => $this->registryhelper,
                'urlBuilder' => $urlInterface,
                'checkoutSession' => $checkoutSession,
                'worldpayHelper' => $this->datahelper,
                'objectCopyService' => $objectCopyService,
            ]
        );
    }

    public function testAuthorizePayment()
    {
        $this->assertInstanceOf(DirectService::class, $this->dirctccObj);
    }
}
