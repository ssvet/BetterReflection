<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use PhpParser\Parser;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Exception\EvaledAnonymousClassCannotBeLocated;
use Roave\BetterReflection\SourceLocator\Exception\NoAnonymousClassOnLine;
use Roave\BetterReflection\SourceLocator\Exception\TwoAnonymousClassesOnSameLine;
use Roave\BetterReflection\SourceLocator\Type\AnonymousClassObjectSourceLocator;
use Roave\BetterReflection\Util\FileHelper;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use stdClass;

use function realpath;
use function sprintf;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Type\AnonymousClassObjectSourceLocator
 */
class AnonymousClassObjectSourceLocatorTest extends TestCase
{
    /**
     * @var \PhpParser\Parser
     */
    private $parser;

    /**
     * @var \Roave\BetterReflection\Reflector\Reflector
     */
    private $reflector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser    = BetterReflectionSingleton::instance()->phpParser();
        $this->reflector = $this->createMock(Reflector::class);
    }

    public function anonymousClassInstancesProvider(): array
    {
        $fileWithClasses                = FileHelper::normalizeWindowsPath(realpath(__DIR__ . '/../../Fixture/AnonymousClassInstances.php'));
        $fileWithClassWithNestedClasses = FileHelper::normalizeWindowsPath(realpath(__DIR__ . '/../../Fixture/NestedAnonymousClassInstances.php'));

        $classes                = require $fileWithClasses;
        $classWithNestedClasses = require $fileWithClassWithNestedClasses;

        return [
            [$classes[0], $fileWithClasses, 3, 9],
            [$classes[1], $fileWithClasses, 11, 17],
            [$classWithNestedClasses, $fileWithClassWithNestedClasses, 3, 13],
            [$classWithNestedClasses->getWrapped(0), $fileWithClassWithNestedClasses, 8, 8],
            [$classWithNestedClasses->getWrapped(1), $fileWithClassWithNestedClasses, 11, 11],
        ];
    }

    /**
     * @dataProvider anonymousClassInstancesProvider
     * @param object $class
     */
    public function testLocateIdentifier($class, string $file, int $startLine, int $endLine): void
    {
        $reflection = (new AnonymousClassObjectSourceLocator($class, $this->parser))->locateIdentifier($this->reflector, new Identifier(get_class($class), new IdentifierType(IdentifierType::IDENTIFIER_CLASS)));

        self::assertInstanceOf(ReflectionClass::class, $reflection);
        self::assertTrue($reflection->isAnonymous());
        self::assertStringStartsWith(ReflectionClass::ANONYMOUS_CLASS_NAME_PREFIX, $reflection->getShortName());
        self::assertSame($file, $reflection->getFileName());
        self::assertSame($startLine, $reflection->getStartLine());
        self::assertSame($endLine, $reflection->getEndLine());
    }

    public function testCannotLocateNonAnonymousClass(): void
    {
        $class = new CoreReflectionClass(stdClass::class);

        $reflection = (new AnonymousClassObjectSourceLocator($class, $this->parser))->locateIdentifier($this->reflector, new Identifier(get_class($class), new IdentifierType(IdentifierType::IDENTIFIER_CLASS)));

        self::assertNull($reflection);
    }

    public function testLocateIdentifierWithFunctionIdentifier(): void
    {
        $anonymousClass = new class {
        };

        $reflection = (new AnonymousClassObjectSourceLocator($anonymousClass, $this->parser))->locateIdentifier($this->reflector, new Identifier('foo', new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)));

        self::assertNull($reflection);
    }

    /**
     * @dataProvider anonymousClassInstancesProvider
     * @param object $class
     */
    public function testLocateIdentifiersByType($class, string $file, int $startLine, int $endLine): void
    {
        /** @var list<ReflectionClass> $reflections */
        $reflections = (new AnonymousClassObjectSourceLocator($class, $this->parser))->locateIdentifiersByType($this->reflector, new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        self::assertCount(1, $reflections);
        self::assertArrayHasKey(0, $reflections);

        self::assertTrue($reflections[0]->isAnonymous());
        self::assertStringStartsWith(ReflectionClass::ANONYMOUS_CLASS_NAME_PREFIX, $reflections[0]->getShortName());
        self::assertSame($file, $reflections[0]->getFileName());
        self::assertSame($startLine, $reflections[0]->getStartLine());
        self::assertSame($endLine, $reflections[0]->getEndLine());
    }

    public function testLocateIdentifiersByTypeWithFunctionIdentifier(): void
    {
        $anonymousClass = new class {
        };

        /** @var list<ReflectionClass> $reflections */
        $reflections = (new AnonymousClassObjectSourceLocator($anonymousClass, $this->parser))->locateIdentifiersByType($this->reflector, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION));

        self::assertCount(0, $reflections);
    }

    public function testExceptionIfAnonymousClassNotFoundOnExpectedLine(): void
    {
        $anonymousClass = new class {
        };

        $sourceLocator = new AnonymousClassObjectSourceLocator($anonymousClass, $this->parser);

        $sourceLocatorReflection = new CoreReflectionClass($sourceLocator);

        $coreReflectionPropertyMock = $this->createMock(CoreReflectionClass::class);
        $coreReflectionPropertyMock
            ->method('isAnonymous')
            ->willReturn(true);
        $coreReflectionPropertyMock
            ->method('getFileName')
            ->willReturn(__FILE__);
        $coreReflectionPropertyMock
            ->method('getStartLine')
            ->willReturn(0);

        $coreReflectionPropertyInSourceLocatatorReflection = $sourceLocatorReflection->getProperty('coreClassReflection');
        $coreReflectionPropertyInSourceLocatatorReflection->setAccessible(true);
        $coreReflectionPropertyInSourceLocatatorReflection->setValue($sourceLocator, $coreReflectionPropertyMock);

        self::expectException(NoAnonymousClassOnLine::class);

        $sourceLocator->locateIdentifiersByType($this->reflector, new IdentifierType(IdentifierType::IDENTIFIER_CLASS));
    }

    public function exceptionIfTwoAnonymousClassesOnSameLineProvider(): array
    {
        $file    = FileHelper::normalizeWindowsPath(realpath(__DIR__ . '/../../Fixture/AnonymousClassInstancesOnSameLine.php'));
        $classes = require $file;

        return [
            [$file, $classes[0]],
            [$file, $classes[1]],
        ];
    }

    /**
     * @dataProvider exceptionIfTwoAnonymousClassesOnSameLineProvider
     * @param object $class
     */
    public function testExceptionIfTwoAnonymousClassesOnSameLine(string $file, $class): void
    {
        $this->expectException(TwoAnonymousClassesOnSameLine::class);
        $this->expectExceptionMessage(sprintf('Two anonymous classes on line 3 in %s', $file));

        (new AnonymousClassObjectSourceLocator($class, $this->parser))->locateIdentifier($this->reflector, new Identifier(get_class($class), new IdentifierType(IdentifierType::IDENTIFIER_CLASS)));
    }

    public function nestedAnonymousClassInstancesProvider(): array
    {
        $class = require __DIR__ . '/../../Fixture/NestedAnonymousClassInstances.php';

        return [
            [$class, 3, 13],
            [$class->getWrapped(0), 8, 8],
            [$class->getWrapped(1), 11, 11],
        ];
    }

    public function testExceptionIfEvaledAnonymousClass(): void
    {
        $class = require __DIR__ . '/../../Fixture/EvaledAnonymousClassInstance.php';

        $this->expectException(EvaledAnonymousClassCannotBeLocated::class);

        (new AnonymousClassObjectSourceLocator($class, $this->parser))->locateIdentifier($this->reflector, new Identifier(get_class($class), new IdentifierType(IdentifierType::IDENTIFIER_CLASS)));
    }
}
