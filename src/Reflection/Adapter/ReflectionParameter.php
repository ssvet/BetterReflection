<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection\Adapter;

use Exception;
use PHPStan\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use PHPStan\BetterReflection\Reflection\ReflectionParameter as BetterReflectionParameter;
use ReflectionParameter as CoreReflectionParameter;
use function assert;

class ReflectionParameter extends CoreReflectionParameter
{
    /** @var BetterReflectionParameter */
    private $betterReflectionParameter;

    public function __construct(BetterReflectionParameter $betterReflectionParameter)
    {
        $this->betterReflectionParameter = $betterReflectionParameter;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public static function export($function, $parameter, $return = null)
    {
        throw new Exception('Unable to export statically');
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return $this->betterReflectionParameter->__toString();
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->betterReflectionParameter->getName();
    }

    /**
     * {@inheritDoc}
     */
    public function isPassedByReference(): bool
    {
        return $this->betterReflectionParameter->isPassedByReference();
    }

    /**
     * {@inheritDoc}
     */
    public function canBePassedByValue(): bool
    {
        return $this->betterReflectionParameter->canBePassedByValue();
    }

    /**
     * {@inheritDoc}
     */
    public function getDeclaringFunction(): \ReflectionFunctionAbstract
    {
        $function = $this->betterReflectionParameter->getDeclaringFunction();
        assert($function instanceof BetterReflectionMethod || $function instanceof \PHPStan\BetterReflection\Reflection\ReflectionFunction);

        if ($function instanceof BetterReflectionMethod) {
            return new ReflectionMethod($function);
        }

        return new ReflectionFunction($function);
    }

    /**
     * {@inheritDoc}
     */
    public function getDeclaringClass(): ?\ReflectionClass
    {
        $declaringClass = $this->betterReflectionParameter->getDeclaringClass();

        if ($declaringClass === null) {
            return null;
        }

        return new ReflectionClass($declaringClass);
    }

    /**
     * {@inheritDoc}
     */
    public function getClass(): ?\ReflectionClass
    {
        $class = $this->betterReflectionParameter->getClass();

        if ($class === null) {
            return null;
        }

        return new ReflectionClass($class);
    }

    /**
     * {@inheritDoc}
     */
    public function isArray(): bool
    {
        return $this->betterReflectionParameter->isArray();
    }

    /**
     * {@inheritDoc}
     */
    public function isCallable(): bool
    {
        return $this->betterReflectionParameter->isCallable();
    }

    /**
     * {@inheritDoc}
     */
    public function allowsNull(): bool
    {
        return $this->betterReflectionParameter->allowsNull();
    }

    /**
     * {@inheritDoc}
     */
    public function getPosition(): int
    {
        return $this->betterReflectionParameter->getPosition();
    }

    /**
     * {@inheritDoc}
     */
    public function isOptional(): bool
    {
        return $this->betterReflectionParameter->isOptional();
    }

    /**
     * {@inheritDoc}
     */
    public function isVariadic(): bool
    {
        return $this->betterReflectionParameter->isVariadic();
    }

    /**
     * {@inheritDoc}
     */
    public function isDefaultValueAvailable(): bool
    {
        return $this->betterReflectionParameter->isDefaultValueAvailable();
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getDefaultValue()
    {
        return $this->betterReflectionParameter->getDefaultValue();
    }

    /**
     * {@inheritDoc}
     */
    public function isDefaultValueConstant(): bool
    {
        return $this->betterReflectionParameter->isDefaultValueConstant();
    }

    public function isPromoted() : bool
    {
        return $this->betterReflectionParameter->isPromoted();
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultValueConstantName(): ?string
    {
        return $this->betterReflectionParameter->getDefaultValueConstantName();
    }

    /**
     * {@inheritDoc}
     */
    public function hasType(): bool
    {
        return $this->betterReflectionParameter->hasType();
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): ?\ReflectionType
    {
        return ReflectionType::fromReturnTypeOrNull($this->betterReflectionParameter->getType());
    }
}
