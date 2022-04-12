<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use Attribute;
use LogicException;
use PhpParser\Node;
use Roave\BetterReflection\NodeCompiler\CompileNodeToValue;
use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\Reflection\StringCast\ReflectionAttributeStringCast;
use Roave\BetterReflection\Reflector\Reflector;

class ReflectionAttribute
{
    /**
     * @var \Roave\BetterReflection\Reflector\Reflector
     */
    private $reflector;
    /**
     * @var \PhpParser\Node\Attribute
     */
    private $node;
    /**
     * @var \Roave\BetterReflection\Reflection\ReflectionClass|\Roave\BetterReflection\Reflection\ReflectionClassConstant|\Roave\BetterReflection\Reflection\ReflectionEnumCase|\Roave\BetterReflection\Reflection\ReflectionFunction|\Roave\BetterReflection\Reflection\ReflectionMethod|\Roave\BetterReflection\Reflection\ReflectionParameter|\Roave\BetterReflection\Reflection\ReflectionProperty
     */
    private $owner;
    /**
     * @var bool
     */
    private $isRepeated;
    /**
     * @param \Roave\BetterReflection\Reflection\ReflectionClass|\Roave\BetterReflection\Reflection\ReflectionClassConstant|\Roave\BetterReflection\Reflection\ReflectionEnumCase|\Roave\BetterReflection\Reflection\ReflectionFunction|\Roave\BetterReflection\Reflection\ReflectionMethod|\Roave\BetterReflection\Reflection\ReflectionParameter|\Roave\BetterReflection\Reflection\ReflectionProperty $owner
     */
    public function __construct(Reflector $reflector, Node\Attribute $node, $owner, bool $isRepeated)
    {
        $this->reflector = $reflector;
        $this->node = $node;
        $this->owner = $owner;
        $this->isRepeated = $isRepeated;
    }
    public function getName(): string
    {
        return $this->node->name->toString();
    }

    public function getClass(): ReflectionClass
    {
        return $this->reflector->reflectClass($this->getName());
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getArguments(): array
    {
        $arguments = [];

        $compiler = new CompileNodeToValue();
        $context  = new CompilerContext($this->reflector, $this->owner);

        foreach ($this->node->args as $argNo => $arg) {
            /** @psalm-suppress MixedAssignment */
            $arguments[(($argName = $arg->name) ? $argName->toString() : null) ?? $argNo] = $compiler->__invoke($arg->value, $context)->value;
        }

        return $arguments;
    }

    public function getTarget(): int
    {
        switch (true) {
            case $this->owner instanceof ReflectionClass:
                return Attribute::TARGET_CLASS;
            case $this->owner instanceof ReflectionFunction:
                return Attribute::TARGET_FUNCTION;
            case $this->owner instanceof ReflectionMethod:
                return Attribute::TARGET_METHOD;
            case $this->owner instanceof ReflectionProperty:
                return Attribute::TARGET_PROPERTY;
            case $this->owner instanceof ReflectionClassConstant:
                return Attribute::TARGET_CLASS_CONSTANT;
            case $this->owner instanceof ReflectionEnumCase:
                return Attribute::TARGET_CLASS_CONSTANT;
            case $this->owner instanceof ReflectionParameter:
                return Attribute::TARGET_PARAMETER;
            default:
                throw new LogicException('unknown owner');
        }
    }

    public function isRepeated(): bool
    {
        return $this->isRepeated;
    }

    public function __toString(): string
    {
        return ReflectionAttributeStringCast::toString($this);
    }
}
