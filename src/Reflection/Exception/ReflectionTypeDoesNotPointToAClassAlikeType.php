<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection\Exception;

use LogicException;
use PHPStan\BetterReflection\Reflection\ReflectionType;
use function get_class;
use function sprintf;

class ReflectionTypeDoesNotPointToAClassAlikeType extends LogicException
{
    public static function for(ReflectionType $type) : self
    {
        return new self(sprintf(
            'Provided %s instance does not point to a class-alike type, but to "%s"',
            get_class($type),
            $type->__toString()
        ));
    }
}
