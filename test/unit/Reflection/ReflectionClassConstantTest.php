<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use PHPUnit\Framework\TestCase;
use ReflectionClassConstant as CoreReflectionClassConstant;
use PHPStan\BetterReflection\Reflection\ReflectionClassConstant;
use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\Type\ComposerSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\Fixture\Attr;
use Roave\BetterReflectionTest\Fixture\ClassWithAttributes;
use Roave\BetterReflectionTest\Fixture\ExampleClass;

use function sprintf;

class ReflectionClassConstantTest extends TestCase
{
    /**
     * @var \PHPStan\BetterReflection\SourceLocator\Ast\Locator
     */
    private $astLocator;

    public function setUp(): void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
    }

    private function getComposerLocator(): ComposerSourceLocator
    {
        return new ComposerSourceLocator(require __DIR__ . '/../../../vendor/autoload.php', $this->astLocator);
    }

    private function getExampleConstant(string $name): ?ReflectionClassConstant
    {
        $reflector = new DefaultReflector($this->getComposerLocator());
        $classInfo = $reflector->reflectClass(ExampleClass::class);

        return $classInfo->getReflectionConstant($name);
    }

    public function testDefaultVisibility(): void
    {
        $const = $this->getExampleConstant('MY_CONST_1');
        self::assertTrue($const->isPublic());
    }

    public function testPublicVisibility(): void
    {
        $const = $this->getExampleConstant('MY_CONST_3');
        self::assertTrue($const->isPublic());
    }

    public function testProtectedVisibility(): void
    {
        $const = $this->getExampleConstant('MY_CONST_4');
        self::assertTrue($const->isProtected());
    }

    public function testPrivateVisibility(): void
    {
        $const = $this->getExampleConstant('MY_CONST_5');
        self::assertTrue($const->isPrivate());
    }

    public function testFinal(): void
    {
        $const = $this->getExampleConstant('MY_CONST_6');
        self::assertTrue($const->isFinal());
    }

    public function testToString(): void
    {
        self::assertSame("Constant [ public integer MY_CONST_1 ] { 123 }\n", (string) $this->getExampleConstant('MY_CONST_1'));
    }

    /**
     * @dataProvider getModifiersProvider
     */
    public function testGetModifiers(string $const, int $expected): void
    {
        self::assertSame($expected, $this->getExampleConstant($const)->getModifiers());
    }

    public function getModifiersProvider(): array
    {
        return [
            ['MY_CONST_1', CoreReflectionClassConstant::IS_PUBLIC],
            ['MY_CONST_2', CoreReflectionClassConstant::IS_PUBLIC],
            ['MY_CONST_3', CoreReflectionClassConstant::IS_PUBLIC],
            ['MY_CONST_4', CoreReflectionClassConstant::IS_PROTECTED],
            ['MY_CONST_5', CoreReflectionClassConstant::IS_PRIVATE],
            ['MY_CONST_6', CoreReflectionClassConstant::IS_PUBLIC | ReflectionClassConstant::IS_FINAL],
        ];
    }

    public function testGetDocComment(): void
    {
        $const = $this->getExampleConstant('MY_CONST_2');
        self::assertStringContainsString('This comment for constant should be used.', $const->getDocComment());
    }

    public function testGetDocCommentReturnsEmptyStringWithNoComment(): void
    {
        $const = $this->getExampleConstant('MY_CONST_1');
        self::assertSame('', $const->getDocComment());
    }

    public function testGetDeclaringClass(): void
    {
        $reflector = new DefaultReflector($this->getComposerLocator());
        $classInfo = $reflector->reflectClass(ExampleClass::class);
        $const     = $classInfo->getReflectionConstant('MY_CONST_1');
        self::assertSame($classInfo, $const->getDeclaringClass());
    }

    /**
     * @dataProvider startEndLineProvider
     */
    public function testStartEndLine(string $php, int $startLine, int $endLine): void
    {
        $reflector       = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $classReflection = $reflector->reflectClass('\T');
        $constReflection = $classReflection->getReflectionConstant('TEST');
        self::assertEquals($startLine, $constReflection->getStartLine());
        self::assertEquals($endLine, $constReflection->getEndLine());
    }

    public function startEndLineProvider(): array
    {
        return [
            ["<?php\nclass T {\nconst TEST = 1; }", 3, 3],
            ["<?php\n\nclass T {\nconst TEST = 1; }", 4, 4],
            ["<?php\nclass T {\nconst TEST = \n1; }", 3, 4],
            ["<?php\nclass T {\nconst \nTEST = 1; }", 3, 4],
        ];
    }

    public function columnsProvider(): array
    {
        return [
            ["<?php\n\nclass T {\nconst TEST = 1;}", 1, 15],
            ["<?php\n\n    class T {\n        const TEST = 1;}", 9, 23],
            ['<?php class T {const TEST = 1;}', 16, 30],
        ];
    }

    /**
     * @dataProvider columnsProvider
     */
    public function testGetStartColumnAndEndColumn(string $php, int $startColumn, int $endColumn): void
    {
        $reflector          = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $classReflection    = $reflector->reflectClass('T');
        $constantReflection = $classReflection->getReflectionConstant('TEST');

        self::assertEquals($startColumn, $constantReflection->getStartColumn());
        self::assertEquals($endColumn, $constantReflection->getEndColumn());
    }

    public function deprecatedDocCommentProvider(): array
    {
        return [
            [
                '/**
                  * @deprecated since 8.0
                  */',
                true,
            ],
            [
                '/**
                  * @deprecated
                  */',
                true,
            ],
            [
                '',
                false,
            ],
        ];
    }

    /**
     * @dataProvider deprecatedDocCommentProvider
     */
    public function testIsDeprecated(string $docComment, bool $isDeprecated): void
    {
        $php = sprintf('<?php
        class Foo {
            %s
            public const FOO = "foo";
        }', $docComment);

        $reflector          = new DefaultReflector(new StringSourceLocator($php, BetterReflectionSingleton::instance()->astLocator()));
        $classReflection    = $reflector->reflectClass('Foo');
        $constantReflection = $classReflection->getReflectionConstant('FOO');

        self::assertSame($isDeprecated, $constantReflection->isDeprecated());
    }

    public function testGetAttributesWithoutAttributes(): void
    {
        $reflector          = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php', $this->astLocator));
        $classReflection    = $reflector->reflectClass(ExampleClass::class);
        $constantReflection = $classReflection->getReflectionConstant('MY_CONST_1');
        $attributes         = $constantReflection->getAttributes();

        self::assertCount(0, $attributes);
    }

    public function testGetAttributesWithAttributes(): void
    {
        $reflector          = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $classReflection    = $reflector->reflectClass(ClassWithAttributes::class);
        $constantReflection = $classReflection->getReflectionConstant('CONSTANT_WITH_ATTRIBUTES');
        $attributes         = $constantReflection->getAttributes();

        self::assertCount(2, $attributes);
    }

    public function testGetAttributesByName(): void
    {
        $reflector          = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $classReflection    = $reflector->reflectClass(ClassWithAttributes::class);
        $constantReflection = $classReflection->getReflectionConstant('CONSTANT_WITH_ATTRIBUTES');
        $attributes         = $constantReflection->getAttributesByName(Attr::class);

        self::assertCount(1, $attributes);
    }

    public function testGetAttributesByInstance(): void
    {
        $reflector          = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Attributes.php', $this->astLocator));
        $classReflection    = $reflector->reflectClass(ClassWithAttributes::class);
        $constantReflection = $classReflection->getReflectionConstant('CONSTANT_WITH_ATTRIBUTES');
        $attributes         = $constantReflection->getAttributesByInstance(Attr::class);

        self::assertCount(2, $attributes);
    }
}
