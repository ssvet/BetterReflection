<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection;

use PhpParser\Node\IntersectionType;
use function array_map;
use function implode;

class ReflectionIntersectionType extends ReflectionType
{
    /** @var ReflectionType[] */
    private $types;

    public function __construct(IntersectionType $type, bool $allowsNull)
    {
        parent::__construct($allowsNull);
        $this->types = array_map(static function ($type) : ReflectionType {
            return ReflectionType::createFromTypeAndReflector($type);
        }, $type->types);
    }

    /**
     * @return ReflectionType[]
     */
    public function getTypes() : array
    {
        return $this->types;
    }

    public function __toString() : string
    {
        return implode('&', array_map(static function (ReflectionType $type) : string {
            return (string) $type;
        }, $this->types));
    }
}
