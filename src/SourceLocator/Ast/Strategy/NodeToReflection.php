<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\SourceLocator\Ast\Strategy;

use PhpParser\Node;
use PHPStan\BetterReflection\Reflection\Exception\InvalidConstantNode;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflection\ReflectionFunction;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Located\LocatedSource;

/**
 * @internal
 */
class NodeToReflection implements AstConversionStrategy
{
    /**
     * Take an AST node in some located source (potentially in a namespace) and
     * convert it to a Reflection
     */
    public function __invoke(
        Reflector $reflector,
        Node $node,
        LocatedSource $locatedSource,
        ?Node\Stmt\Namespace_ $namespace,
        ?int $positionInNode = null
    ) : ?Reflection {
        if ($node instanceof Node\Stmt\ClassLike) {
            return ReflectionClass::createFromNode(
                $reflector,
                $node,
                $locatedSource,
                $namespace
            );
        }

        if ($node instanceof Node\Stmt\ClassMethod
            || $node instanceof Node\Stmt\Function_
            || $node instanceof Node\Expr\Closure
        ) {
            return ReflectionFunction::createFromNode(
                $reflector,
                $node,
                $locatedSource,
                $namespace
            );
        }

        if ($node instanceof Node\Stmt\Const_) {
            return ReflectionConstant::createFromNode($reflector, $node, $locatedSource, $namespace, $positionInNode);
        }

        if ($node instanceof Node\Expr\FuncCall) {
            try {
                return ReflectionConstant::createFromNode($reflector, $node, $locatedSource);
            } catch (InvalidConstantNode $e) {
                // Ignore
            }
        }

        return null;
    }
}
