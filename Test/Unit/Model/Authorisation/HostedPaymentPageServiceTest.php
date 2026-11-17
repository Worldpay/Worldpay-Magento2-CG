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
    use \Sapient\Worldpay\Test\Unit\ObjectFactory;

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
        $this->mappingservice = $this->createStub(Service::class);
        $this->paymentservicerequest = $this->createStub(PaymentServiceRequest::class);
        $wplogger = $this->createStub(WorldpayLogger::class);
        $redirectresponse = $this->createStub(RedirectResponse::class);
        $this->registryhelper = $this->createStub(Registry::class);
        $hppstate = $this->createStub(State::class);
        $this->checkoutsession = $this->createStub(Session::class);
        $urlInterface = $this->createStub(UrlInterface::class);

        $this->hppObj = $this->createObject(
            HostedPaymentPageService::class,
            [
                'mappingservice' => $this->mappingservice,
                'paymentservicerequest' => $this->paymentservicerequest,
                'wplogger' => $wplogger,
                'redirectresponse' => $redirectresponse,
                'registryhelper' => $this->registryhelper,
                'hppstate' => $hppstate,
                'checkoutsession' => $this->checkoutsession,
                'urlInterface' => $urlInterface,
            ]
        );
    }

    public function testAuthorizePayment()
    {
        $this->assertInstanceOf(HostedPaymentPageService::class, $this->hppObj);
    }
}
