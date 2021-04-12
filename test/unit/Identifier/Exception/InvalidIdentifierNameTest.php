<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Identifier\Exception;

use PHPStan\BetterReflection\Identifier\Exception\InvalidIdentifierName;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PHPStan\BetterReflection\Identifier\Exception\InvalidIdentifierName
 */
class InvalidIdentifierNameTest extends TestCase
{
    public function testFromInvalidName() : void
    {
        $exception = InvalidIdentifierName::fromInvalidName('!@#$%^&*()');

        self::assertInstanceOf(InvalidIdentifierName::class, $exception);
        self::assertSame('Invalid identifier name "!@#$%^&*()"', $exception->getMessage());
    }
}
