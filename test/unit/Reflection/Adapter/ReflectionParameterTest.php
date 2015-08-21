<?php

namespace BetterReflectionTest\Reflection\Adapter;

use ReflectionClass as CoreReflectionClass;
use ReflectionParameter as CoreReflectionParameter;
use BetterReflection\Reflection\Adapter\ReflectionParameter as ReflectionParameterAdapter;
use BetterReflection\Reflection\ReflectionParameter as BetterReflectionParameter;
use BetterReflection\Reflection\ReflectionFunction as BetterReflectionFunction;
use BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;

/**
 * @covers \BetterReflection\Reflection\Adapter\ReflectionParameter
 */
class ReflectionParameterTest extends \PHPUnit_Framework_TestCase
{
    public function coreReflectionParameterNamesProvider()
    {
        $methods = get_class_methods(CoreReflectionParameter::class);
        return array_combine($methods, array_map(function ($i) { return [$i]; }, $methods));
    }

    /**
     * @param string $methodName
     * @dataProvider coreReflectionParameterNamesProvider
     */
    public function testCoreReflectionParameters($methodName)
    {
        $reflectionParameterAdapterReflection = new CoreReflectionClass(ReflectionParameterAdapter::class);
        $this->assertTrue($reflectionParameterAdapterReflection->hasMethod($methodName));
    }

    public function methodExpectationProvider()
    {
        $mockFunction = $this->getMockBuilder(BetterReflectionFunction::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockMethod = $this->getMockBuilder(BetterReflectionMethod::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockClassLike = $this->getMockBuilder(BetterReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();

        return [
            ['__toString', null, '', []],
            ['getName', null, '', []],
            ['isPassedByReference', null, true, []],
            ['canBePassedByValue', null, true, []],
            ['getDeclaringFunction', null, $mockFunction, []],
            ['getDeclaringFunction', null, $mockMethod, []],
            ['getDeclaringClass', null, null, []],
            ['getDeclaringClass', null, $mockClassLike, []],
            ['getClass', null, null, []],
            ['getClass', null, $mockClassLike, []],
            ['isArray', null, true, []],
            ['isCallable', null, true, []],
            ['allowsNull', null, true, []],
            ['getPosition', null, 123, []],
            ['isOptional', null, true, []],
            ['isDefaultValueAvailable', null, true, []],
            ['getDefaultValue', null, true, []],
            ['isDefaultValueConstant', null, true, []],
            ['getDefaultValueConstantName', null, 'foo', []],
        ];
    }

    /**
     * @param string $methodName
     * @param string|null $expectedException
     * @param mixed $returnValue
     * @param array $args
     * @dataProvider methodExpectationProvider
     */
    public function testAdapterMethods($methodName, $expectedException, $returnValue, array $args)
    {
        /* @var BetterReflectionParameter|\PHPUnit_Framework_MockObject_MockObject $reflectionStub */
        $reflectionStub = $this->getMockBuilder(BetterReflectionParameter::class)
            ->disableOriginalConstructor()
            ->getMock();

        if (null === $expectedException) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->will($this->returnValue($returnValue));
        }

        if (null !== $expectedException) {
            $this->setExpectedException($expectedException);
        }

        $adapter = new ReflectionParameterAdapter($reflectionStub);
        $adapter->{$methodName}(...$args);
    }

    public function testExport()
    {
        $this->setExpectedException(\Exception::class, 'Unable to export statically');
        ReflectionParameterAdapter::export('foo', 0);
    }
}
