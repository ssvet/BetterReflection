<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type\Composer\Psr;

use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Psr\Psr0Mapping;

/**
 * @covers \PHPStan\BetterReflection\SourceLocator\Type\Composer\Psr\Psr0Mapping
 */
class Psr0MappingTest extends TestCase
{
    /**
     * @param list<array<string, list<string>>> $mappings
     * @param list<string>                      $expectedDirectories
     *
     * @dataProvider mappings
     */
    public function testExpectedDirectories(array $mappings, array $expectedDirectories): void
    {
        self::assertEquals($expectedDirectories, Psr0Mapping::fromArrayMappings($mappings)->directories());
    }

    /**
     * @param list<array<string, list<string>>> $mappings
     *
     * @dataProvider mappings
     */
    public function testIdempotentConstructor(array $mappings): void
    {
        self::assertEquals(Psr0Mapping::fromArrayMappings($mappings), Psr0Mapping::fromArrayMappings($mappings));
    }

    /** @return array<string, list<array<string, list<string>>>> */
    public function mappings(): array
    {
        return [
            'one directory, one prefix'                  => [
                ['foo' => [__DIR__]],
                [__DIR__],
            ],
            'two directories, one prefix'                => [
                ['foo' => [__DIR__, __DIR__ . '/../..']],
                [__DIR__, __DIR__ . '/../..'],
            ],
            'two directories, one duplicate, one prefix' => [
                ['foo' => [__DIR__, __DIR__, __DIR__ . '/../..']],
                [__DIR__, __DIR__ . '/../..'],
            ],
            'two directories, two prefixes'              => [
                [
                    'foo' => [__DIR__],
                    'bar' => [__DIR__ . '/../..'],
                ],
                [__DIR__, __DIR__ . '/../..'],
            ],
            'trailing slash in directory is trimmed'     => [
                ['foo' => [__DIR__ . '/']],
                [__DIR__],
            ],
        ];
    }

    /**
     * @param list<array<string, list<string>>> $mappings
     * @param list<string>                      $expectedFiles
     *
     * @dataProvider classLookupMappings
     */
    public function testClassLookups(array $mappings, Identifier $identifier, array $expectedFiles): void
    {
        self::assertEquals($expectedFiles, Psr0Mapping::fromArrayMappings($mappings)->resolvePossibleFilePaths($identifier));
    }

    /** @return array<string, list<array<string, list<string>>>> */
    public function classLookupMappings(): array
    {
        return [
            'empty mappings, no match'                                          => [
                [],
                new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
                [],
            ],
            'one mapping, no match for function identifier'                     => [
                ['Foo\\' => [__DIR__]],
                new Identifier('Foo\\Bar', new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)),
                [],
            ],
            'one mapping, match'                                                => [
                ['Foo\\' => [__DIR__]],
                new Identifier('Foo\\Bar', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
                [__DIR__ . '/Foo/Bar.php'],
            ],
            'one mapping, no match with underscore prefix and namespaced class' => [
                ['Foo_' => [__DIR__]],
                new Identifier('Foo\\Bar', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
                [],
            ],
            'one mapping, match with underscore replacement'                    => [
                ['Foo_' => [__DIR__]],
                new Identifier('Foo_Bar', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
                [__DIR__ . '/Foo/Bar.php'],
            ],
            'trailing and leading slash in mapping is trimmed'                  => [
                ['Foo' => [__DIR__ . '/']],
                new Identifier('Foo\\Bar', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
                [__DIR__ . '/Foo/Bar.php'],
            ],
            'one mapping, match if class === prefix'                            => [
                ['Foo' => [__DIR__]],
                new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
                [__DIR__ . '/Foo.php'],
            ],
            'multiple mappings, match when class !== prefix'                    => [
                [
                    'Foo_Baz' => [__DIR__ . '/../..'],
                    'Foo'     => [__DIR__ . '/..'],
                ],
                new Identifier('Foo_Bar', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
                [__DIR__ . '/../Foo/Bar.php'],
            ],
        ];
    }
}
