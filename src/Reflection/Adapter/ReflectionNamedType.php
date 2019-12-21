<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use ReflectionNamedType as CoreReflectionType;
use Roave\BetterReflection\Reflection\ReflectionType as BetterReflectionType;

class ReflectionNamedType extends CoreReflectionType
{
    /** @var BetterReflectionType */
    private $betterReflectionType;

    public function __construct(BetterReflectionType $betterReflectionType)
    {
        $this->betterReflectionType = $betterReflectionType;
    }

    /**
     * {@inheritDoc}
     */
    public function __toString() : string
    {
        return $this->betterReflectionType->__toString();
    }

    /**
     * {@inheritDoc}
     */
    public function allowsNull() : bool
    {
        return $this->betterReflectionType->allowsNull();
    }

    /**
     * {@inheritDoc}
     */
    public function isBuiltin() : bool
    {
        $type = (string) $this->betterReflectionType;

        if ($type === 'self' || $type === 'parent') {
            return false;
        }

        return $this->betterReflectionType->isBuiltin();
    }

    public function getName() : string
    {
        return $this->__toString();
    }
}
