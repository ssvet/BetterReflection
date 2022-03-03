<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection;

use PhpParser\Node;
use PhpParser\Node\Stmt\Namespace_ as NamespaceNode;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\BetterReflection\NodeCompiler\CompiledValue;
use PHPStan\BetterReflection\NodeCompiler\CompileNodeToValue;
use PHPStan\BetterReflection\NodeCompiler\CompilerContext;
use PHPStan\BetterReflection\Reflection\Annotation\AnnotationHelper;
use PHPStan\BetterReflection\Reflection\Exception\InvalidConstantNode;
use PHPStan\BetterReflection\Reflection\StringCast\ReflectionConstantStringCast;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Located\LocatedSource;
use PHPStan\BetterReflection\Util\CalculateReflectionColumn;
use PHPStan\BetterReflection\Util\ConstantNodeChecker;
use PHPStan\BetterReflection\Util\GetLastDocComment;

use function array_slice;
use function assert;
use function count;
use function explode;
use function implode;
use function is_int;
use function substr_count;

class ReflectionConstant implements Reflection
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
     * @var \PhpParser\Node\Expr\FuncCall|\PhpParser\Node\Stmt\Const_
     */
    private $node;
    /**
     * @var \PHPStan\BetterReflection\SourceLocator\Located\LocatedSource
     */
    private $locatedSource;
    /**
     * @var NamespaceNode|null
     */
    private $declaringNamespace;
    /**
     * @var int|null
     */
    private $positionInNode;
    /**
     * @param \PhpParser\Node\Expr\FuncCall|\PhpParser\Node\Stmt\Const_ $node
     */
    private function __construct(Reflector $reflector, $node, LocatedSource $locatedSource, ?NamespaceNode $declaringNamespace = null, ?int $positionInNode = null)
    {
        $this->reflector = $reflector;
        $this->node = $node;
        $this->locatedSource = $locatedSource;
        $this->declaringNamespace = $declaringNamespace;
        $this->positionInNode = $positionInNode;
    }

    /**
     * Create a ReflectionConstant by name, using default reflectors etc.
     *
     * @deprecated Use Reflector instead.
     *
     * @throws IdentifierNotFound
     */
    public static function createFromName(string $constantName): self
    {
        return (new BetterReflection())->reflector()->reflectConstant($constantName);
    }

    /**
     * Create a reflection of a constant
     *
     * @internal
     *
     * @param Node\Stmt\Const_|Node\Expr\FuncCall $node Node has to be processed by the PhpParser\NodeVisitor\NameResolver
     */
    public static function createFromNode(Reflector $reflector, Node $node, LocatedSource $locatedSource, ?NamespaceNode $namespace = null, ?int $positionInNode = null): self
    {
        if ($node instanceof Node\Stmt\Const_) {
            assert(is_int($positionInNode));

            return self::createFromConstKeyword($reflector, $node, $locatedSource, $namespace, $positionInNode);
        }
        return self::createFromDefineFunctionCall($reflector, $node, $locatedSource);
    }

    private static function createFromConstKeyword(Reflector $reflector, Node\Stmt\Const_ $node, LocatedSource $locatedSource, ?NamespaceNode $namespace, int $positionInNode): self
    {
        return new self($reflector, $node, $locatedSource, $namespace, $positionInNode);
    }

    /**
     * @throws InvalidConstantNode
     */
    private static function createFromDefineFunctionCall(Reflector $reflector, Node\Expr\FuncCall $node, LocatedSource $locatedSource): self
    {
        ConstantNodeChecker::assertValidDefineFunctionCall($node);
        return new self($reflector, $node, $locatedSource);
    }

    /**
     * Get the "short" name of the constant (e.g. for A\B\FOO, this will return
     * "FOO").
     */
    public function getShortName(): string
    {
        if ($this->node instanceof Node\Expr\FuncCall) {
            $nameParts = explode('\\', $this->getNameFromDefineFunctionCall($this->node));

            return $nameParts[count($nameParts) - 1];
        }

        /** @psalm-suppress PossiblyNullArrayOffset */
        return $this->node->consts[$this->positionInNode]->name->name;
    }

    /**
     * Get the "full" name of the constant (e.g. for A\B\FOO, this will return
     * "A\B\FOO").
     */
    public function getName(): string
    {
        if (! $this->inNamespace()) {
            return $this->getShortName();
        }

        if ($this->node instanceof Node\Expr\FuncCall) {
            return $this->getNameFromDefineFunctionCall($this->node);
        }

        /** @psalm-suppress PossiblyNullArrayOffset */
        return $this->node->consts[$this->positionInNode]->namespacedName->toString();
    }

    /**
     * Get the "namespace" name of the constant (e.g. for A\B\FOO, this will
     * return "A\B").
     */
    public function getNamespaceName(): string
    {
        if (! $this->inNamespace()) {
            return '';
        }

        $namespaceParts = $this->node instanceof Node\Expr\FuncCall
            ? array_slice(explode('\\', $this->getNameFromDefineFunctionCall($this->node)), 0, -1)
            : $this->declaringNamespace->name->parts;

        return implode('\\', $namespaceParts);
    }

    /**
     * Decide if this constant is part of a namespace. Returns false if the constant
     * is in the global namespace or does not have a specified namespace.
     *
     * @psalm-assert-if-true NamespaceNode $this->declaringNamespace
     * @psalm-assert-if-true Node\Name $this->declaringNamespace->name
     */
    public function inNamespace(): bool
    {
        if ($this->node instanceof Node\Expr\FuncCall) {
            return substr_count($this->getNameFromDefineFunctionCall($this->node), '\\') !== 0;
        }

        return $this->declaringNamespace !== null
            && $this->declaringNamespace->name !== null;
    }

    public function getExtensionName(): ?string
    {
        return $this->locatedSource->getExtensionName();
    }

    /**
     * Is this an internal constant?
     */
    public function isInternal(): bool
    {
        return $this->locatedSource->isInternal();
    }

    /**
     * Is this a user-defined function (will always return the opposite of
     * whatever isInternal returns).
     */
    public function isUserDefined(): bool
    {
        return ! $this->isInternal();
    }

    public function isDeprecated(): bool
    {
        return AnnotationHelper::isDeprecated($this->getDocComment());
    }

    /**
     * @param mixed $value
     */
    public function populateValue($value): void
    {
        $this->compiledValue = new CompiledValue($value);
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        if ($this->compiledValue !== null) {
            return $this->compiledValue->value;
        }

        if ($this->node instanceof Node\Expr\FuncCall) {
            $argumentValueNode = $this->node->args[1];
            assert($argumentValueNode instanceof Node\Arg);
            $valueNode = $argumentValueNode->value;
        } else {
            /** @psalm-suppress PossiblyNullArrayOffset */
            $valueNode = $this->node->consts[$this->positionInNode]->value;
        }

        $this->compiledValue = (new CompileNodeToValue())->__invoke($valueNode, new CompilerContext($this->reflector, $this));

        return $this->compiledValue->value;
    }

    public function getFileName(): ?string
    {
        return $this->locatedSource->getFileName();
    }

    public function getLocatedSource(): LocatedSource
    {
        return $this->locatedSource;
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
        return CalculateReflectionColumn::getStartColumn($this->locatedSource->getSource(), $this->node);
    }

    public function getEndColumn(): int
    {
        return CalculateReflectionColumn::getEndColumn($this->locatedSource->getSource(), $this->node);
    }

    /**
     * Returns the doc comment for this constant
     */
    public function getDocComment(): string
    {
        return GetLastDocComment::forNode($this->node);
    }

    public function __toString(): string
    {
        return ReflectionConstantStringCast::toString($this);
    }

    /**
     * @return Node\Stmt\Const_|Node\Expr\FuncCall
     */
    public function getAst(): Node
    {
        return $this->node;
    }

    private function getNameFromDefineFunctionCall(Node\Expr\FuncCall $node): string
    {
        $argumentNameNode = $node->args[0];
        assert($argumentNameNode instanceof Node\Arg);
        $nameNode = $argumentNameNode->value;
        assert($nameNode instanceof Node\Scalar\String_);

        return $nameNode->value;
    }
}
