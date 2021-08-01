<?php


/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Test\Unit\Helper;

use Sapient\Worldpay\Helper\KlarnaCountries;
use Magento\Store\Model\Store;
use Magento\Framework\Math\Random;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Sapient\Worldpay\Model\Config\Source\KlarnaCountries as KlarnaSourceCountries;
use \PHPUnit\Framework\TestCase;

class KlarnaCountriesTest extends TestCase
{
    protected $scopeConfig;
     /** @var KlarnaCountries  */
    protected $model;
    
    protected function setUp(): void
    {
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
                        ->disableOriginalConstructor()->getMock();
        $mathRandom = $this->getMockBuilder(Random::class)
                        ->disableOriginalConstructor()->getMock();
        $serializer = $this->getMockBuilder(SerializerInterface::class)
                        ->disableOriginalConstructor()->getMock();
        $klarnaCountries = $this->getMockBuilder(KlarnaSourceCountries::class)
                        ->disableOriginalConstructor()->getMock();
        $this->model = new KlarnaCountries($this->scopeConfig, $mathRandom, $serializer, $klarnaCountries);
    }
    
    public function testGetConfigValue()
    {
        $value = ['Sweden' => 14, 'Norway' => 14,
            'Finland' => 14, 'Germany' => 14,
            'United Kingdom' => 30,
            'Denmark' => 14, 'US' => 30,
            'Netherlands' => 14, 'Switzerland' => 14];
        $this->scopeConfig->expects($this->once())
                ->method('getValue')
                ->with(
                    'worldpay/klarna_config/paylater_config/paylater_days_config/subscription_days',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
                ->willReturn($value);

        $this->assertNull($this->model->getConfigValue('US'));
    }
}
