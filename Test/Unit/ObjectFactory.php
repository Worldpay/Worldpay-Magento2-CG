<?php
namespace Sapient\Worldpay\Test\Unit;

trait ObjectFactory
{
    /**
     * Construct a subject with named collaborators and stubs for other dependencies.
     *
     * @param string $class
     * @param array $arguments
     * @return object
     */
    private function createObject($class, array $arguments = [])
    {
        $reflection = new \ReflectionClass($class);
        $values = [];
        foreach ($reflection->getConstructor()->getParameters() as $parameter) {
            $name = $parameter->getName();
            if (array_key_exists($name, $arguments)) {
                $values[] = $arguments[$name];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $values[] = $parameter->getDefaultValue();
            } else {
                $type = $parameter->getType();
                if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                    throw new \InvalidArgumentException('Missing constructor argument: ' . $name);
                }
                $values[] = $this->createStub($type->getName());
            }
        }
        return $reflection->newInstanceArgs($values);
    }
}
