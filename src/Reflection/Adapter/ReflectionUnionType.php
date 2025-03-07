<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection\Adapter;

use ReflectionUnionType as CoreReflectionUnionType;
use PHPStan\BetterReflection\Reflection\ReflectionIntersectionType as BetterReflectionIntersectionType;
use PHPStan\BetterReflection\Reflection\ReflectionNamedType as BetterReflectionNamedType;
use PHPStan\BetterReflection\Reflection\ReflectionUnionType as BetterReflectionUnionType;

use function array_filter;
use function array_map;

final class ReflectionUnionType extends CoreReflectionUnionType
{
    /**
     * @var BetterReflectionUnionType
     */
    private $betterReflectionType;
    public function __construct(BetterReflectionUnionType $betterReflectionType)
    {
        $this->betterReflectionType = $betterReflectionType;
    }

    /**
     * @return array<ReflectionNamedType>
     */
    public function getTypes(): array
    {
        return array_filter(array_map(static function ($type) {
            return ReflectionType::fromTypeOrNull($type);
        }, $this->betterReflectionType->getTypes()), static function ($type) : bool {
            return $type instanceof ReflectionNamedType;
        });
    }

    public function __toString(): string
    {
        return $this->betterReflectionType->__toString();
    }

    public function allowsNull(): bool
    {
        return $this->betterReflectionType->allowsNull();
    }
}
