<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection\StringCast;

use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionClassConstant;
use PHPStan\BetterReflection\Reflection\ReflectionEnum;
use PHPStan\BetterReflection\Reflection\ReflectionEnumCase;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;
use PHPStan\BetterReflection\Reflection\ReflectionObject;
use PHPStan\BetterReflection\Reflection\ReflectionProperty;

use function array_filter;
use function array_map;
use function assert;
use function count;
use function implode;
use function is_string;
use function preg_replace;
use function sprintf;
use function str_repeat;
use function strtolower;
use function trim;

/**
 * @internal
 */
final class ReflectionClassStringCast
{
    public static function toString(ReflectionClass $classReflection): string
    {
        $isObject = $classReflection instanceof ReflectionObject;

        $format  = "%s [ <%s> %s%s%s %s%s%s ] {\n";
        $format .= "%s\n";
        $format .= "  - Constants [%d] {%s\n  }\n\n";
        $format .= "  - Static properties [%d] {%s\n  }\n\n";
        $format .= "  - Static methods [%d] {%s\n  }\n\n";
        $format .= "  - Properties [%d] {%s\n  }\n\n";
        $format .= ($isObject ? "  - Dynamic properties [%d] {%s\n  }\n\n" : '%s%s');
        $format .= "  - Methods [%d] {%s\n  }\n";
        $format .= "}\n";

        $type = self::typeToString($classReflection);

        $constants         = $classReflection->getReflectionConstants();
        $enumCases         = $classReflection instanceof ReflectionEnum ? $classReflection->getCases() : [];
        $staticProperties  = self::getStaticProperties($classReflection);
        $staticMethods     = self::getStaticMethods($classReflection);
        $defaultProperties = self::getDefaultProperties($classReflection);
        $dynamicProperties = self::getDynamicProperties($classReflection);
        $methods           = self::getMethods($classReflection);

        return sprintf($format, $isObject ? 'Object of class' : $type, self::sourceToString($classReflection), $classReflection->isFinal() ? 'final ' : '', $classReflection->isAbstract() ? 'abstract ' : '', strtolower($type), $classReflection->getName(), self::extendsToString($classReflection), self::implementsToString($classReflection), self::fileAndLinesToString($classReflection), count($constants) + count($enumCases), self::constantsToString($constants, $enumCases), count($staticProperties), self::propertiesToString($staticProperties), count($staticMethods), self::methodsToString($staticMethods), count($defaultProperties), self::propertiesToString($defaultProperties), $isObject ? count($dynamicProperties) : '', $isObject ? self::propertiesToString($dynamicProperties) : '', count($methods), self::methodsToString($methods, 2));
    }

    private static function typeToString(ReflectionClass $classReflection): string
    {
        if ($classReflection->isInterface()) {
            return 'Interface';
        }

        if ($classReflection->isTrait()) {
            return 'Trait';
        }

        return 'Class';
    }

    private static function sourceToString(ReflectionClass $classReflection): string
    {
        if ($classReflection->isUserDefined()) {
            return 'user';
        }

        $extensionName = $classReflection->getExtensionName();
        assert(is_string($extensionName));

        return sprintf('internal:%s', $extensionName);
    }

    private static function extendsToString(ReflectionClass $classReflection): string
    {
        $parentClass = $classReflection->getParentClass();

        if ($parentClass === null) {
            return '';
        }

        return ' extends ' . $parentClass->getName();
    }

    private static function implementsToString(ReflectionClass $classReflection): string
    {
        $interfaceNames = $classReflection->getInterfaceNames();

        if ($interfaceNames === []) {
            return '';
        }

        return ' implements ' . implode(', ', $interfaceNames);
    }

    private static function fileAndLinesToString(ReflectionClass $classReflection): string
    {
        if ($classReflection->isInternal()) {
            return '';
        }

        $fileName = $classReflection->getFileName();
        if ($fileName === null) {
            return '';
        }

        return sprintf("  @@ %s %d-%d\n", $fileName, $classReflection->getStartLine(), $classReflection->getEndLine());
    }

    /**
     * @param array<ReflectionClassConstant> $constants
     * @param array<ReflectionEnumCase>      $enumCases
     */
    private static function constantsToString(array $constants, array $enumCases): string
    {
        if ($constants === [] && $enumCases === []) {
            return '';
        }

        $items = array_map(static function (ReflectionEnumCase $enumCaseReflection) : string {
            return trim(ReflectionEnumCaseStringCast::toString($enumCaseReflection));
        }, $enumCases)
            + array_map(static function (ReflectionClassConstant $constantReflection) : string {
                return trim(ReflectionClassConstantStringCast::toString($constantReflection));
            }, $constants);

        return self::itemsToString($items);
    }

    /**
     * @param array<ReflectionProperty> $properties
     */
    private static function propertiesToString(array $properties): string
    {
        if ($properties === []) {
            return '';
        }

        return self::itemsToString(array_map(static function (ReflectionProperty $propertyReflection) : string {
            return ReflectionPropertyStringCast::toString($propertyReflection);
        }, $properties));
    }

    /**
     * @param array<ReflectionMethod> $methods
     */
    private static function methodsToString(array $methods, int $emptyLinesAmongItems = 1): string
    {
        if ($methods === []) {
            return '';
        }

        return self::itemsToString(array_map(static function (ReflectionMethod $method) : string {
            return ReflectionMethodStringCast::toString($method);
        }, $methods), $emptyLinesAmongItems);
    }

    /**
     * @param array<string> $items
     */
    private static function itemsToString(array $items, int $emptyLinesAmongItems = 1): string
    {
        $string = implode(str_repeat("\n", $emptyLinesAmongItems), $items);

        return "\n" . preg_replace('/(^|\n)(?!\n)/', '\1' . self::indent(), $string);
    }

    private static function indent(): string
    {
        return str_repeat(' ', 4);
    }

    /**
     * @return array<ReflectionProperty>
     */
    private static function getStaticProperties(ReflectionClass $classReflection): array
    {
        return array_filter($classReflection->getProperties(), static function (ReflectionProperty $propertyReflection) : bool {
            return $propertyReflection->isStatic();
        });
    }

    /**
     * @return array<ReflectionMethod>
     */
    private static function getStaticMethods(ReflectionClass $classReflection): array
    {
        return array_filter($classReflection->getMethods(), static function (ReflectionMethod $methodReflection) : bool {
            return $methodReflection->isStatic();
        });
    }

    /**
     * @return array<ReflectionProperty>
     */
    private static function getDefaultProperties(ReflectionClass $classReflection): array
    {
        return array_filter($classReflection->getProperties(), static function (ReflectionProperty $propertyReflection) : bool {
            return ! $propertyReflection->isStatic() && $propertyReflection->isDefault();
        });
    }

    /**
     * @return array<ReflectionProperty>
     */
    private static function getDynamicProperties(ReflectionClass $classReflection): array
    {
        return array_filter($classReflection->getProperties(), static function (ReflectionProperty $propertyReflection) : bool {
            return ! $propertyReflection->isStatic() && ! $propertyReflection->isDefault();
        });
    }

    /**
     * @return array<ReflectionMethod>
     */
    private static function getMethods(ReflectionClass $classReflection): array
    {
        return array_filter($classReflection->getMethods(), static function (ReflectionMethod $methodReflection) : bool {
            return ! $methodReflection->isStatic();
        });
    }
}
