<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util;

use PhpParser\Node;
use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\Reflection\Exception\InvalidConstantNode;
use PHPStan\BetterReflection\Util\ConstantNodeChecker;

/**
 * @covers \PHPStan\BetterReflection\Util\ConstantNodeChecker
 */
class ConstantNodeCheckerTest extends TestCase
{
    public function testWithoutName(): void
    {
        $node = new Node\Expr\FuncCall(new Node\Expr\Variable('foo'));

        self::expectException(InvalidConstantNode::class);

        ConstantNodeChecker::assertValidDefineFunctionCall($node);
    }

    public function testDifferentName(): void
    {
        $node = new Node\Expr\FuncCall(new Node\Name('foo'));

        self::expectException(InvalidConstantNode::class);

        ConstantNodeChecker::assertValidDefineFunctionCall($node);
    }

    public function testInvalidArgumentsCount(): void
    {
        $node = new Node\Expr\FuncCall(new Node\Name('define'), [new Node\Arg(new Node\Scalar\String_('FOO'))]);

        self::expectException(InvalidConstantNode::class);

        ConstantNodeChecker::assertValidDefineFunctionCall($node);
    }

    public function testInvalidWithVariadicPlaceholderAsName(): void
    {
        $node = new Node\Expr\FuncCall(new Node\Name('define'), [new Node\VariadicPlaceholder()]);

        self::expectException(InvalidConstantNode::class);

        ConstantNodeChecker::assertValidDefineFunctionCall($node);
    }

    public function testInvalidWithVariadicPlaceholderAsValue(): void
    {
        $node = new Node\Expr\FuncCall(new Node\Name('define'), [new Node\Arg(new Node\Scalar\String_('FOO')), new Node\VariadicPlaceholder()]);

        self::expectException(InvalidConstantNode::class);

        ConstantNodeChecker::assertValidDefineFunctionCall($node);
    }

    public function testNameAsNotString(): void
    {
        $node = new Node\Expr\FuncCall(new Node\Name('define'), [new Node\Arg(new Node\Expr\Variable('FOO')), new Node\Arg(new Node\Scalar\String_('foo'))]);

        self::expectException(InvalidConstantNode::class);

        ConstantNodeChecker::assertValidDefineFunctionCall($node);
    }

    public function testValueAsFunctionCall(): void
    {
        $node = new Node\Expr\FuncCall(new Node\Name('define'), [new Node\Arg(new Node\Scalar\String_('FOO')), new Node\Arg(new Node\Expr\FuncCall(new Node\Name('fopen')))]);

        self::expectException(InvalidConstantNode::class);

        ConstantNodeChecker::assertValidDefineFunctionCall($node);
    }

    public function testValueAsVariable(): void
    {
        $node = new Node\Expr\FuncCall(new Node\Name('define'), [new Node\Arg(new Node\Scalar\String_('FOO')), new Node\Arg(new Node\Expr\Variable('foo'))]);

        self::expectException(InvalidConstantNode::class);

        ConstantNodeChecker::assertValidDefineFunctionCall($node);
    }

    /**
     * @return Node\Expr[][]
     */
    public function validValuesProvider(): array
    {
        return [
            [new Node\Scalar\String_('foo')],
            [new Node\Scalar\LNumber(1)],
            [new Node\Scalar\DNumber(1.0)],
            [new Node\Expr\UnaryMinus(new Node\Scalar\LNumber(1))],
            [new Node\Expr\ConstFetch(new Node\Name('true'))],
            [new Node\Expr\ConstFetch(new Node\Name('false'))],
            [new Node\Expr\ConstFetch(new Node\Name('null'))],
            [new Node\Scalar\MagicConst\Dir()],
            [new Node\Expr\BinaryOp\BitwiseAnd(new Node\Scalar\LNumber(1), new Node\Scalar\LNumber(2))],
            [new Node\Expr\BinaryOp\BitwiseOr(new Node\Scalar\LNumber(1), new Node\Scalar\LNumber(2))],
            [new Node\Expr\AssignOp\Concat(new Node\Scalar\String_('foo'), new Node\Scalar\String_('boo'))],
            [new Node\Expr\FuncCall(new Node\Name('constant'), [new Node\Arg(new Node\Scalar\String_('STDIN'))])],
        ];
    }

    /**
     * @dataProvider validValuesProvider
     */
    public function testValidValues(Node\Expr $valueNode): void
    {
        self::expectNotToPerformAssertions();

        $node = new Node\Expr\FuncCall(new Node\Name('define'), [new Node\Arg(new Node\Scalar\String_('FOO')), new Node\Arg($valueNode)]);

        ConstantNodeChecker::assertValidDefineFunctionCall($node);
    }
}
