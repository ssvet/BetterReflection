<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Located;

use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\SourceLocator\Located\EvaledLocatedSource;

/**
 * @covers \PHPStan\BetterReflection\SourceLocator\Located\EvaledLocatedSource
 */
class EvaledLocatedSourceTest extends TestCase
{
    public function testInternalsLocatedSource(): void
    {
        $locatedSource = new EvaledLocatedSource('foo', 'name');

        self::assertSame('foo', $locatedSource->getSource());
        self::assertSame('name', $locatedSource->getName());
        self::assertNull($locatedSource->getFileName());
        self::assertFalse($locatedSource->isInternal());
        self::assertTrue($locatedSource->isEvaled());
    }
}
