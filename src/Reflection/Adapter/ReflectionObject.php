<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection\Adapter;

use PHPStan\BetterReflection\Reflection\Exception\NotAnObject;
use PHPStan\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use PHPStan\BetterReflection\Reflection\ReflectionObject as BetterReflectionObject;
use PHPStan\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;
use PHPStan\BetterReflection\Util\FileHelper;
use ReflectionException as CoreReflectionException;
use ReflectionObject as CoreReflectionObject;
use function array_combine;
use function array_map;
use function array_values;
use function assert;
use function func_num_args;
use function is_array;
use function sprintf;
use function strtolower;

class ReflectionObject extends CoreReflectionObject
{
    /** @var BetterReflectionObject */
    private $betterReflectionObject;

    public function __construct(BetterReflectionObject $betterReflectionObject)
    {
        $this->betterReflectionObject = $betterReflectionObject;
    }

    /**
     * {@inheritDoc}
     *
     * @throws CoreReflectionException
     */
    public static function export($argument, $return = null)
    {
        $output = BetterReflectionObject::createFromInstance($argument)->__toString();

        if ($return) {
            return $output;
        }

        echo $output;

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return $this->betterReflectionObject->__toString();
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->betterReflectionObject->getName();
    }

    /**
     * {@inheritDoc}
     */
    public function isInternal(): bool
    {
        return $this->betterReflectionObject->isInternal();
    }

    /**
     * {@inheritDoc}
     */
    public function isUserDefined(): bool
    {
        return $this->betterReflectionObject->isUserDefined();
    }

    /**
     * {@inheritDoc}
     */
    public function isInstantiable(): bool
    {
        return $this->betterReflectionObject->isInstantiable();
    }

