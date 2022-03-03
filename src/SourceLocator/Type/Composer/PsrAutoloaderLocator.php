<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\SourceLocator\Type\Composer;

use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\Located\LocatedSource;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Psr\PsrAutoloaderMapping;
use PHPStan\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;

use function file_get_contents;
use function is_file;

final class PsrAutoloaderLocator implements SourceLocator
{
    /**
     * @var \PHPStan\BetterReflection\SourceLocator\Type\Composer\Psr\PsrAutoloaderMapping
     */
    private $mapping;
    /**
     * @var \PHPStan\BetterReflection\SourceLocator\Ast\Locator
     */
    private $astLocator;
    public function __construct(PsrAutoloaderMapping $mapping, Locator $astLocator)
    {
        $this->mapping = $mapping;
        $this->astLocator = $astLocator;
    }

    public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
    {
        foreach ($this->mapping->resolvePossibleFilePaths($identifier) as $file) {
            if (! is_file($file)) {
                continue;
            }

            try {
                return $this->astLocator->findReflection($reflector, new LocatedSource(file_get_contents($file), $identifier->getName(), $file), $identifier);
            } catch (IdentifierNotFound $exception) {
                // on purpose - autoloading is allowed to fail, and silently-failing autoloaders are normal/endorsed
            }
        }

        return null;
    }

    /**
     * Find all identifiers of a type
     *
     * @return list<Reflection>
     */
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
    {
        return (new DirectoriesSourceLocator($this->mapping->directories(), $this->astLocator))->locateIdentifiersByType($reflector, $identifierType);
    }
}
