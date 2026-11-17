<?php
namespace Sapient\Worldpay\Test\Unit;

trait ConsecutiveCalls
{
    /**
     * Assert ordered arguments and return the corresponding result.
     *
     * @param array $arguments
     * @param array $results
     * @return \Closure
     */
    private function consecutiveCalls(array $arguments, array $results)
    {
        $index = 0;
        return function (...$actual) use ($arguments, $results, &$index) {
            self::assertArrayHasKey($index, $arguments);
            self::assertSame($arguments[$index], array_slice($actual, 0, count($arguments[$index])));
            return $results[$index++];
        };
    }
}
