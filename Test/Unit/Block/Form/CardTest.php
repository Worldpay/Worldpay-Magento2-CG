<?php
namespace Sapient\Worldpay\Test\Unit\Block\Form;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sapient\Worldpay\Block\Form\Card;
use Sapient\Worldpay\Helper\Data;
use Sapient\Worldpay\Model\WorldpayConfigProvider;

class CardTest extends TestCase
{
    /**
     * @dataProvider integrationModes
     * @param string $mode
     * @param bool $direct
     * @param bool $redirect
     */
    #[DataProvider('integrationModes')]
    public function testIntegrationMode($mode, $direct, $redirect)
    {
        $helper = $this->createMock(Data::class);
        $helper->expects($this->exactly(2))->method('getMotoIntegrationMode')->willReturn($mode);
        $block = $this->blockWithProperty('worldpayhelper', $helper);
        self::assertSame($direct, $block->isDirectIntegration());
        self::assertSame($redirect, $block->isRedirectIntegration());
    }

    /**
     * @return array
     */
    public static function integrationModes()
    {
        return [
            ['moto_direct', true, false],
            ['moto_redirect', false, true],
            ['', false, false]
        ];
    }

    public function testSaveCardEnabled()
    {
        $provider = $this->createMock(WorldpayConfigProvider::class);
        $provider->expects($this->once())->method('getIsSaveCardAllowed')->willReturn(true);
        self::assertTrue($this->blockWithProperty('configProvider', $provider)->saveCardEnabled());
    }

    public function testRequireCvcEnabled()
    {
        $helper = $this->createMock(Data::class);
        $helper->expects($this->once())->method('isCcRequireCVC')->willReturn('1');
        self::assertTrue($this->blockWithProperty('worldpayhelper', $helper)->requireCvcEnabled());
    }

    /**
     * @param string $name
     * @param object $value
     * @return Card
     */
    private function blockWithProperty($name, $value)
    {
        $reflection = new \ReflectionClass(Card::class);
        $block = $reflection->newInstanceWithoutConstructor();
        $reflection->getProperty($name)->setValue($block, $value);
        return $block;
    }
}
