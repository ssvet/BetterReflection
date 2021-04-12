<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type\Composer\Factory\Exception;

use PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\InvalidProjectDirectory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\InvalidProjectDirectory
 */
class InvalidProjectDirectoryTest extends TestCase
{
    public function testAtPath() : void
    {
        self::assertSame(
            'Could not locate project directory "foo/bar"',
            InvalidProjectDirectory::atPath('foo/bar')
                ->getMessage()
        );
    }
}
