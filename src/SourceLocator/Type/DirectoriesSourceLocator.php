<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\SourceLocator\Type;

use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\Exception\InvalidDirectory;
use PHPStan\BetterReflection\SourceLocator\Exception\InvalidFileInfo;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use function array_map;
use function array_values;
use function is_dir;
use function is_string;

/**
 * This source locator recursively loads all php files in an entire directory or multiple directories.
 */
class DirectoriesSourceLocator implements SourceLocator
{
    /** @var AggregateSourceLocator */
    private $aggregateSourceLocator;

    /**
     * @param string[] $directories directories to scan
     *
     * @throws InvalidDirectory
     * @throws InvalidFileInfo
     */
    public function __construct(array $directories, Locator $astLocator)
    {
        $this->aggregateSourceLocator = new AggregateSourceLocator(array_values(array_map(
            static function ($directory) use ($astLocator) : FileIteratorSourceLocator {
                if (! is_string($directory)) {
                    throw InvalidDirectory::fromNonStringValue($directory);
                }

                if (! is_dir($directory)) {
                    throw InvalidDirectory::fromNonDirectory($directory);
                }

                return new FileIteratorSourceLocator(
                    new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                        $directory,
                        RecursiveDirectoryIterator::SKIP_DOTS
                    )),
                    $astLocator
                );
            },
            $directories
        )));
    }

    public function locateIdentifier(Reflector $reflector, Identifier $identifier) : ?Reflection
    {
        return $this->aggregateSourceLocator->locateIdentifier($reflector, $identifier);
    }

    /**
     * {@inheritDoc}
     */
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType) : array
    {
        return $this->aggregateSourceLocator->locateIdentifiersByType($reflector, $identifierType);
    }
}
