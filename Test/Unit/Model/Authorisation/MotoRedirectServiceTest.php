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
    /** @var Data */
    protected $datahelper;

    /**
     *
     * @var HostedPaymentPageService
     */
    protected $motoObj;
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
        $this->mappingservice = $this->createStub(Service::class);
        $this->paymentservicerequest = $this->createStub(PaymentServiceRequest::class);
        $wplogger = $this->createStub(WorldpayLogger::class);
        $paymentService = $this->createStub(PaymentService::class);
        $redirectresponse = $this->createStub(RedirectResponse::class);
        $this->registryhelper = $this->createStub(Registry::class);
        $this->datahelper = $this->createStub(Data::class);
        $this->checkoutsession = $this->createStub(Session::class);
        $urlInterface = $this->createStub(UrlInterface::class);
        $paymentMethods = $this->createStub(PaymentMethods::class);

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
        $this->assertInstanceOf(MotoRedirectService::class, $this->motoObj);
    }
}
