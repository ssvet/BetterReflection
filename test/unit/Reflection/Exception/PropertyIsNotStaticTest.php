<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Exception;

use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\Reflection\Exception\PropertyIsNotStatic;

/**
 * @covers \PHPStan\BetterReflection\Reflection\Exception\PropertyIsNotStatic
 */
class PropertyIsNotStaticTest extends TestCase
{
    public function testFromName(): void
    {
        $exception = PropertyIsNotStatic::fromName('boo');

        self::assertInstanceOf(PropertyIsNotStatic::class, $exception);
        self::assertSame('Property "boo" is not static', $exception->getMessage());
    }
}
