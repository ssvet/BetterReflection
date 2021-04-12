<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Located;

use PHPStan\BetterReflection\SourceLocator\Located\InternalLocatedSource;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PHPStan\BetterReflection\SourceLocator\Located\InternalLocatedSource
 */
class InternalLocatedSourceTest extends TestCase
{
    public function testInternalsLocatedSource() : void
    {
        $locatedSource = new InternalLocatedSource('foo', 'fooExt');

        self::assertSame('foo', $locatedSource->getSource());
        self::assertNull($locatedSource->getFileName());
        self::assertTrue($locatedSource->isInternal());
        self::assertFalse($locatedSource->isEvaled());
        self::assertSame('fooExt', $locatedSource->getExtensionName());
    }
}
