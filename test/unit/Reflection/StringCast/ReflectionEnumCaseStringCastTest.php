<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\StringCast;

use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\Reflection\ReflectionEnum;
use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\Fixture\StringCastBackedEnum;
use Roave\BetterReflectionTest\Fixture\StringCastPureEnum;

/**
 * @covers \PHPStan\BetterReflection\Reflection\StringCast\ReflectionEnumCaseStringCast
 */
class ReflectionEnumCaseStringCastTest extends TestCase
{
    /**
     * @var \PHPStan\BetterReflection\SourceLocator\Ast\Locator
     */
    private $astLocator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
    }

    public function testPureEnumCaseToString(): void
    {
        $reflector      = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../../Fixture/StringCastPureEnum.php', $this->astLocator));
        $enumReflection = $reflector->reflectClass(StringCastPureEnum::class);

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);
        self::assertSame("Constant [ public Roave\BetterReflectionTest\Fixture\StringCastPureEnum ENUM_CASE ] { Object }\n", (string) $enumReflection->getCase('ENUM_CASE'));
    }

    public function testBackedEnumCaseToString(): void
    {
        $reflector      = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../../Fixture/StringCastBackedEnum.php', $this->astLocator));
        $enumReflection = $reflector->reflectClass(StringCastBackedEnum::class);

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);
        self::assertSame("Constant [ public string ENUM_CASE ] { string }\n", (string) $enumReflection->getCase('ENUM_CASE'));
    }
}
