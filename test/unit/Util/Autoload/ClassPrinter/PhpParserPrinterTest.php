<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util\Autoload\ClassPrinter;

use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Util\Autoload\ClassPrinter\PhpParserPrinter;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflectionTest\Fixture\TestClassForPhpParserPrinterTest;

/**
 * @covers \PHPStan\BetterReflection\Util\Autoload\ClassPrinter\PhpParserPrinter
 */
final class PhpParserPrinterTest extends TestCase
{
    public function testPrinting() : void
    {
        self::assertSame(
            <<<'PHP'
namespace Roave\BetterReflectionTest\Fixture;

class TestClassForPhpParserPrinterTest
{
    public function foo() : \PHPStan\BetterReflection\TypesFinder\FindReturnType
    {
        return new \PHPStan\BetterReflection\TypesFinder\FindReturnType();
    }
}
PHP
            ,
            (new PhpParserPrinter())->__invoke(
                ReflectionClass::createFromName(TestClassForPhpParserPrinterTest::class)
            )
        );
    }
}
