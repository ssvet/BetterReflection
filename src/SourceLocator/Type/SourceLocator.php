<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Reflector;

interface SourceLocator
{
    /**
     * Locate some source code.
     *
     * This method should return a LocatedSource value object or `null` if the
     * SourceLocator is unable to locate the source.
     *
     * NOTE: A SourceLocator should *NOT* throw an exception if it is unable to
     * locate the identifier, it should simply return null. If an exception is
     * thrown, it will break the Generic Reflector.
	 *
	 * @template TType of Reflection
	 * @param Identifier<TType> $identifier
	 * @return TType|null
     */
    public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection;

    /**
     * Find all identifiers of a type
     *
	 * @template TType of Reflection
	 * @param IdentifierType<TType> $identifierType
     * @return list<TType>
     */
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array;
}
