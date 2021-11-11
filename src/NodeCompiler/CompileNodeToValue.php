<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\NodeCompiler;

use PhpParser\ConstExprEvaluator;
use PhpParser\Node;

class CompileNodeToValue
{
    /**
     * Compile an expression from a node into a value.
     *
     * @param Node\Stmt\Expression|Node\Expr $node Node has to be processed by the PhpParser\NodeVisitor\NameResolver
     *
     * @return mixed
     *
     * @throws Exception\UnableToCompileNode
     */
    public function __invoke(Node $node, CompilerContext $context)
    {
        if ($node instanceof Node\Stmt\Expression) {
            return $this($node->expr, $context);
        }

        $fallbackEvaluator = new FallbackEvaluator($context);
        $constExprEvaluator = new ConstExprEvaluator($fallbackEvaluator);
        $fallbackEvaluator->setConstExprEvaluator($constExprEvaluator);

        return $constExprEvaluator->evaluateDirectly($node);
    }


}
