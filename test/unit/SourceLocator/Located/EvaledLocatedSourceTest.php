<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Located;

use PHPStan\BetterReflection\SourceLocator\Located\EvaledLocatedSource;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PHPStan\BetterReflection\SourceLocator\Located\EvaledLocatedSource
 */
class EvaledLocatedSourceTest extends TestCase
{
    public function testInternalsLocatedSource() : void
    {
        $locatedSource = new EvaledLocatedSource('foo');

        self::assertSame('foo', $locatedSource->getSource());
        self::assertNull($locatedSource->getFileName());
        self::assertFalse($locatedSource->isInternal());
        self::assertTrue($locatedSource->isEvaled());
    }
}
