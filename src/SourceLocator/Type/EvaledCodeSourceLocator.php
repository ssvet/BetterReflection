<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\SourceLocator\Type;

use InvalidArgumentException;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use PHPStan\BetterReflection\SourceLocator\Located\EvaledLocatedSource;
use PHPStan\BetterReflection\SourceLocator\Located\LocatedSource;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use ReflectionClass;
use function class_exists;
use function file_exists;
use function interface_exists;
use function trait_exists;

final class EvaledCodeSourceLocator extends AbstractSourceLocator
{
    /** @var SourceStubber */
    private $stubber;

    public function __construct(Locator $astLocator, SourceStubber $stubber)
    {
        parent::__construct($astLocator);

        $this->stubber = $stubber;
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     * @throws InvalidFileLocation
     */
    protected function createLocatedSource(Identifier $identifier) : ?LocatedSource
    {
        $classReflection = $this->getInternalReflectionClass($identifier);

        if ($classReflection === null) {
            return null;
        }

        $stubData = $this->stubber->generateClassStub($classReflection->getName());

        if ($stubData === null) {
            return null;
        }

        return new EvaledLocatedSource($stubData->getStub());
    }

    private function getInternalReflectionClass(Identifier $identifier) : ?ReflectionClass
    {
        if (! $identifier->isClass()) {
            return null;
        }

        $name = $identifier->getName();

        if (! (class_exists($name, false) || interface_exists($name, false) || trait_exists($name, false))) {
            return null; // not an available internal class
        }

        $reflection = new ReflectionClass($name);
        $sourceFile = $reflection->getFileName();

        return $sourceFile && file_exists($sourceFile)
            ? null : $reflection;
    }
}
