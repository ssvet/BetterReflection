<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Exception;

use PHPStan\BetterReflection\Reflection\Exception\NoObjectProvided;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PHPStan\BetterReflection\Reflection\Exception\NoObjectProvided
 */
class NoObjectProvidedTest extends TestCase
{
    public function testFromClassName() : void
    {
        $exception = NoObjectProvided::create();

        self::assertInstanceOf(NoObjectProvided::class, $exception);
        self::assertSame('No object provided', $exception->getMessage());
    }
}
