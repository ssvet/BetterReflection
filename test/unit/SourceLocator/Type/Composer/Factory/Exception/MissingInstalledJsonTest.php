<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type\Composer\Factory\Exception;

use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\MissingInstalledJson;

/**
 * @covers \PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\MissingInstalledJson
 */
class MissingInstalledJsonTest extends TestCase
{
    public function testInProjectPath(): void
    {
        self::assertSame('Could not locate a "composer/installed.json" file in "foo/bar/vendor"', MissingInstalledJson::inProjectPath('foo/bar/vendor')
            ->getMessage());
    }
}
