<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionUnionType as CoreReflectionUnionType;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionUnionType as ReflectionUnionTypeAdapter;
use PHPStan\BetterReflection\Reflection\ReflectionUnionType as BetterReflectionUnionType;

use function array_combine;
use function array_map;
use function get_class_methods;

/**
 * @covers \PHPStan\BetterReflection\Reflection\Adapter\ReflectionUnionType
 */
class ReflectionUnionTypeTest extends TestCase
{
    public function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionUnionType::class);

        return array_combine($methods, array_map(static function (string $i) : array {
            return [$i];
        }, $methods));
    }

    /**
     * @dataProvider coreReflectionMethodNamesProvider
     */
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionTypeAdapterReflection = new CoreReflectionClass(ReflectionUnionTypeAdapter::class);

        self::assertTrue($reflectionTypeAdapterReflection->hasMethod($methodName));
        self::assertSame(ReflectionUnionTypeAdapter::class, $reflectionTypeAdapterReflection->getMethod($methodName)->getDeclaringClass()->getName());
    }

    public function methodExpectationProvider(): array
    {
        return [
            ['__toString', null, 'int|string', []],
            ['allowsNull', null, true, []],
            ['getTypes', null, [], []],
        ];
    }

    /**
     * @param list<mixed> $args
     *
     * @dataProvider methodExpectationProvider
     * @param mixed $returnValue
     */
    public function testAdapterMethods(string $methodName, ?string $expectedException, $returnValue, array $args): void
    {
        $reflectionStub = $this->createMock(BetterReflectionUnionType::class);

        if ($expectedException === null) {
            $reflectionStub->expects($this->once())
                ->method($methodName)
                ->with(...$args)
                ->will($this->returnValue($returnValue));
        }

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $adapter = new ReflectionUnionTypeAdapter($reflectionStub);
        $adapter->{$methodName}(...$args);
    }
}
