<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type\Composer\Factory\Exception;

use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\MissingComposerJson;

/**
 * @covers \PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\MissingComposerJson
 */
class MissingComposerJsonTest extends TestCase
{
    public function testInProjectPath(): void
    {
        self::assertSame('Could not locate a "composer.json" file in "foo/bar"', MissingComposerJson::inProjectPath('foo/bar')
            ->getMessage());
    }
}
