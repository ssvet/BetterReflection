<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection\StringCast;

use PHPStan\BetterReflection\Reflection\ReflectionClassConstant;

use function gettype;
use function is_array;
use function sprintf;

/**
 * @internal
 */
final class ReflectionClassConstantStringCast
{
    public static function toString(ReflectionClassConstant $constantReflection): string
    {
        /** @psalm-var scalar|array<scalar> $value */
        $value = $constantReflection->getValue();

        return sprintf("Constant [ %s%s %s %s ] { %s }\n", $constantReflection->isFinal() ? 'final ' : '', self::visibilityToString($constantReflection), gettype($value), $constantReflection->getName(), is_array($value) ? 'Array' : (string) $value);
    }

    private static function visibilityToString(ReflectionClassConstant $constantReflection): string
    {
        if ($constantReflection->isProtected()) {
            return 'protected';
        }

        if ($constantReflection->isPrivate()) {
            return 'private';
        }

        return 'public';
    }
}
