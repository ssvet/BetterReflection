<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use Closure;
use ReflectionClass as CoreReflectionClass;
use ReflectionException as CoreReflectionException;
use ReflectionExtension as CoreReflectionExtension;
use ReflectionFunction as CoreReflectionFunction;
use ReflectionType as CoreReflectionType;
use ReturnTypeWillChange;
use Roave\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionFunction as BetterReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionParameter as BetterReflectionParameter;
use Roave\BetterReflection\Util\FileHelper;
use Throwable;
use ValueError;

use function array_map;
use function func_get_args;

use const PHP_VERSION_ID;

final class ReflectionFunction extends CoreReflectionFunction
{
    public function __construct(private BetterReflectionFunction $betterReflectionFunction)
    {
    }

    public function __toString(): string
    {
        return $this->betterReflectionFunction->__toString();
    }

    public function inNamespace(): bool
    {
        return $this->betterReflectionFunction->inNamespace();
    }

    public function isClosure(): bool
    {
        return $this->betterReflectionFunction->isClosure();
    }

    public function isDeprecated(): bool
    {
        return $this->betterReflectionFunction->isDeprecated();
    }

    public function isInternal(): bool
    {
        return $this->betterReflectionFunction->isInternal();
    }

    public function isUserDefined(): bool
    {
        return $this->betterReflectionFunction->isUserDefined();
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
        return $this->betterReflectionFunction->getDocComment() ?: false;
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getStartLine()
    {
        return $this->betterReflectionFunction->getStartLine();
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getEndLine()
    {
        return $this->betterReflectionFunction->getEndLine();
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
        return $this->betterReflectionFunction->getExtensionName() ?? '';
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getFileName()
    {
        $fileName = $this->betterReflectionFunction->getFileName();

        return $fileName !== null ? FileHelper::normalizeSystemPath($fileName) : false;
    }

    public function getName(): string
    {
        return $this->betterReflectionFunction->getName();
    }

    public function getNamespaceName(): string
    {
        return $this->betterReflectionFunction->getNamespaceName();
    }

    public function getNumberOfParameters(): int
    {
        return $this->betterReflectionFunction->getNumberOfParameters();
    }

    public function getNumberOfRequiredParameters(): int
    {
        return $this->betterReflectionFunction->getNumberOfRequiredParameters();
    }

    /**
     * @return list<ReflectionParameter>
     */
    public function getParameters(): array
    {
        return array_map(
            static fn (BetterReflectionParameter $parameter): ReflectionParameter => new ReflectionParameter($parameter),
            $this->betterReflectionFunction->getParameters(),
        );
    }

    public function hasReturnType(): bool
    {
        return $this->betterReflectionFunction->hasReturnType();
    }

    public function getReturnType(): ?CoreReflectionType
    {
        return ReflectionType::fromTypeOrNull($this->betterReflectionFunction->getReturnType());
    }

    public function getShortName(): string
    {
        return $this->betterReflectionFunction->getShortName();
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
        return $this->betterReflectionFunction->returnsReference();
    }

    public function isGenerator(): bool
    {
        return $this->betterReflectionFunction->isGenerator();
    }

    public function isVariadic(): bool
    {
        return $this->betterReflectionFunction->isVariadic();
    }

    public function isDisabled(): bool
    {
        return $this->betterReflectionFunction->isDisabled();
    }

    /**
     * @param mixed $arg
     * @param mixed ...$args
     *
     * @return mixed
     *
     * @throws CoreReflectionException
     */
    #[ReturnTypeWillChange]
    public function invoke($arg = null, ...$args)
    {
        try {
            return $this->betterReflectionFunction->invoke(...func_get_args());
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param mixed[] $args
     */
    #[ReturnTypeWillChange]
    public function invokeArgs(array $args)
    {
        try {
            return $this->betterReflectionFunction->invokeArgs($args);
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), 0, $e);
        }
    }

    public function getClosure(): Closure
    {
        return $this->betterReflectionFunction->getClosure();
    }

    /**
     * @return mixed[]
     */
    public function getClosureUsedVariables(): array
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    public function hasTentativeReturnType(): bool
    {
        return $this->betterReflectionFunction->hasTentativeReturnType();
    }

    public function getTentativeReturnType(): ?CoreReflectionType
    {
        return ReflectionType::fromTypeOrNull($this->betterReflectionFunction->getTentativeReturnType());
    }

    public function isStatic(): bool
    {
        return $this->betterReflectionFunction->isStatic();
    }

    /**
     * @param class-string|null $name
     *
     * @return list<ReflectionAttribute>
     */
    public function getAttributes(?string $name = null, int $flags = 0): array
    {
        if ($flags !== 0 && $flags !== ReflectionAttribute::IS_INSTANCEOF) {
            throw new ValueError('Argument #2 ($flags) must be a valid attribute filter flag');
        }

        if (PHP_VERSION_ID >= 80000 && PHP_VERSION_ID < 80012) {
            return [];
        }

        if (PHP_VERSION_ID < 70200) {
            return [];
        }

        if ($name !== null && $flags & ReflectionAttribute::IS_INSTANCEOF) {
            $attributes = $this->betterReflectionFunction->getAttributesByInstance($name);
        } elseif ($name !== null) {
            $attributes = $this->betterReflectionFunction->getAttributesByName($name);
        } else {
            $attributes = $this->betterReflectionFunction->getAttributes();
        }

        return array_map(static fn (BetterReflectionAttribute $betterReflectionAttribute): ReflectionAttribute => new ReflectionAttribute($betterReflectionAttribute), $attributes);
    }
}
