<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type\Composer\Factory\Exception;

use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\FailedToParseJson;

/**
 * @covers \PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\FailedToParseJson
 */
class FailedToParseJsonTest extends TestCase
{
    public function testInFile(): void
    {
        self::assertSame('Could not parse JSON file "foo/bar"', FailedToParseJson::inFile('foo/bar')
            ->getMessage());
    }
}
