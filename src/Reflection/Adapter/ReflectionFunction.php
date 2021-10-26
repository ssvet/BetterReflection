<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection\Adapter;

use Exception;
use PHPStan\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use PHPStan\BetterReflection\Reflection\ReflectionFunction as BetterReflectionFunction;
use PHPStan\BetterReflection\Util\FileHelper;
use ReflectionException as CoreReflectionException;
use ReflectionFunction as CoreReflectionFunction;
use Throwable;
use function func_get_args;

class ReflectionFunction extends CoreReflectionFunction
{
    /** @var BetterReflectionFunction */
    private $betterReflectionFunction;

    public function __construct(BetterReflectionFunction $betterReflectionFunction)
    {
        $this->betterReflectionFunction = $betterReflectionFunction;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public static function export($name, $return = null)
    {
        throw new Exception('Unable to export statically');
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return $this->betterReflectionFunction->__toString();
    }

    /**
     * {@inheritDoc}
     */
    public function inNamespace(): bool
    {
        return $this->betterReflectionFunction->inNamespace();
    }

    /**
     * {@inheritDoc}
     */
    public function isClosure(): bool
    {
        return $this->betterReflectionFunction->isClosure();
    }

    /**
     * {@inheritDoc}
     */
    public function isDeprecated(): bool
    {
        return $this->betterReflectionFunction->isDeprecated();
    }

    /**
     * {@inheritDoc}
     */
    public function isInternal(): bool
    {
        return $this->betterReflectionFunction->isInternal();
    }

    /**
     * {@inheritDoc}
     */
    public function isUserDefined(): bool
    {
        return $this->betterReflectionFunction->isUserDefined();
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getClosureThis()
    {
        throw new NotImplemented('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getClosureScopeClass(): ?\ReflectionClass
    {
        throw new NotImplemented('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getDocComment()
    {
        return $this->betterReflectionFunction->getDocComment() ?: false;
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getEndLine()
    {
        return $this->betterReflectionFunction->getEndLine();
    }

    /**
     * {@inheritDoc}
     */
    public function getExtension(): ?\ReflectionExtension
    {
        throw new NotImplemented('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getExtensionName()
    {
        return $this->betterReflectionFunction->getExtensionName() ?? false;
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getFileName()
    {
        $fileName = $this->betterReflectionFunction->getFileName();

        return $fileName !== null ? FileHelper::normalizeSystemPath($fileName) : false;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->betterReflectionFunction->getName();
    }

    /**
     * {@inheritDoc}
     */
    public function getNamespaceName(): string
    {
        return $this->betterReflectionFunction->getNamespaceName();
    }

    /**
     * {@inheritDoc}
     */
    public function getNumberOfParameters(): int
    {
        return $this->betterReflectionFunction->getNumberOfParameters();
    }

    /**
     * {@inheritDoc}
     */
    public function getNumberOfRequiredParameters(): int
    {
        return $this->betterReflectionFunction->getNumberOfRequiredParameters();
    }

    /**
     * {@inheritDoc}
     */
    public function getParameters(): array
    {
        $parameters = $this->betterReflectionFunction->getParameters();

        $wrappedParameters = [];
        foreach ($parameters as $key => $parameter) {
            $wrappedParameters[$key] = new ReflectionParameter($parameter);
        }

        return $wrappedParameters;
    }

    /**
     * {@inheritDoc}
     */
    public function getReturnType(): ?\ReflectionType
    {
        return ReflectionType::fromReturnTypeOrNull($this->betterReflectionFunction->getReturnType());
    }

    /**
     * {@inheritDoc}
     */
    public function getShortName(): string
    {
        return $this->betterReflectionFunction->getShortName();
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getStartLine()
    {
        return $this->betterReflectionFunction->getStartLine();
    }

    /**
     * {@inheritDoc}
     */
    public function getStaticVariables(): array
    {
        throw new NotImplemented('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function returnsReference(): bool
    {
        return $this->betterReflectionFunction->returnsReference();
    }

    /**
     * {@inheritDoc}
     */
    public function isGenerator(): bool
    {
        return $this->betterReflectionFunction->isGenerator();
    }

    /**
     * {@inheritDoc}
     */
    public function isVariadic(): bool
    {
        return $this->betterReflectionFunction->isVariadic();
    }

    /**
     * {@inheritDoc}
     */
    public function isDisabled(): bool
    {
        return $this->betterReflectionFunction->isDisabled();
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function invoke($arg = null, ...$args)
    {
        try {
            return $this->betterReflectionFunction->invoke(...func_get_args());
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function invokeArgs(array $args)
    {
        try {
            return $this->betterReflectionFunction->invokeArgs($args);
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getClosure(): \Closure
    {
        try {
            return $this->betterReflectionFunction->getClosure();
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), 0, $e);
        }
    }
}
