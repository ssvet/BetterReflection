<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection\Adapter;

use PHPStan\BetterReflection\Reflection\ReflectionIntersectionType as BetterReflectionType;
use ReflectionIntersectionType as CoreReflectionIntersectionType;
use function array_map;

class ReflectionIntersectionType extends CoreReflectionIntersectionType
{
    /** @var BetterReflectionType */
    private $betterReflectionType;

    public function __construct(BetterReflectionType $betterReflectionType)
    {
        $this->betterReflectionType = $betterReflectionType;
    }

    public function __toString() : string
    {
        return $this->betterReflectionType->__toString();
    }

    public function allowsNull() : bool
    {
        return $this->betterReflectionType->allowsNull();
    }

    /**
     * @return \ReflectionType[]
     */
    public function getTypes() : array
    {
        return array_map(static function (\PHPStan\BetterReflection\Reflection\ReflectionType $type) : \ReflectionType {
            return ReflectionType::fromReturnTypeOrNull($type);
        }, $this->betterReflectionType->getTypes());
    }
}
