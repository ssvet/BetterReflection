<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\NodeCompiler;

use PhpParser\ConstExprEvaluator;
use PhpParser\Node;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionClassConstant;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\Util\FileHelper;
use function class_exists;

class FallbackEvaluator
{

    /** @var CompilerContext */
    private $context;

    /** @var ConstExprEvaluator */
    private $constExprEvaluator;

    public function __construct(CompilerContext $context)
    {
        $this->context = $context;
    }

    public function setConstExprEvaluator(ConstExprEvaluator $constExprEvaluator): void
    {
        $this->constExprEvaluator = $constExprEvaluator;
    }

    public function __invoke(Node\Expr $node)
    {
        if ($node instanceof Node\Expr\ConstFetch) {
            return $this->compileConstFetch($node);
        }

        if ($node instanceof Node\Expr\ClassConstFetch) {
            return $this->compileClassConstFetch($node);
        }

        if ($node instanceof Node\Scalar\MagicConst\Dir) {
            return $this->compileDirConstant();
        }

        if ($node instanceof Node\Scalar\MagicConst\Class_) {
            return $this->compileClassConstant();
        }

        if ($node instanceof Node\Scalar\MagicConst\File) {
            return $this->context->getFileName();
        }

        if ($node instanceof Node\Scalar\MagicConst\Line) {
            return $node->getLine();
        }

        if ($node instanceof Node\Scalar\MagicConst\Namespace_) {
            return $this->context->getNamespace() ?? '';
        }

        if ($node instanceof Node\Scalar\MagicConst\Method) {
            if ($this->context->hasSelf()) {
                if ($this->context->getFunctionName() !== null) {
                    return sprintf('%s::%s', $this->context->getSelf()->getName(), $this->context->getFunctionName());
                }

                return '';
            }

            if ($this->context->getFunctionName() !== null) {
                return $this->context->getFunctionName();
            }

            return '';
        }

        if ($node instanceof Node\Scalar\MagicConst\Function_) {
            if ($this->context->getFunctionName() !== null) {
                return $this->context->getFunctionName();
            }

            return '';
        }

        if ($node instanceof Node\Scalar\MagicConst\Trait_) {
            if ($this->context->hasSelf()) {
                $class = $this->context->getSelf();
                if ($class->isTrait()) {
                    return $class->getName();
                }
            }

            return '';
        }

        if ($node instanceof Node\Expr\New_ && $node->class instanceof Node\Name) {
            $className = $this->resolveClassNameForClassNameConstant($node->class->toString());
            if (class_exists($className)) {
                $evaluatedArgs = [];
                foreach ($node->getArgs() as $i => $arg) {
                    $name = $i;
                    if ($arg->name !== null) {
                        $name = $arg->name->toString();
                    }
                    $evaluatedArgs[$name] = $this->constExprEvaluator->evaluateDirectly($arg->value);
                }

                return new $className(...$evaluatedArgs);
            }
        }

        throw Exception\UnableToCompileNode::forUnRecognizedExpressionInContext($node, $this->context);
    }

    /**
     * Compile constant expressions
     *
     * @return scalar|array<scalar>|null
     *
     * @throws Exception\UnableToCompileNode
     */
    private function compileConstFetch(Node\Expr\ConstFetch $constNode)
    {
        $constantName = $constNode->name->toString();
        switch (strtolower($constantName)) {
            case 'null':
                return null;
            case 'false':
                return false;
            case 'true':
                return true;
            default:
                if ($this->context->getNamespace() !== null && ! $constNode->name->isFullyQualified()) {
                    $namespacedName = sprintf('%s\\%s', $this->context->getNamespace(), $constantName);
                    if (defined($namespacedName)) {
                        return constant($namespacedName);
                    }

                    try {
                        return ReflectionConstant::createFromName($namespacedName)->getValue();
                    } catch (IdentifierNotFound $e) {
                        // pass
                    }
                }

                if (defined($constantName)) {
                    return constant($constantName);
                }

                try {
                    return ReflectionConstant::createFromName($constantName)->getValue();
                } catch (IdentifierNotFound $e) {
                    throw Exception\UnableToCompileNode::becauseOfNotFoundConstantReference($this->context, $constNode);
                }
        }
    }

    /**
     * Compile class constants
     *
     * @return scalar|array<scalar>|null
     *
     * @throws IdentifierNotFound
     * @throws Exception\UnableToCompileNode If a referenced constant could not be located on the expected referenced class.
     */
    private function compileClassConstFetch(Node\Expr\ClassConstFetch $node)
    {
        assert($node->name instanceof Node\Identifier);
        $nodeName = $node->name->name;
        assert($node->class instanceof Node\Name);
        $className = $node->class->toString();

        if ($nodeName === 'class') {
            return $this->resolveClassNameForClassNameConstant($className);
        }

        $classInfo = null;

        if ($className === 'self' || $className === 'static') {
            $classInfo = $this->context->getSelf()->hasConstant($nodeName) ? $this->context->getSelf() : null;
        } elseif ($className === 'parent') {
            $classInfo = $this->context->getSelf()->getParentClass();
        }

        if ($classInfo === null) {
            $classInfo = $this->context->getReflector()->reflect($className);
            assert($classInfo instanceof ReflectionClass);
        }

        $reflectionConstant = $classInfo->getReflectionConstant($nodeName);

        if (! $reflectionConstant instanceof ReflectionClassConstant) {
            throw Exception\UnableToCompileNode::becauseOfNotFoundClassConstantReference($this->context, $classInfo, $node);
        }

        $newEvaluator = new self(new CompilerContext($this->context->getReflector(), $this->context->hasFileName() ? $this->context->getFileName() : null, $classInfo, $this->context->getNamespace(), $this->context->getFunctionName()));
        $constExprEvaluator = new ConstExprEvaluator($newEvaluator);
        $newEvaluator->setConstExprEvaluator($constExprEvaluator);

        return $constExprEvaluator->evaluateDirectly($reflectionConstant->getAst()->consts[$reflectionConstant->getPositionInAst()]->value);
    }

    /**
     * Compile a __DIR__ node
     */
    private function compileDirConstant() : string
    {
        return FileHelper::normalizeWindowsPath(dirname(realpath($this->context->getFileName())));
    }

    /**
     * Compiles magic constant __CLASS__
     */
    private function compileClassConstant() : string
    {
        return $this->context->hasSelf() ? $this->context->getSelf()->getName() : '';
    }

    private function resolveClassNameForClassNameConstant(string $className) : string
    {
        if ($className === 'self' || $className === 'static') {
            return $this->context->getSelf()->getName();
        }

        if ($className === 'parent') {
            $parentClass = $this->context->getSelf()->getParentClass();
            assert($parentClass instanceof ReflectionClass);

            return $parentClass->getName();
        }

        return $className;
    }

}
