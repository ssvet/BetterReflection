<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection\Adapter;

use ReflectionException as CoreReflectionException;
use ReflectionProperty as CoreReflectionProperty;
use ReturnTypeWillChange;
use PHPStan\BetterReflection\Reflection\Exception\NoObjectProvided;
use PHPStan\BetterReflection\Reflection\Exception\NotAnObject;
use PHPStan\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use PHPStan\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;
use Throwable;
use TypeError;
use ValueError;

use function array_map;

final class ReflectionProperty extends CoreReflectionProperty
{
    /**
     * @var bool
     */
    private $accessible = false;
    /**
     * @var BetterReflectionProperty
     */
    private $betterReflectionProperty;

    public function __construct(BetterReflectionProperty $betterReflectionProperty)
    {
        $this->betterReflectionProperty = $betterReflectionProperty;
    }

    public function __toString(): string
    {
        return $this->betterReflectionProperty->__toString();
    }

    public function getName(): string
    {
        return $this->betterReflectionProperty->getName();
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getValue($object = null)
    {
        if (! $this->isAccessible()) {
            throw new CoreReflectionException('Property not accessible');
        }

        try {
            return $this->betterReflectionProperty->getValue($object);
        } catch (NoObjectProvided | TypeError $exception) {
            return null;
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @psalm-suppress MethodSignatureMismatch
     * @param mixed $object
     * @param mixed $value
     */
    public function setValue($object, $value = null): void
    {
        if (! $this->isAccessible()) {
            throw new CoreReflectionException('Property not accessible');
        }

        try {
            $this->betterReflectionProperty->setValue($object, $value);
        } catch (NoObjectProvided | NotAnObject $exception) {
            return;
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), 0, $e);
        }
    }

    public function hasType(): bool
    {
        return $this->betterReflectionProperty->hasType();
    }

    /**
     * @psalm-mutation-free
     */
    public function getType(): ?\ReflectionType
    {
        /** @psalm-suppress ImpureMethodCall */
        return ReflectionType::fromTypeOrNull($this->betterReflectionProperty->getType());
    }

    public function isPublic(): bool
    {
        return $this->betterReflectionProperty->isPublic();
    }

    public function isPrivate(): bool
    {
        return $this->betterReflectionProperty->isPrivate();
    }

    public function isProtected(): bool
    {
        return $this->betterReflectionProperty->isProtected();
    }

    public function isStatic(): bool
    {
        return $this->betterReflectionProperty->isStatic();
    }

    public function isDefault(): bool
    {
        return $this->betterReflectionProperty->isDefault();
    }

    public function getModifiers(): int
    {
        return $this->betterReflectionProperty->getModifiers();
    }

    public function getDeclaringClass(): ReflectionClass
    {
        return new ReflectionClass($this->betterReflectionProperty->getImplementingClass());
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getDocComment()
    {
        return $this->betterReflectionProperty->getDocComment() ?: false;
    }

    /**
     * {@inheritDoc}
     */
    public function setAccessible($accessible): void
    {
        $this->accessible = true;
    }

    public function isAccessible(): bool
    {
        return $this->accessible || $this->isPublic();
    }

    public function hasDefaultValue(): bool
    {
        return $this->betterReflectionProperty->hasDefaultValue();
    }

    /**
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function getDefaultValue()
    {
        return $this->betterReflectionProperty->getDefaultValue();
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function isInitialized($object = null)
    {
        if (! $this->isAccessible()) {
            throw new CoreReflectionException('Property not accessible');
        }

        try {
            return $this->betterReflectionProperty->isInitialized($object);
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), 0, $e);
        }
    }

    public function isPromoted(): bool
    {
        return $this->betterReflectionProperty->isPromoted();
    }

    /**
     * @param class-string|null $name
     *
     * @return list<ReflectionAttribute|FakeReflectionAttribute>
     */
    public function getAttributes(?string $name = null, int $flags = 0): array
    {
        if ($flags !== 0 && $flags !== ReflectionAttribute::IS_INSTANCEOF) {
            throw new ValueError('Argument #2 ($flags) must be a valid attribute filter flag');
        }

        if ($name !== null && $flags !== 0) {
            $attributes = $this->betterReflectionProperty->getAttributesByInstance($name);
        } elseif ($name !== null) {
            $attributes = $this->betterReflectionProperty->getAttributesByName($name);
        } else {
            $attributes = $this->betterReflectionProperty->getAttributes();
        }

        return array_map(static function (BetterReflectionAttribute $betterReflectionAttribute) {
            return ReflectionAttributeFactory::create($betterReflectionAttribute);
        }, $attributes);
    }

    public function isReadOnly(): bool
    {
        return $this->betterReflectionProperty->isReadOnly();
    }

    public function getBetterReflection(): BetterReflectionProperty
    {
        return $this->betterReflectionProperty;
    }
}
