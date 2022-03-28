<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection;

use LogicException;
use PhpParser\Node\Stmt\EnumCase;
use PHPStan\BetterReflection\NodeCompiler\CompiledValue;
use PHPStan\BetterReflection\NodeCompiler\CompileNodeToValue;
use PHPStan\BetterReflection\NodeCompiler\CompilerContext;
use PHPStan\BetterReflection\Reflection\Annotation\AnnotationHelper;
use PHPStan\BetterReflection\Reflection\Attribute\ReflectionAttributeHelper;
use PHPStan\BetterReflection\Reflection\StringCast\ReflectionEnumCaseStringCast;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\Util\CalculateReflectionColumn;
use PHPStan\BetterReflection\Util\GetLastDocComment;

use function assert;
use function is_int;
use function is_string;

class ReflectionEnumCase
{
    /**
     * @var \PHPStan\BetterReflection\NodeCompiler\CompiledValue|null
     */
    private $compiledValue;
    /**
     * @var \PHPStan\BetterReflection\Reflector\Reflector
     */
    private $reflector;
    /**
     * @var \PhpParser\Node\Stmt\EnumCase
     */
    private $node;
    /**
     * @var \PHPStan\BetterReflection\Reflection\ReflectionEnum
     */
    private $enum;
    private function __construct(Reflector $reflector, EnumCase $node, ReflectionEnum $enum)
    {
        $this->reflector = $reflector;
        $this->node = $node;
        $this->enum = $enum;
    }
    /**
     * @internal
     */
    public static function createFromNode(Reflector $reflector, EnumCase $node, ReflectionEnum $enum): self
    {
        return new self($reflector, $node, $enum);
    }

    public function getName(): string
    {
        return $this->node->name->toString();
    }

    /**
     * @return int|string
     */
    public function getValue()
    {
        $value = $this->getCompiledValue()->value;
        assert(is_string($value) || is_int($value));

        return $value;
    }

    /**
     * @throws LogicException
     */
    private function getCompiledValue(): CompiledValue
    {
        if ($this->node->expr === null) {
            throw new LogicException('This enum case does not have a value');
        }

        if ($this->compiledValue === null) {
            $this->compiledValue = (new CompileNodeToValue())->__invoke($this->node->expr, new CompilerContext($this->reflector, $this));
        }

        return $this->compiledValue;
    }

    public function getAst(): EnumCase
    {
        return $this->node;
    }

    public function getStartLine(): int
    {
        return $this->node->getStartLine();
    }

    public function getEndLine(): int
    {
        return $this->node->getEndLine();
    }

    public function getStartColumn(): int
    {
        return CalculateReflectionColumn::getStartColumn($this->enum->getLocatedSource()->getSource(), $this->node);
    }

    public function getEndColumn(): int
    {
        return CalculateReflectionColumn::getEndColumn($this->enum->getLocatedSource()->getSource(), $this->node);
    }

    public function getDeclaringEnum(): ReflectionEnum
    {
        return $this->enum;
    }

    public function getDeclaringClass(): ReflectionClass
    {
        return $this->enum;
    }

    public function getDocComment(): string
    {
        return GetLastDocComment::forNode($this->node);
    }

    public function isDeprecated(): bool
    {
        return AnnotationHelper::isDeprecated($this->getDocComment());
    }

    /**
     * @return list<ReflectionAttribute>
     */
    public function getAttributes(): array
    {
        return ReflectionAttributeHelper::createAttributes($this->reflector, $this);
    }

    /**
     * @return list<ReflectionAttribute>
     */
    public function getAttributesByName(string $name): array
    {
        return ReflectionAttributeHelper::filterAttributesByName($this->getAttributes(), $name);
    }

    /**
     * @param class-string $className
     *
     * @return list<ReflectionAttribute>
     */
    public function getAttributesByInstance(string $className): array
    {
        return ReflectionAttributeHelper::filterAttributesByInstance($this->getAttributes(), $className);
    }

    public function __toString(): string
    {
        return ReflectionEnumCaseStringCast::toString($this);
    }
}
