<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use Closure;
use ReflectionClass as CoreReflectionClass;
use ReflectionException as CoreReflectionException;
use ReflectionExtension as CoreReflectionExtension;
use ReflectionMethod as CoreReflectionMethod;
use ReflectionType as CoreReflectionType;
use ReturnTypeWillChange;
use Roave\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use Roave\BetterReflection\Reflection\Exception\NoObjectProvided;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter as BetterReflectionParameter;
use Roave\BetterReflection\Util\FileHelper;
use Throwable;
use TypeError;
use ValueError;

use function array_map;
use function func_get_args;

final class ReflectionMethod extends CoreReflectionMethod
{
    /**
     * @var bool
     */
    private $accessible = false;
    /**
     * @var BetterReflectionMethod
     */
    private $betterReflectionMethod;

    public function __construct(BetterReflectionMethod $betterReflectionMethod)
    {
        $this->betterReflectionMethod = $betterReflectionMethod;
    }

    public function __toString(): string
    {
        return $this->betterReflectionMethod->__toString();
    }

    public function inNamespace(): bool
    {
        return $this->betterReflectionMethod->inNamespace();
    }

    public function isClosure(): bool
    {
        return $this->betterReflectionMethod->isClosure();
    }

    public function isDeprecated(): bool
    {
        return $this->betterReflectionMethod->isDeprecated();
    }

    public function isInternal(): bool
    {
        return $this->betterReflectionMethod->isInternal();
    }

    public function isUserDefined(): bool
    {
        return $this->betterReflectionMethod->isUserDefined();
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getClosureThis()
    {
        throw new NotImplemented('Not implemented');
    }

    public function getClosureScopeClass(): ?CoreReflectionClass
    {
        throw new NotImplemented('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getDocComment()
    {
        return $this->betterReflectionMethod->getDocComment() ?: false;
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getStartLine()
    {
        return $this->betterReflectionMethod->getStartLine();
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getEndLine()
    {
        return $this->betterReflectionMethod->getEndLine();
    }

    /**
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    public function getExtension(): ?CoreReflectionExtension
    {
        throw new NotImplemented('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getExtensionName()
    {
        return $this->betterReflectionMethod->getExtensionName() ?? '';
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getFileName()
    {
        $fileName = $this->betterReflectionMethod->getFileName();

        return $fileName !== null ? FileHelper::normalizeSystemPath($fileName) : false;
    }

    public function getName(): string
    {
        return $this->betterReflectionMethod->getName();
    }

    public function getNamespaceName(): string
    {
        return $this->betterReflectionMethod->getNamespaceName();
    }

    public function getNumberOfParameters(): int
    {
        return $this->betterReflectionMethod->getNumberOfParameters();
    }

    public function getNumberOfRequiredParameters(): int
    {
        return $this->betterReflectionMethod->getNumberOfRequiredParameters();
    }

    /**
     * @return list<ReflectionParameter>
     */
    public function getParameters(): array
    {
        return array_map(static function (BetterReflectionParameter $parameter) : ReflectionParameter {
            return new ReflectionParameter($parameter);
        }, $this->betterReflectionMethod->getParameters());
    }

    public function hasReturnType(): bool
    {
        return $this->betterReflectionMethod->hasReturnType();
    }

    public function getReturnType(): ?CoreReflectionType
    {
        return ReflectionType::fromTypeOrNull($this->betterReflectionMethod->getReturnType());
    }

    public function getShortName(): string
    {
        return $this->betterReflectionMethod->getShortName();
    }

    /**
     * @return array<string, scalar>
     */
    public function getStaticVariables(): array
    {
        throw new NotImplemented('Not implemented');
    }

    public function returnsReference(): bool
    {
        return $this->betterReflectionMethod->returnsReference();
    }

    public function isGenerator(): bool
    {
        return $this->betterReflectionMethod->isGenerator();
    }

    public function isVariadic(): bool
    {
        return $this->betterReflectionMethod->isVariadic();
    }

    public function isPublic(): bool
    {
        return $this->betterReflectionMethod->isPublic();
    }

    public function isPrivate(): bool
    {
        return $this->betterReflectionMethod->isPrivate();
    }

    public function isProtected(): bool
    {
        return $this->betterReflectionMethod->isProtected();
    }

    public function isAbstract(): bool
    {
        return $this->betterReflectionMethod->isAbstract();
    }

    public function isFinal(): bool
    {
        return $this->betterReflectionMethod->isFinal();
    }

    public function isStatic(): bool
    {
        return $this->betterReflectionMethod->isStatic();
    }

    public function isConstructor(): bool
    {
        return $this->betterReflectionMethod->isConstructor();
    }

    public function isDestructor(): bool
    {
        return $this->betterReflectionMethod->isDestructor();
    }

    /**
     * {@inheritDoc}
     */
    public function getClosure($object = null): Closure
    {
        try {
            return $this->betterReflectionMethod->getClosure($object);
        } catch (NoObjectProvided $e) {
            throw new ValueError($e->getMessage(), 0, $e);
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), 0, $e);
        }
    }

    public function getModifiers(): int
    {
        return $this->betterReflectionMethod->getModifiers();
    }

    /**
     * @param object $object
     * @param mixed  $arg
     * @param mixed  ...$args
     *
     * @return mixed
     *
     * @throws CoreReflectionException
     */
    #[ReturnTypeWillChange]
    public function invoke($object = null, $arg = null, ...$args)
    {
        if (! $this->isAccessible()) {
            throw new CoreReflectionException('Method not accessible');
        }

        try {
            return $this->betterReflectionMethod->invoke(...func_get_args());
        } catch (NoObjectProvided | TypeError $exception) {
            return null;
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param object  $object
     * @param mixed[] $args
     *
     * @return mixed
     *
     * @throws CoreReflectionException
     */
    #[ReturnTypeWillChange]
    public function invokeArgs($object = null, array $args = [])
    {
        if (! $this->isAccessible()) {
            throw new CoreReflectionException('Method not accessible');
        }

        try {
            return $this->betterReflectionMethod->invokeArgs($object, $args);
        } catch (NoObjectProvided | TypeError $exception) {
            return null;
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), 0, $e);
        }
    }

    public function getDeclaringClass(): ReflectionClass
    {
        return new ReflectionClass($this->betterReflectionMethod->getImplementingClass());
    }

    public function getPrototype(): ReflectionMethod
    {
        return new self($this->betterReflectionMethod->getPrototype());
    }

    /**
     * {@inheritDoc}
     */
    public function setAccessible($accessible): void
    {
        $this->accessible = true;
    }

    private function isAccessible(): bool
    {
        return $this->accessible || $this->isPublic();
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
            $attributes = $this->betterReflectionMethod->getAttributesByInstance($name);
        } elseif ($name !== null) {
            $attributes = $this->betterReflectionMethod->getAttributesByName($name);
        } else {
            $attributes = $this->betterReflectionMethod->getAttributes();
        }

        return array_map(static function (BetterReflectionAttribute $betterReflectionAttribute) {
            return ReflectionAttributeFactory::create($betterReflectionAttribute);
        }, $attributes);
    }

    public function hasTentativeReturnType(): bool
    {
        return $this->betterReflectionMethod->hasTentativeReturnType();
    }

    public function getTentativeReturnType(): ?CoreReflectionType
    {
        return ReflectionType::fromTypeOrNull($this->betterReflectionMethod->getTentativeReturnType());
    }

    /**
     * @return mixed[]
     */
    public function getClosureUsedVariables(): array
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    public function getBetterReflection(): BetterReflectionMethod
    {
        return $this->betterReflectionMethod;
    }
}
