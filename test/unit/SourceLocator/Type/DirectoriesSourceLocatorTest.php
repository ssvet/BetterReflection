<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\BetterReflection\SourceLocator\Exception\InvalidDirectory;
use PHPStan\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;
use Roave\BetterReflectionTest\Assets\DirectoryScannerAssets;
use Roave\BetterReflectionTest\Assets\DirectoryScannerAssetsFoo;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

use function array_map;
use function sort;
use function uniqid;

/**
 * @covers \PHPStan\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator
 */
class DirectoriesSourceLocatorTest extends TestCase
{
    /**
     * @var \PHPStan\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator
     */
    private $sourceLocator;

    public function setUp(): void
    {
        parent::setUp();

        $this->sourceLocator = new DirectoriesSourceLocator([
            __DIR__ . '/../../Assets/DirectoryScannerAssets',
            __DIR__ . '/../../Assets/DirectoryScannerAssetsFoo',
        ], BetterReflectionSingleton::instance()->astLocator());
    }

    public function testScanDirectoryClasses(): void
    {
        $classes = $this->sourceLocator->locateIdentifiersByType(new DefaultReflector($this->sourceLocator), new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        self::assertCount(4, $classes);

        $classNames = array_map(static function (ReflectionClass $reflectionClass) : string {
            return $reflectionClass->getName();
        }, $classes);

        sort($classNames);

        self::assertEquals(DirectoryScannerAssetsFoo\Bar\FooBar::class, $classNames[0]);
        self::assertEquals(DirectoryScannerAssetsFoo\Foo::class, $classNames[1]);
        self::assertEquals(DirectoryScannerAssets\Bar\FooBar::class, $classNames[2]);
        self::assertEquals(DirectoryScannerAssets\Foo::class, $classNames[3]);
    }

    public function testLocateIdentifier(): void
    {
        $class = $this->sourceLocator->locateIdentifier(new DefaultReflector($this->sourceLocator), new Identifier(DirectoryScannerAssets\Bar\FooBar::class, new IdentifierType(IdentifierType::IDENTIFIER_CLASS)));

        self::assertInstanceOf(ReflectionClass::class, $class);
        self::assertSame(DirectoryScannerAssets\Bar\FooBar::class, $class->getName());
    }

    /**
     * @param list<string> $directories
     *
     * @dataProvider invalidDirectoriesProvider
     */
    public function testInvalidDirectory(array $directories): void
    {
        $this->expectException(InvalidDirectory::class);

        new DirectoriesSourceLocator($directories, BetterReflectionSingleton::instance()->astLocator());
    }

    public function invalidDirectoriesProvider(): array
    {
        $validDir = __DIR__ . '/../../Assets/DirectoryScannerAssets';

        return [
            [[__DIR__ . '/' . uniqid('nonExisting', true)]],
            [[__FILE__]],
            [[$validDir, __DIR__ . '/' . uniqid('nonExisting', true)]],
            [[$validDir, __FILE__]],
        ];
    }
}
