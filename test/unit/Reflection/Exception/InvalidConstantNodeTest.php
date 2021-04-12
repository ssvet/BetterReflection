<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Exception;

use PhpParser\Node;
use PHPStan\BetterReflection\Reflection\Exception\InvalidConstantNode;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PHPStan\BetterReflection\Reflection\Exception\InvalidConstantNode
 */
class InvalidConstantNodeTest extends TestCase
{
    public function testCreate() : void
    {
        $exception = InvalidConstantNode::create(new Node\Name('Whatever'));

        self::assertInstanceOf(InvalidConstantNode::class, $exception);
        self::assertStringStartsWith('Invalid constant node', $exception->getMessage());
    }
}
