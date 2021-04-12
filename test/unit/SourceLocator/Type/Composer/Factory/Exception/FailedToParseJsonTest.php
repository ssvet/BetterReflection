<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type\Composer\Factory\Exception;

use PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\FailedToParseJson;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\FailedToParseJson
 */
class FailedToParseJsonTest extends TestCase
{
    public function testInFile() : void
    {
        self::assertSame(
            'Could not parse JSON file "foo/bar"',
            FailedToParseJson::inFile('foo/bar')
                ->getMessage()
        );
    }
}
