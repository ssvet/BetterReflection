<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type\Composer\Factory\Exception;

use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\InvalidProjectDirectory;

/**
 * @covers \PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\InvalidProjectDirectory
 */
class InvalidProjectDirectoryTest extends TestCase
{
    public function testAtPath(): void
    {
        self::assertSame('Could not locate project directory "foo/bar"', InvalidProjectDirectory::atPath('foo/bar')
            ->getMessage());
    }
}
