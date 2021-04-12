<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant as ReflectionClassConstantAdapter;
use PHPStan\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionClassConstant as BetterReflectionClassConstant;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionClassConstant as CoreReflectionClassConstant;
use function array_combine;
use function array_map;
use function get_class_methods;

/**
 * @covers \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant
 */
class ReflectionClassConstantTest extends TestCase
{
    public function coreReflectionMethodNamesProvider() : array
    {
        $methods = get_class_methods(CoreReflectionClassConstant::class);

        return array_combine($methods, array_map(static function (string $i) : array {
            return [$i];
        }, $methods));
    }

    /**
     * @dataProvider coreReflectionMethodNamesProvider
     */
    public function testCoreReflectionMethods(string $methodName) : void
    {
        $reflectionClassConstantAdapterReflection = new CoreReflectionClass(ReflectionClassConstantAdapter::class);
        self::assertTrue($reflectionClassConstantAdapterReflection->hasMethod($methodName));
    }

    public function methodExpectationProvider() : array
    {
        return [
            ['__toString', '', []],
            ['getName', '', []],
            ['getValue', null, []],
            ['isPublic', true, []],
            ['isPrivate', true, []],
            ['isProtected', true, []],
            ['getModifiers', 123, []],
            ['getDeclaringClass', $this->createMock(BetterReflectionClass::class), []],
            ['getDocComment', '', []],
        ];
    }

    /**
     * @param mixed   $returnValue
     * @param mixed[] $args
     *
     * @dataProvider methodExpectationProvider
     */
    public function testAdapterMethods(string $methodName, $returnValue, array $args) : void
    {
        $reflectionStub = $this->createMock(BetterReflectionClassConstant::class);

        $reflectionStub->expects($this->once())
            ->method($methodName)
            ->with(...$args)
            ->will($this->returnValue($returnValue));

        $adapter = new ReflectionClassConstantAdapter($reflectionStub);
        $adapter->{$methodName}(...$args);
    }

    public function testGetDocCommentReturnsFalseWhenNoDocComment() : void
    {
        $betterReflectionClassConstant = $this->createMock(BetterReflectionClassConstant::class);
        $betterReflectionClassConstant
            ->method('getDocComment')
            ->willReturn('');

        $reflectionClassConstantAdapter = new ReflectionClassConstantAdapter($betterReflectionClassConstant);

        self::assertFalse($reflectionClassConstantAdapter->getDocComment());
    }
}
