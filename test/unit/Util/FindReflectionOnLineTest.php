<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util;

use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflection\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;
use PHPStan\BetterReflection\Util\FindReflectionOnLine;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

/**
 * @covers \PHPStan\BetterReflection\Util\FindReflectionOnLine
 */
class FindReflectionOnLineTest extends TestCase
{
    /** @var FindReflectionOnLine */
    private $finder;

    protected function setUp() : void
    {
        parent::setUp();

        $this->finder = BetterReflectionSingleton::instance()->findReflectionsOnLine();
    }

    public function testInvokeFindsClass() : void
    {
        $reflection = ($this->finder)(__DIR__ . '/../Fixture/FindReflectionOnLineFixture.php', 10);

        self::assertInstanceOf(ReflectionClass::class, $reflection);
        self::assertSame('SomeFooClass', $reflection->getName());
    }

    public function testInvokeFindsTrait() : void
    {
        $reflection = ($this->finder)(__DIR__ . '/../Fixture/FindReflectionOnLineFixture.php', 19);

        self::assertInstanceOf(ReflectionClass::class, $reflection);
        self::assertSame('SomeFooTrait', $reflection->getName());
    }

    public function testInvokeFindsInterface() : void
    {
        $reflection = ($this->finder)(__DIR__ . '/../Fixture/FindReflectionOnLineFixture.php', 24);

        self::assertInstanceOf(ReflectionClass::class, $reflection);
        self::assertSame('SomeFooInterface', $reflection->getName());
    }

    public function testInvokeFindsMethod() : void
    {
        $reflection = ($this->finder)(__DIR__ . '/../Fixture/FindReflectionOnLineFixture.php', 14);

        self::assertInstanceOf(ReflectionMethod::class, $reflection);
        self::assertSame('someMethod', $reflection->getName());
    }

    public function testInvokeFindsFunction() : void
    {
        $reflection = ($this->finder)(__DIR__ . '/../Fixture/FindReflectionOnLineFixture.php', 5);

        self::assertInstanceOf(ReflectionFunction::class, $reflection);
        self::assertSame('fooFunc', $reflection->getName());
    }

    public function testInvokeFindsConstantByDefine() : void
    {
        $reflection = ($this->finder)(__DIR__ . '/../Fixture/FindReflectionOnLineFixture.php', 30);

        self::assertInstanceOf(ReflectionConstant::class, $reflection);
        self::assertSame('FOO', $reflection->getName());
    }

    public function testInvokeFindsConstantByConst() : void
    {
        $reflection = ($this->finder)(__DIR__ . '/../Fixture/FindReflectionOnLineFixture.php', 32);

        self::assertInstanceOf(ReflectionConstant::class, $reflection);
        self::assertSame('BOO', $reflection->getName());
    }

    public function testInvokeReturnsNullWhenNothingFound() : void
    {
        self::assertNull(($this->finder)(__DIR__ . '/../Fixture/FindReflectionOnLineFixture.php', 1));
    }

    public function testInvokeFindsClassWithImplementedInterface() : void
    {
        $reflection = ($this->finder)(__DIR__ . '/../Fixture/FindReflectionOnLineFixture.php', 26);

        self::assertInstanceOf(ReflectionClass::class, $reflection);
        self::assertSame('SomeFooClassWithImplementedInterface', $reflection->getName());
    }
}
