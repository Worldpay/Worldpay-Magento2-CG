<?php

/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Test\Unit\Model\Config\Source;

use \PHPUnit\Framework\TestCase;
use Sapient\Worldpay\Model\Config\Source\HppIntegration;

class HppIntegrationTest extends TestCase {
    /** @var HppIntegration  */
    protected $model;
    
    protected function setUp() {
        $this->model = new HppIntegration();
    }
    
    public function testToOptionArray() {
        $expectedResult = [
            ['value' => 'full_page', 'label' => __('Full page')],
            ['value' => 'iframe', 'label' => __('Iframe')],
        ];
        $this->assertEquals($expectedResult, $this->model->toOptionArray());
    }
}
