<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection\Adapter;

use Exception;
use PHPStan\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use PHPStan\BetterReflection\Reflection\Exception\NoObjectProvided;
use PHPStan\BetterReflection\Reflection\Exception\NotAnObject;
use PHPStan\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use PHPStan\BetterReflection\Util\FileHelper;
use ReflectionException as CoreReflectionException;
use ReflectionMethod as CoreReflectionMethod;
use Throwable;
use function func_get_args;

class ReflectionMethod extends CoreReflectionMethod
{
    /** @var BetterReflectionMethod */
    private $betterReflectionMethod;

    /** @var bool */
    private $accessible = false;

    public function __construct(BetterReflectionMethod $betterReflectionMethod)
    {
        $this->betterReflectionMethod = $betterReflectionMethod;
    }

    public function getBetterReflection() : BetterReflectionMethod
    {
        return $this->betterReflectionMethod;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public static function export($class, $name, $return = null)
    {
        throw new Exception('Unable to export statically');
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return $this->betterReflectionMethod->__toString();
    }

    /**
     * {@inheritDoc}
     */
    public function inNamespace(): bool
    {
        return $this->betterReflectionMethod->inNamespace();
    }

    /**
     * {@inheritDoc}
     */
    public function isClosure(): bool
    {
        return $this->betterReflectionMethod->isClosure();
    }

    /**
     * {@inheritDoc}
     */
    public function isDeprecated(): bool
    {
        return $this->betterReflectionMethod->isDeprecated();
    }

    /**
     * {@inheritDoc}
     */
    public function isInternal(): bool
    {
        return $this->betterReflectionMethod->isInternal();
    }

    /**
     * {@inheritDoc}
     */
    public function isUserDefined(): bool
    {
        return $this->betterReflectionMethod->isUserDefined();
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
        return $this->betterReflectionMethod->getDocComment() ?: false;
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getEndLine()
    {
        return $this->betterReflectionMethod->getEndLine();
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
        return $this->betterReflectionMethod->getExtensionName() ?? false;
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getFileName()
    {
        $fileName = $this->betterReflectionMethod->getFileName();

        return $fileName !== null ? FileHelper::normalizeSystemPath($fileName) : false;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->betterReflectionMethod->getName();
    }

    /**
     * {@inheritDoc}
     */
    public function getNamespaceName(): string
    {
        return $this->betterReflectionMethod->getNamespaceName();
    }

    /**
     * {@inheritDoc}
     */
    public function getNumberOfParameters(): int
    {
        return $this->betterReflectionMethod->getNumberOfParameters();
    }

    /**
     * {@inheritDoc}
     */
    public function getNumberOfRequiredParameters(): int
    {
        return $this->betterReflectionMethod->getNumberOfRequiredParameters();
    }

    /**
     * {@inheritDoc}
     */
    public function getParameters(): array
    {
        $parameters = $this->betterReflectionMethod->getParameters();

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
        return ReflectionType::fromReturnTypeOrNull($this->betterReflectionMethod->getReturnType());
    }

    /**
     * {@inheritDoc}
     */
    public function getShortName(): string
    {
        return $this->betterReflectionMethod->getShortName();
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getStartLine()
    {
        return $this->betterReflectionMethod->getStartLine();
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
        return $this->betterReflectionMethod->returnsReference();
    }

    /**
     * {@inheritDoc}
     */
    public function isGenerator(): bool
    {
        return $this->betterReflectionMethod->isGenerator();
    }

    /**
     * {@inheritDoc}
     */
    public function isVariadic(): bool
    {
        return $this->betterReflectionMethod->isVariadic();
    }

    /**
     * {@inheritDoc}
     */
    public function isPublic(): bool
    {
        return $this->betterReflectionMethod->isPublic();
    }

    /**
     * {@inheritDoc}
     */
    public function isPrivate(): bool
    {
        return $this->betterReflectionMethod->isPrivate();
    }

    /**
     * {@inheritDoc}
     */
    public function isProtected(): bool
    {
        return $this->betterReflectionMethod->isProtected();
    }

    /**
     * {@inheritDoc}
     */
    public function isAbstract(): bool
    {
        return $this->betterReflectionMethod->isAbstract();
    }

    /**
     * {@inheritDoc}
     */
    public function isFinal(): bool
    {
        return $this->betterReflectionMethod->isFinal();
    }

    /**
     * {@inheritDoc}
     */
    public function isStatic(): bool
    {
        return $this->betterReflectionMethod->isStatic();
    }

    /**
     * {@inheritDoc}
     */
    public function isConstructor(): bool
    {
        return $this->betterReflectionMethod->isConstructor();
    }

    /**
     * {@inheritDoc}
     */
    public function isDestructor(): bool
    {
        return $this->betterReflectionMethod->isDestructor();
    }

    /**
     * {@inheritDoc}
     */
    public function getClosure($object = null): \Closure
    {
        try {
            return $this->betterReflectionMethod->getClosure($object);
        } catch (NoObjectProvided | NotAnObject $e) {
            throw new CoreReflectionException($e->getMessage(), 0, $e);
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getModifiers(): int
    {
        return $this->betterReflectionMethod->getModifiers();
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function invoke($object = null, $arg = null, ...$args)
    {
        if (! $this->isAccessible()) {
            throw new CoreReflectionException('Method not accessible');
        }

        try {
            return $this->betterReflectionMethod->invoke(...func_get_args());
        } catch (NoObjectProvided | NotAnObject $e) {
            return null;
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function invokeArgs($object = null, array $args = [])
    {
        if (! $this->isAccessible()) {
            throw new CoreReflectionException('Method not accessible');
        }

        try {
            return $this->betterReflectionMethod->invokeArgs($object, $args);
        } catch (NoObjectProvided | NotAnObject $e) {
            return null;
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getDeclaringClass(): \ReflectionClass
    {
        return new ReflectionClass($this->betterReflectionMethod->getImplementingClass());
    }

    /**
     * {@inheritDoc}
     */
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

    private function isAccessible() : bool
    {
        return $this->accessible || $this->isPublic();
    }
}
