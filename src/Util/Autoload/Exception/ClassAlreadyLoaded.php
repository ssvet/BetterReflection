<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Util\Autoload\Exception;

use LogicException;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use function sprintf;

final class ClassAlreadyLoaded extends LogicException
{
    public static function fromReflectionClass(ReflectionClass $reflectionClass) : self
    {
        return new self(sprintf(
            'Class %s has already been loaded into memory so cannot be modified',
            $reflectionClass->getName()
        ));
    }
}
