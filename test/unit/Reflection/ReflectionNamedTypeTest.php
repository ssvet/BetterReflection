<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use Generator;
use LogicException;
use PhpParser\Node\Identifier;
use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\Reflection\ReflectionEnum;
use PHPStan\BetterReflection\Reflection\ReflectionNamedType;
use PHPStan\BetterReflection\Reflection\ReflectionParameter;
use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

use function sprintf;

/**
 * @covers \PHPStan\BetterReflection\Reflection\ReflectionNamedType
 */
class ReflectionNamedTypeTest extends TestCase
{
    /**
     * @var \PHPStan\BetterReflection\Reflector\Reflector
     */
    private $reflector;
    /**
     * @var \PHPStan\BetterReflection\Reflection\ReflectionParameter
     */
    private $owner;
    /**
     * @var \PHPStan\BetterReflection\SourceLocator\Ast\Locator
     */
    private $astLocator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reflector  = $this->createMock(Reflector::class);
        $this->owner      = $this->createMock(ReflectionParameter::class);
        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
    }

    public function testCreateFromNode(): void
    {
        $typeInfo = $this->createType('string');

        self::assertInstanceOf(ReflectionNamedType::class, $typeInfo);
        self::assertSame('string', $typeInfo->getName());
        self::assertSame($this->owner, $typeInfo->getOwner());
    }

    public function testAllowsNull(): void
    {
        $noNullType = $this->createType('string');
        self::assertFalse($noNullType->allowsNull());
    }

    public function dataMixedAllowsNull(): array
    {
        return [
            ['mixed'],
            ['MIXED'],
        ];
    }

    /**
     * @dataProvider dataMixedAllowsNull
     */
    public function testMixedAllowsNull(string $mixedType): void
    {
        $noNullType = $this->createType($mixedType);
        self::assertTrue($noNullType->allowsNull());
    }

    public function isBuildinProvider(): Generator
    {
        yield ['string'];
        yield ['StRiNg'];
        yield ['int'];
        yield ['INT'];
        yield ['array'];
        yield ['aRRay'];
        yield ['object'];
        yield ['obJEct'];
        yield ['iterable'];
        yield ['iteRABle'];
        yield ['mixed'];
        yield ['MIXED'];
        yield ['never'];
        yield ['NEVER'];
        yield ['false'];
        yield ['FALSE'];
    }

    /**
     * @dataProvider isBuildinProvider
     */
    public function testIsBuiltin(string $type): void
    {
        $reflectionType = $this->createType($type);

        self::assertInstanceOf(ReflectionNamedType::class, $reflectionType);
        self::assertTrue($reflectionType->isBuiltin());
    }

    public function isNotBuildinProvider(): Generator
    {
        yield ['foo'];
        yield ['\foo'];
    }

    /**
     * @dataProvider isNotBuildinProvider
     */
    public function testIsNotBuiltin(string $type): void
    {
        $reflectionType = $this->createType($type);

        self::assertInstanceOf(ReflectionNamedType::class, $reflectionType);
        self::assertFalse($reflectionType->isBuiltin());
    }

    public function testImplicitCastToString(): void
    {
        self::assertSame('int', (string) $this->createType('int'));
        self::assertSame('string', (string) $this->createType('string'));
        self::assertSame('array', (string) $this->createType('array'));
        self::assertSame('callable', (string) $this->createType('callable'));
        self::assertSame('bool', (string) $this->createType('bool'));
        self::assertSame('float', (string) $this->createType('float'));
        self::assertSame('void', (string) $this->createType('void'));
        self::assertSame('object', (string) $this->createType('object'));
        self::assertSame('iterable', (string) $this->createType('iterable'));
        self::assertSame('mixed', (string) $this->createType('mixed'));
        self::assertSame('never', (string) $this->createType('never'));

        self::assertSame('Foo\Bar\Baz', (string) $this->createType('Foo\Bar\Baz'));
        self::assertSame('\Foo\Bar\Baz', (string) $this->createType('\Foo\Bar\Baz'));
    }

    private function createType(string $type): ReflectionNamedType
    {
        return new ReflectionNamedType($this->reflector, $this->owner, new Identifier($type));
    }

    public function testGetClassFromPropertyType(): void
    {
        $php = '<?php
            class Foo {
                public self $property;
            }
        ';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));

        $classReflection    = $reflector->reflectClass('Foo');
        $propertyReflection = $classReflection->getProperty('property');
        $typeReflection     = $propertyReflection->getType();
        $class              = $typeReflection->getClass();

        self::assertSame('Foo', $class->getName());
    }

    public function testGetClassFromFunctionReturnType(): void
    {
        $php = '<?php
            class Foo {}
            function getFoo(): Foo {}
        ';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));

        $functionReflection = $reflector->reflectFunction('getFoo');
        $typeReflection     = $functionReflection->getReturnType();
        $class              = $typeReflection->getClass();

        self::assertSame('Foo', $class->getName());
    }

    public function testGetClassFromMethodReturnType(): void
    {
        $php = '<?php
            abstract class Foo {
                public function method(): self;
            }
        ';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));

        $classReflection  = $reflector->reflectClass('Foo');
        $methodReflection = $classReflection->getMethod('method');
        $typeReflection   = $methodReflection->getReturnType();
        $class            = $typeReflection->getClass();

        self::assertSame('Foo', $class->getName());
    }

    public function testGetClassFromMethodParameterType(): void
    {
        $php = '<?php
            abstract class Foo {
                public function method(self $parameter);
            }
        ';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));

        $classReflection     = $reflector->reflectClass('Foo');
        $methodReflection    = $classReflection->getMethod('method');
        $parameterReflection = $methodReflection->getParameter('parameter');
        $typeReflection      = $parameterReflection->getType();
        $class               = $typeReflection->getClass();

        self::assertSame('Foo', $class->getName());
    }

    public function testGetClassFromEnumBackingTypeThrowsException(): void
    {
        $php = '<?php
            enum Foo: int {}
        ';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));

        $enumReflection = $reflector->reflectClass('Foo');

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);

        $typeReflection = $enumReflection->getBackingType();

        self::expectException(LogicException::class);
        $typeReflection->getClass();
    }

    public function testGetClassFromFunctionWithSelfReturnTypeThrowsException(): void
    {
        $php = '<?php
            function getSelf(): self {}
        ';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));

        $functionReflection = $reflector->reflectFunction('getSelf');
        $typeReflection     = $functionReflection->getReturnType();

        self::expectException(LogicException::class);
        $typeReflection->getClass();
    }

    public function testGetClassFromParameterWithSelfTypeInFunctionThrowsException(): void
    {
        $php = '<?php
            function withSelf(self $parameter) {}
        ';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));

        $functionReflection  = $reflector->reflectFunction('withSelf');
        $parameterReflection = $functionReflection->getParameter('parameter');
        $typeReflection      = $parameterReflection->getType();

        self::expectException(LogicException::class);
        $typeReflection->getClass();
    }

    public function dataGetClassWithSelfOrStatic(): array
    {
        return [
            ['ParentClass', 'self', 'ParentClass'],
            ['ParentClass', 'SELF', 'ParentClass'],
            ['ParentClass', 'static', 'ParentClass'],
            ['ParentClass', 'STATIC', 'ParentClass'],
            ['ClassWithExtend', 'self', 'ParentClass'],
            ['ClassWithExtend', 'sElF', 'ParentClass'],
            ['ClassWithExtend', 'static', 'ClassWithExtend'],
            ['ClassWithExtend', 'StAtIc', 'ClassWithExtend'],
        ];
    }

    /**
     * @dataProvider dataGetClassWithSelfOrStatic
     */
    public function testGetClassWithSelfOrStatic(string $classNameToReflect, string $type, string $typeClassName): void
    {
        $php = sprintf('<?php
            abstract class ParentClass {
                public function method(): %s;
            }

            class ClassWithExtend extends ParentClass {}
        ', $type);

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));

        $classReflection  = $reflector->reflectClass($classNameToReflect);
        $methodReflection = $classReflection->getMethod('method');

        $typeReflection = $methodReflection->getReturnType();
        $class          = $typeReflection->getClass();

        self::assertSame($typeClassName, $class->getName());
    }

    public function dataGetClassWithParent(): array
    {
        return [
            ['parent'],
            ['PARENT'],
        ];
    }

    /**
     * @dataProvider dataGetClassWithParent
     */
    public function testGetClassWithParent(string $parentType): void
    {
        $php = sprintf('<?php
            abstract class Foo {
            }

            class Boo extends Foo {
                public function method(): %s {}
            }
        ', $parentType);

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));

        $classReflection  = $reflector->reflectClass('Boo');
        $methodReflection = $classReflection->getMethod('method');
        $typeReflection   = $methodReflection->getReturnType();
        $class            = $typeReflection->getClass();

        self::assertSame('Foo', $class->getName());
    }

    public function testGetClassThrowsExceptionForBuiltinType(): void
    {
        $php = '<?php
            abstract class Foo {
                public function method(): int;
            }
        ';

        $reflector = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));

        $classReflection  = $reflector->reflectClass('Foo');
        $methodReflection = $classReflection->getMethod('method');
        $typeReflection   = $methodReflection->getReturnType();

        self::expectException(LogicException::class);
        $typeReflection->getClass();
    }
}
