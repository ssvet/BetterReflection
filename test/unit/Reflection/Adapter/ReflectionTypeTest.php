<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use PhpParser\Node\Identifier;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionType as CoreReflectionType;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionIntersectionType as ReflectionIntersectionTypeAdapter;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionNamedType as ReflectionNamedTypeAdapter;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionType as ReflectionTypeAdapter;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionUnionType as ReflectionUnionTypeAdapter;
use PHPStan\BetterReflection\Reflection\ReflectionIntersectionType as BetterReflectionIntersectionType;
use PHPStan\BetterReflection\Reflection\ReflectionNamedType as BetterReflectionNamedType;
use PHPStan\BetterReflection\Reflection\ReflectionParameter as BetterReflectionParameter;
use PHPStan\BetterReflection\Reflection\ReflectionUnionType as BetterReflectionUnionType;
use PHPStan\BetterReflection\Reflector\Reflector;

use function array_combine;
use function array_map;
use function get_class_methods;

/**
 * @covers \PHPStan\BetterReflection\Reflection\Adapter\ReflectionType
 */
class ReflectionTypeTest extends TestCase
{
    public function coreReflectionMethodNamesProvider(): array
    {
        $methods = get_class_methods(CoreReflectionType::class);

        return array_combine($methods, array_map(static function (string $i) : array {
            return [$i];
        }, $methods));
    }

    /**
     * @dataProvider coreReflectionMethodNamesProvider
     */
    public function testCoreReflectionMethods(string $methodName): void
    {
        $reflectionTypeAdapterReflection = new CoreReflectionClass(ReflectionNamedTypeAdapter::class);

        self::assertTrue($reflectionTypeAdapterReflection->hasMethod($methodName));
        self::assertSame(ReflectionNamedTypeAdapter::class, $reflectionTypeAdapterReflection->getMethod($methodName)->getDeclaringClass()->getName());
    }

    public function testFromTypeOrNullWithNull(): void
    {
        self::assertNull(ReflectionTypeAdapter::fromTypeOrNull(null));
    }

    public function testFromTypeOrNullWithNamedType(): void
    {
        self::assertInstanceOf(ReflectionNamedTypeAdapter::class, ReflectionTypeAdapter::fromTypeOrNull($this->createMock(BetterReflectionNamedType::class)));
    }

    public function dataWillMakeNullableNamedTypeOutOfNullableUnionWithOnlyOneType(): array
    {
        return [
            ['foo', 'null'],
            ['null', 'foo'],
        ];
    }

    /**
     * @dataProvider dataWillMakeNullableNamedTypeOutOfNullableUnionWithOnlyOneType
     */
    public function testWillMakeNullableNamedTypeOutOfNullableUnionWithOnlyOneType(string $firstType, string $secondType): void
    {
        $unionType = $this->createMock(BetterReflectionUnionType::class);
        $fooType   = $this->createMock(BetterReflectionNamedType::class);
        $nullType  = $this->createMock(BetterReflectionNamedType::class);

        $fooType->method('getName')
            ->willReturn($firstType);
        $nullType->method('getName')
            ->willReturn($secondType);
        $unionType->method('getTypes')
            ->willReturn([$fooType, $nullType]);
        $unionType->method('allowsNull')
            ->willReturn(true);

        $type = ReflectionTypeAdapter::fromTypeOrNull($unionType);

        self::assertInstanceOf(ReflectionNamedTypeAdapter::class, $type);
        self::assertTrue($type->allowsNull());
        self::assertSame('foo', $type->getName());
    }

    public function testWillNotMakeNullableNamedTypeOutOfNullableUnionWithMoreTypes(): void
    {
        $unionType = $this->createMock(BetterReflectionUnionType::class);
        $fooType   = $this->createMock(BetterReflectionNamedType::class);
        $booType   = $this->createMock(BetterReflectionNamedType::class);
        $nullType  = $this->createMock(BetterReflectionNamedType::class);

        $fooType->method('getName')
            ->willReturn('foo');
        $booType->method('getName')
            ->willReturn('boo');
        $nullType->method('getName')
            ->willReturn('null');
        $unionType->method('getTypes')
            ->willReturn([$fooType, $booType, $nullType]);
        $unionType->method('allowsNull')
            ->willReturn(true);

        $type = ReflectionTypeAdapter::fromTypeOrNull($unionType);

        self::assertInstanceOf(ReflectionUnionTypeAdapter::class, $type);
    }

    public function testFromTypeOrNullWithUnionType(): void
    {
        self::assertInstanceOf(ReflectionUnionTypeAdapter::class, ReflectionTypeAdapter::fromTypeOrNull($this->createMock(BetterReflectionUnionType::class)));
    }

    public function testFromTypeOrNullWithIntersectionType(): void
    {
        self::assertInstanceOf(ReflectionIntersectionTypeAdapter::class, ReflectionTypeAdapter::fromTypeOrNull($this->createMock(BetterReflectionIntersectionType::class)));
    }

    public function testMixedAllowsNull(): void
    {
        $type = ReflectionTypeAdapter::fromTypeOrNull(new BetterReflectionNamedType($this->createMock(Reflector::class), $this->createMock(BetterReflectionParameter::class), new Identifier('mixed')));
        self::assertTrue($type->allowsNull());
    }
}
