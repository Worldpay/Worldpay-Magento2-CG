<?php

/**
 * @copyright 2017 Sapient
 */

namespace Sapient\Worldpay\Test\Unit\Model\Config\Source;

use Sapient\Worldpay\Model\Config\Source\KlarnaCountries;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Directory\Model\ResourceModel\Country\Collection;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use \PHPUnit\Framework\TestCase;

class KlarnaCountriesTest extends TestCase
{
    
    /** @var KlarnaCountries  */
    protected $model;
    /**
     * Core store config
     *
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var AbstractCollection|MockObject
     */
    protected $abstractCollection;
    /**
     * Collection name
     *
     * @var string
     */
    protected $collection;

    protected function setUp(): void
    {
        $countryCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
                        ->disableOriginalConstructor()->getMock();
        $this->collection = $this->getMockBuilder(Collection::class)
                        ->disableOriginalConstructor()
                ->setMethods(['loadByStore'])->getMock();
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
                        ->disableOriginalConstructor()->getMock();
        $this->abstractCollection = $this->getMockBuilder(AbstractCollection::class)
                        ->disableOriginalConstructor()->getMock();
        $this->model = new KlarnaCountries($countryCollectionFactory, $this->scopeConfig);
    }
    
    public function testGetTopDestinations()
    {
        $destinations = 'SE,NO,FI,DE,AT,GB,DK,US,NL,CH';
        $this->scopeConfig->expects($this->once())
                ->method('getValue')
                ->with(
                    'worldpay/klarna_config/klarna_countries_config/klarna_contries',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
                ->willReturn($destinations);
        $this->assertEquals(explode(',', $destinations), $this->model->getTopDestinations());
    }
}
