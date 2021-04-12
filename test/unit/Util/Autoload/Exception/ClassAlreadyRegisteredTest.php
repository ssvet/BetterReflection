<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util\Autoload\Exception;

use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Util\Autoload\Exception\ClassAlreadyRegistered;
use PHPUnit\Framework\TestCase;
use function sprintf;
use function uniqid;

/**
 * @covers \PHPStan\BetterReflection\Util\Autoload\Exception\ClassAlreadyRegistered
 */
final class ClassAlreadyRegisteredTest extends TestCase
{
    public function testFromReflectionClass() : void
    {
        $className = uniqid('class name', true);

        $reflection = $this->createMock(ReflectionClass::class);
        $reflection->expects(self::any())->method('getName')->willReturn($className);

        $exception = ClassAlreadyRegistered::fromReflectionClass($reflection);

        self::assertInstanceOf(ClassAlreadyRegistered::class, $exception);
        self::assertSame(
            sprintf('Class %s already registered', $className),
            $exception->getMessage()
        );
    }
}
