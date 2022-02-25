<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection;

use PhpParser\Node\Stmt\ClassConst;
use ReflectionClassConstant as CoreReflectionClassConstant;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\BetterReflection\NodeCompiler\CompiledValue;
use PHPStan\BetterReflection\NodeCompiler\CompileNodeToValue;
use PHPStan\BetterReflection\NodeCompiler\CompilerContext;
use PHPStan\BetterReflection\Reflection\Annotation\AnnotationHelper;
use PHPStan\BetterReflection\Reflection\Attribute\ReflectionAttributeHelper;
use PHPStan\BetterReflection\Reflection\StringCast\ReflectionClassConstantStringCast;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\Util\CalculateReflectionColumn;
use PHPStan\BetterReflection\Util\GetLastDocComment;

class ReflectionClassConstant
{
    public const IS_FINAL = 32;

    /**
     * @var \PHPStan\BetterReflection\NodeCompiler\CompiledValue|null
     */
    private $compiledValue;
    /**
     * @var \PHPStan\BetterReflection\Reflector\Reflector
     */
    private $reflector;
    /**
     * @var \PhpParser\Node\Stmt\ClassConst
     */
    private $node;
    /**
     * @var \PHPStan\BetterReflection\Reflection\ReflectionClass
     */
    private $owner;
    /**
     * @var int
     */
    private $positionInNode;
    private function __construct(Reflector $reflector, ClassConst $node, ReflectionClass $owner, int $positionInNode)
    {
        $this->reflector = $reflector;
        $this->node = $node;
        $this->owner = $owner;
        $this->positionInNode = $positionInNode;
    }
    /**
     * Create a reflection of a class's constant by Const Node
     *
     * @internal
     */
    public static function createFromNode(Reflector $reflector, ClassConst $node, int $positionInNode, ReflectionClass $owner): self
    {
        return new self($reflector, $node, $owner, $positionInNode);
    }

    /**
     * Get the name of the reflection (e.g. if this is a ReflectionClass this
     * will be the class name).
     */
    public function getName(): string
    {
        return $this->node->consts[$this->positionInNode]->name->name;
    }

    /**
     * Returns constant value
     * @return mixed
     */
    public function getValue()
    {
        if ($this->compiledValue === null) {
            $this->compiledValue = (new CompileNodeToValue())->__invoke($this->node->consts[$this->positionInNode]->value, new CompilerContext($this->reflector, $this));
        }

        return $this->compiledValue->value;
    }

    /**
     * Constant is public
     */
    public function isPublic(): bool
    {
        return $this->node->isPublic();
    }

    /**
     * Constant is private
     */
    public function isPrivate(): bool
    {
        return $this->node->isPrivate();
    }

    /**
     * Constant is protected
     */
    public function isProtected(): bool
    {
        return $this->node->isProtected();
    }

    public function isFinal(): bool
    {
        $final = $this->node->isFinal();
        if ($final) {
            return true;
        }

        if (BetterReflection::$phpVersion >= 80100) {
            return false;
        }

        return $this->getDeclaringClass()->isInterface();
    }

    /**
     * Returns a bitfield of the access modifiers for this constant
     */
    public function getModifiers(): int
    {
        $val  = $this->isPublic() ? CoreReflectionClassConstant::IS_PUBLIC : 0;
        $val += $this->isProtected() ? CoreReflectionClassConstant::IS_PROTECTED : 0;
        $val += $this->isPrivate() ? CoreReflectionClassConstant::IS_PRIVATE : 0;
        $val += $this->isFinal() ? self::IS_FINAL : 0;

        return $val;
    }

    /**
     * Get the line number that this constant starts on.
     */
    public function getStartLine(): int
    {
        return $this->node->getStartLine();
    }

    /**
     * Get the line number that this constant ends on.
     */
    public function getEndLine(): int
    {
        return $this->node->getEndLine();
    }

    public function getStartColumn(): int
    {
        return CalculateReflectionColumn::getStartColumn($this->owner->getLocatedSource()->getSource(), $this->node);
    }

    public function getEndColumn(): int
    {
        return CalculateReflectionColumn::getEndColumn($this->owner->getLocatedSource()->getSource(), $this->node);
    }

    /**
     * Get the declaring class
     */
    public function getDeclaringClass(): ReflectionClass
    {
        return $this->owner;
    }

    /**
     * Returns the doc comment for this constant
     */
    public function getDocComment(): string
    {
        return GetLastDocComment::forNode($this->node);
    }

    public function isDeprecated(): bool
    {
        return AnnotationHelper::isDeprecated($this->getDocComment());
    }

    public function __toString(): string
    {
        return ReflectionClassConstantStringCast::toString($this);
    }

    public function getAst(): ClassConst
    {
        return $this->node;
    }

    public function getPositionInAst(): int
    {
        return $this->positionInNode;
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
}
