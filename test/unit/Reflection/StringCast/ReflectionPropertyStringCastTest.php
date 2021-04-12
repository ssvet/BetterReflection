<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\StringCast;

use PHPStan\BetterReflection\Reflector\ClassReflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\Fixture\StringCastProperties;

/**
 * @covers \PHPStan\BetterReflection\Reflection\StringCast\ReflectionPropertyStringCast
 */
class ReflectionPropertyStringCastTest extends TestCase
{
    /** @var Locator */
    private $astLocator;

    protected function setUp() : void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
    }

    public function toStringProvider() : array
    {
        return [
            ['publicProperty', 'Property [ <default> public $publicProperty ]'],
            ['protectedProperty', 'Property [ <default> protected $protectedProperty ]'],
            ['privateProperty', 'Property [ <default> private $privateProperty ]'],
            ['publicStaticProperty', 'Property [ public static $publicStaticProperty ]'],
        ];
    }

    /**
     * @dataProvider toStringProvider
     */
    public function testToString(string $propertyName, string $expectedString) : void
    {
        $reflector       = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../../Fixture/StringCastProperties.php', $this->astLocator));
        $classReflection = $reflector->reflect(StringCastProperties::class);

        self::assertSame($expectedString, (string) $classReflection->getProperty($propertyName));
    }
}
