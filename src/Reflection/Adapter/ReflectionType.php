<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection\Adapter;

use LogicException;
use PHPStan\BetterReflection\Reflection\ReflectionIntersectionType as BetterReflectionIntersectionType;
use PHPStan\BetterReflection\Reflection\ReflectionNamedType as BetterReflectionNamedType;
use PHPStan\BetterReflection\Reflection\ReflectionType as BetterReflectionType;
use PHPStan\BetterReflection\Reflection\ReflectionUnionType as BetterReflectionUnionType;
use ReflectionType as CoreReflectionType;
use function get_class;
use function sprintf;

class ReflectionType
{
    private function __construct()
    {
    }

    public static function fromReturnTypeOrNull(?BetterReflectionType $betterReflectionType) : ?CoreReflectionType
    {
        if ($betterReflectionType === null) {
            return null;
        }

        if ($betterReflectionType instanceof BetterReflectionNamedType) {
            return new ReflectionNamedType($betterReflectionType);
        }

        if ($betterReflectionType instanceof BetterReflectionUnionType) {
            return new ReflectionUnionType($betterReflectionType);
        }

        if ($betterReflectionType instanceof BetterReflectionIntersectionType) {
            return new ReflectionIntersectionType($betterReflectionType);
        }

        throw new LogicException(sprintf('%s is not supported.', get_class($betterReflectionType)));
    }
}