    /**
     * {@inheritDoc}
     */
    public function isCloneable(): bool
    {
        return $this->betterReflectionObject->isCloneable();
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getFileName()
    {
        $fileName = $this->betterReflectionObject->getFileName();

        return $fileName !== null ? FileHelper::normalizeSystemPath($fileName) : false;
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getStartLine()
    {
        return $this->betterReflectionObject->getStartLine();
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getEndLine()
    {
        return $this->betterReflectionObject->getEndLine();
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getDocComment()
    {
        return $this->betterReflectionObject->getDocComment() ?: false;
    }

    /**
     * {@inheritDoc}
     */
    public function getConstructor(): ?\ReflectionMethod
    {
        return new ReflectionMethod($this->betterReflectionObject->getConstructor());
    }

    /**
     * {@inheritDoc}
     */
    public function hasMethod($name): bool
    {
        return $this->betterReflectionObject->hasMethod($this->getMethodRealName($name));
    }

    /**
     * {@inheritDoc}
     */
    public function getMethod($name): \ReflectionMethod
    {
        return new ReflectionMethod($this->betterReflectionObject->getMethod($this->getMethodRealName($name)));
    }

    private function getMethodRealName(string $name) : string
    {
        $realMethodNames = array_map(static function (BetterReflectionMethod $method) : string {
            return $method->getName();
        }, $this->betterReflectionObject->getMethods());

        $methodNames = array_combine(array_map(static function (string $methodName) : string {
            return strtolower($methodName);
        }, $realMethodNames), $realMethodNames);

        return $methodNames[strtolower($name)] ?? $name;
    }

    /**
     * {@inheritDoc}
     */
    public function getMethods($filter = null): array
    {
        $methods = $this->betterReflectionObject->getMethods();

        $wrappedMethods = [];
        foreach ($methods as $key => $method) {
            $wrappedMethods[$key] = new ReflectionMethod($method);
        }

        return $wrappedMethods;
    }

    /**
     * {@inheritDoc}
     */
    public function hasProperty($name): bool
    {
        return $this->betterReflectionObject->hasProperty($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getProperty($name): \ReflectionProperty
    {
        $property = $this->betterReflectionObject->getProperty($name);

        if ($property === null) {
            throw new CoreReflectionException(sprintf('Property "%s" does not exist', $name));
        }

        return new ReflectionProperty($property);
    }

    /**
     * {@inheritDoc}
     */
    public function getProperties($filter = null): array
    {
        return array_values(array_map(static function (BetterReflectionProperty $property) : ReflectionProperty {
            return new ReflectionProperty($property);
        }, $this->betterReflectionObject->getProperties()));
    }

    /**
     * {@inheritDoc}
     */
    public function hasConstant($name): bool
    {
        return $this->betterReflectionObject->hasConstant($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getConstants(int $filter = null): array
    {
        return $this->betterReflectionObject->getConstants($filter);
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getConstant($name)
    {
        return $this->betterReflectionObject->getConstant($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getInterfaces(): array
    {
        $interfaces = $this->betterReflectionObject->getInterfaces();

        $wrappedInterfaces = [];
        foreach ($interfaces as $key => $interface) {
            $wrappedInterfaces[$key] = new ReflectionClass($interface);
        }

        return $wrappedInterfaces;
    }

    /**
     * {@inheritDoc}
     */
    public function getInterfaceNames(): array
    {
        return $this->betterReflectionObject->getInterfaceNames();
    }

    /**
     * {@inheritDoc}
     */
    public function isInterface(): bool
    {
        return $this->betterReflectionObject->isInterface();
    }

    /**
     * {@inheritDoc}
     */
    public function getTraits(): array
    {
        $traits = $this->betterReflectionObject->getTraits();

        /** @var array<trait-string> $traitNames */
        $traitNames = array_map(static function (BetterReflectionClass $trait) : string {
            return $trait->getName();
        }, $traits);

        $traitsByName = array_combine(
            $traitNames,
            array_map(static function (BetterReflectionClass $trait) : ReflectionClass {
                return new ReflectionClass($trait);
            }, $traits)
        );

        assert(
            is_array($traitsByName),
            sprintf(
                'Could not create an array<trait-string, ReflectionClass> for class "%s"',
                $this->betterReflectionObject->getName()
            )
        );

        return $traitsByName;
    }

    /**
     * {@inheritDoc}
     */
    public function getTraitNames(): array
    {
        return $this->betterReflectionObject->getTraitNames();
    }

    /**
     * {@inheritDoc}
     */
    public function getTraitAliases(): array
    {
        return $this->betterReflectionObject->getTraitAliases();
    }

    /**
     * {@inheritDoc}
     */
    public function isTrait(): bool
    {
        return $this->betterReflectionObject->isTrait();
    }

    /**
     * {@inheritDoc}
     */
    public function isAbstract(): bool
    {
        return $this->betterReflectionObject->isAbstract();
    }

    /**
     * {@inheritDoc}
     */
    public function isFinal(): bool
    {
        return $this->betterReflectionObject->isFinal();
    }

    /**
     * {@inheritDoc}
     */
    public function getModifiers(): int
    {
        return $this->betterReflectionObject->getModifiers();
    }

    /**
     * {@inheritDoc}
     */
    public function isInstance($object): bool
    {
        try {
            return $this->betterReflectionObject->isInstance($object);
        } catch (NotAnObject $e) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function newInstance($arg = null, ...$args)
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function newInstanceWithoutConstructor()
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function newInstanceArgs(?array $args = null)
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getParentClass()
    {
        $parentClass = $this->betterReflectionObject->getParentClass();

        if ($parentClass === null) {
            return false;
        }

        return new ReflectionClass($parentClass);
    }

    /**
     * {@inheritDoc}
     */
    public function isSubclassOf($class): bool
    {
        $realParentClassNames = $this->betterReflectionObject->getParentClassNames();

        $parentClassNames = array_combine(array_map(static function (string $parentClassName) : string {
            return strtolower($parentClassName);
        }, $realParentClassNames), $realParentClassNames);

        $realParentClassName = $parentClassNames[strtolower($class)] ?? $class;

        return $this->betterReflectionObject->isSubclassOf($realParentClassName);
    }

    /**
     * {@inheritDoc}
     */
    public function getStaticProperties(): array
    {
        return $this->betterReflectionObject->getStaticProperties();
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getStaticPropertyValue($name, $default = null)
    {
        $betterReflectionProperty = $this->betterReflectionObject->getProperty($name);

        if ($betterReflectionProperty === null) {
            if (func_num_args() === 2) {
                return $default;
            }

            throw new CoreReflectionException(sprintf('Property "%s" does not exist', $name));
        }

        $property = new ReflectionProperty($betterReflectionProperty);

        if (! $property->isAccessible()) {
            throw new CoreReflectionException(sprintf('Property "%s" is not accessible', $name));
        }

        if (! $property->isStatic()) {
            throw new CoreReflectionException(sprintf('Property "%s" is not static', $name));
        }

        return $property->getValue();
    }

    /**
     * {@inheritDoc}
     */
    public function setStaticPropertyValue($name, $value): void
    {
        $betterReflectionProperty = $this->betterReflectionObject->getProperty($name);

        if ($betterReflectionProperty === null) {
            throw new CoreReflectionException(sprintf('Property "%s" does not exist', $name));
        }

        $property = new ReflectionProperty($betterReflectionProperty);

        if (! $property->isAccessible()) {
            throw new CoreReflectionException(sprintf('Property "%s" is not accessible', $name));
        }

        if (! $property->isStatic()) {
            throw new CoreReflectionException(sprintf('Property "%s" is not static', $name));
        }

        $property->setValue($value);
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultProperties(): array
    {
        return $this->betterReflectionObject->getDefaultProperties();
    }

    /**
     * {@inheritDoc}
     */
    public function isIterateable(): bool
    {
        return $this->betterReflectionObject->isIterateable();
    }

    /**
     * {@inheritDoc}
     */
    public function implementsInterface($interface): bool
    {
        $realInterfaceNames = $this->betterReflectionObject->getInterfaceNames();

        $interfaceNames = array_combine(array_map(static function (string $interfaceName) : string {
            return strtolower($interfaceName);
        }, $realInterfaceNames), $realInterfaceNames);

        $realInterfaceName = $interfaceNames[strtolower($interface)] ?? $interface;

        return $this->betterReflectionObject->implementsInterface($realInterfaceName);
    }

    /**
     * {@inheritDoc}
     */
    public function getExtension(): ?\ReflectionExtension
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getExtensionName()
    {
        return $this->betterReflectionObject->getExtensionName() ?? false;
    }

    /**
     * {@inheritDoc}
     */
    public function inNamespace(): bool
    {
        return $this->betterReflectionObject->inNamespace();
    }

    /**
     * {@inheritDoc}
     */
    public function getNamespaceName(): string
    {
        return $this->betterReflectionObject->getNamespaceName();
    }

    /**
     * {@inheritDoc}
     */
    public function getShortName(): string
    {
        return $this->betterReflectionObject->getShortName();
    }
}
