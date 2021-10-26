<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection\Adapter;

use InvalidArgumentException;
use OutOfBoundsException;
use PHPStan\BetterReflection\Reflection\Exception\NotAnObject;
use PHPStan\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionClassConstant as BetterReflectionClassConstant;
use PHPStan\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use PHPStan\BetterReflection\Reflection\ReflectionObject as BetterReflectionObject;
use PHPStan\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;
use PHPStan\BetterReflection\Util\FileHelper;
use ReflectionClass as CoreReflectionClass;
use ReflectionException as CoreReflectionException;
use function array_combine;
use function array_map;
use function array_values;
use function assert;
use function func_num_args;
use function is_array;
use function is_object;
use function is_string;
use function sprintf;
use function strtolower;

class ReflectionClass extends CoreReflectionClass
{
    /** @var BetterReflectionClass */
    private $betterReflectionClass;

    public function __construct(BetterReflectionClass $betterReflectionClass)
    {
        $this->betterReflectionClass = $betterReflectionClass;

        unset($this->name);
    }

    public function getBetterReflection() : BetterReflectionClass
    {
        return $this->betterReflectionClass;
    }

    /**
     * {@inheritDoc}
     *
     * @throws CoreReflectionException
     */
    public static function export($argument, $return = false)
    {
        if (is_string($argument) || is_object($argument)) {
            if (is_string($argument)) {
                $output = BetterReflectionClass::createFromName($argument)->__toString();
            } else {
                $output = BetterReflectionObject::createFromInstance($argument)->__toString();
            }

            if ($return) {
                return $output;
            }

            echo $output;

            return null;
        }

        throw new InvalidArgumentException('Class name must be provided');
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return $this->betterReflectionClass->__toString();
    }

    /**
     * @return mixed
     */
    public function __get(string $name)
    {
        if ($name === 'name') {
            return $this->betterReflectionClass->getName();
        }

        return $this->{$name};
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->betterReflectionClass->getName();
    }

    /**
     * {@inheritDoc}
     */
    public function isAnonymous(): bool
    {
        return $this->betterReflectionClass->isAnonymous();
    }

    /**
     * {@inheritDoc}
     */
    public function isInternal(): bool
    {
        return $this->betterReflectionClass->isInternal();
    }

    /**
     * {@inheritDoc}
     */
    public function isUserDefined(): bool
    {
        return $this->betterReflectionClass->isUserDefined();
    }

    /**
     * {@inheritDoc}
     */
    public function isInstantiable(): bool
    {
        return $this->betterReflectionClass->isInstantiable();
    }

    /**
     * {@inheritDoc}
     */
    public function isCloneable(): bool
    {
        return $this->betterReflectionClass->isCloneable();
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getFileName()
    {
        $fileName = $this->betterReflectionClass->getFileName();

        return $fileName !== null ? FileHelper::normalizeSystemPath($fileName) : false;
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getStartLine()
    {
        return $this->betterReflectionClass->getStartLine();
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getEndLine()
    {
        return $this->betterReflectionClass->getEndLine();
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getDocComment()
    {
        return $this->betterReflectionClass->getDocComment() ?: false;
    }

    /**
     * {@inheritDoc}
     */
    public function getConstructor(): ?\ReflectionMethod
    {
        try {
            return new ReflectionMethod($this->betterReflectionClass->getConstructor());
        } catch (OutOfBoundsException $e) {
            return null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function hasMethod($name): bool
    {
        return $this->betterReflectionClass->hasMethod($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getMethod($name): \ReflectionMethod
    {
        return new ReflectionMethod($this->betterReflectionClass->getMethod($name));
    }

    /**
     * {@inheritDoc}
     */
    public function getMethods($filter = null): array
    {
        return array_map(static function (BetterReflectionMethod $method) : ReflectionMethod {
            return new ReflectionMethod($method);
        }, $this->betterReflectionClass->getMethods($filter));
    }

    /**
     * {@inheritDoc}
     */
    public function hasProperty($name): bool
    {
        return $this->betterReflectionClass->hasProperty($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getProperty($name): \ReflectionProperty
    {
        $betterReflectionProperty = $this->betterReflectionClass->getProperty($name);

        if ($betterReflectionProperty === null) {
            throw new CoreReflectionException(sprintf('Property "%s" does not exist', $name));
        }

        return new ReflectionProperty($betterReflectionProperty);
    }

    /**
     * {@inheritDoc}
     */
    public function getProperties($filter = null): array
    {
        return array_values(array_map(static function (BetterReflectionProperty $property) : ReflectionProperty {
            return new ReflectionProperty($property);
        }, $this->betterReflectionClass->getProperties($filter)));
    }

    /**
     * {@inheritDoc}
     */
    public function hasConstant($name): bool
    {
        return $this->betterReflectionClass->hasConstant($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getConstants(int $filter = null): array
    {
        return $this->betterReflectionClass->getConstants($filter);
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getConstant($name)
    {
        return $this->betterReflectionClass->getConstant($name);
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function getReflectionConstant($name)
    {
        return new ReflectionClassConstant(
            $this->betterReflectionClass->getReflectionConstant($name)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getReflectionConstants(int $filter = null): array
    {
        return array_values(array_map(static function (BetterReflectionClassConstant $betterConstant) : ReflectionClassConstant {
            return new ReflectionClassConstant($betterConstant);
        }, $this->betterReflectionClass->getReflectionConstants($filter)));
    }

    /**
     * {@inheritDoc}
     */
    public function getInterfaces(): array
    {
        $interfaces = $this->betterReflectionClass->getInterfaces();

        $wrappedInterfaces = [];
        foreach ($interfaces as $key => $interface) {
            $wrappedInterfaces[$key] = new self($interface);
        }

        return $wrappedInterfaces;
    }

    /**
     * {@inheritDoc}
     */
    public function getInterfaceNames(): array
    {
        return $this->betterReflectionClass->getInterfaceNames();
    }

    /**
     * {@inheritDoc}
     */
    public function isInterface(): bool
    {
        return $this->betterReflectionClass->isInterface();
    }

    /**
     * {@inheritDoc}
     */
    public function getTraits(): array
    {
        $traits = $this->betterReflectionClass->getTraits();

        /** @var array<trait-string> $traitNames */
        $traitNames = array_map(static function (BetterReflectionClass $trait) : string {
            return $trait->getName();
        }, $traits);

        $traitsByName = array_combine(
            $traitNames,
            array_map(static function (BetterReflectionClass $trait) : self {
                return new self($trait);
            }, $traits)
        );

        assert(
            is_array($traitsByName),
            sprintf(
                'Could not create an array<trait-string, ReflectionClass> for class "%s"',
                $this->betterReflectionClass->getName()
            )
        );

        return $traitsByName;
    }

    /**
     * {@inheritDoc}
     */
    public function getTraitNames(): array
    {
        return $this->betterReflectionClass->getTraitNames();
    }

    /**
     * {@inheritDoc}
     */
    public function getTraitAliases(): array
    {
        return $this->betterReflectionClass->getTraitAliases();
    }

    /**
     * {@inheritDoc}
     */
    public function isTrait(): bool
    {
        return $this->betterReflectionClass->isTrait();
    }

    /**
     * {@inheritDoc}
     */
    public function isAbstract(): bool
    {
        return $this->betterReflectionClass->isAbstract();
    }

    /**
     * {@inheritDoc}
     */
    public function isFinal(): bool
    {
        return $this->betterReflectionClass->isFinal();
    }

    /**
     * {@inheritDoc}
     */
    public function getModifiers(): int
    {
        return $this->betterReflectionClass->getModifiers();
    }

    /**
     * {@inheritDoc}
     */
    public function isInstance($object): bool
    {
        try {
            return $this->betterReflectionClass->isInstance($object);
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
        $parentClass = $this->betterReflectionClass->getParentClass();

        if ($parentClass === null) {
            return false;
        }

        return new self($parentClass);
    }

    /**
     * {@inheritDoc}
     */
    public function isSubclassOf($class): bool
    {
        $realParentClassNames = $this->betterReflectionClass->getParentClassNames();

        $parentClassNames = array_combine(array_map(static function (string $parentClassName) : string {
            return strtolower($parentClassName);
        }, $realParentClassNames), $realParentClassNames);

        $realParentClassName = $parentClassNames[strtolower($class)] ?? $class;

        return $this->betterReflectionClass->isSubclassOf($realParentClassName) || $this->implementsInterface($class);
    }

    /**
     * {@inheritDoc}
     */
    public function getStaticProperties(): ?array
    {
        return $this->betterReflectionClass->getStaticProperties();
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getStaticPropertyValue($name, $default = null)
    {
        $betterReflectionProperty = $this->betterReflectionClass->getProperty($name);

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
        $betterReflectionProperty = $this->betterReflectionClass->getProperty($name);

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
        return $this->betterReflectionClass->getDefaultProperties();
    }

    /**
     * {@inheritDoc}
     */
    public function isIterateable(): bool
    {
        return $this->betterReflectionClass->isIterateable();
    }

    /**
     * {@inheritDoc}
     */
    public function implementsInterface($interface): bool
    {
        $realInterfaceNames = $this->betterReflectionClass->getInterfaceNames();

        $interfaceNames = array_combine(array_map(static function (string $interfaceName) : string {
            return strtolower($interfaceName);
        }, $realInterfaceNames), $realInterfaceNames);

        $realInterfaceName = $interfaceNames[strtolower($interface)] ?? $interface;

        return $this->betterReflectionClass->implementsInterface($realInterfaceName);
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
        return $this->betterReflectionClass->getExtensionName() ?? false;
    }

    /**
     * {@inheritDoc}
     */
    public function inNamespace(): bool
    {
        return $this->betterReflectionClass->inNamespace();
    }

    /**
     * {@inheritDoc}
     */
    public function getNamespaceName(): string
    {
        return $this->betterReflectionClass->getNamespaceName();
    }

    /**
     * {@inheritDoc}
     */
    public function getShortName(): string
    {
        return $this->betterReflectionClass->getShortName();
    }
}
