<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\StringCast;

use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\Fixture\StringCastProperties;

/**
 * @covers \PHPStan\BetterReflection\Reflection\StringCast\ReflectionPropertyStringCast
 */
class ReflectionPropertyStringCastTest extends TestCase
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

    public function toStringProvider(): array
    {
        return [
            ['publicProperty', 'Property [ <default> public $publicProperty ]'],
            ['protectedProperty', 'Property [ <default> protected $protectedProperty ]'],
            ['privateProperty', 'Property [ <default> private $privateProperty ]'],
            ['publicStaticProperty', 'Property [ public static $publicStaticProperty ]'],
            ['namedTypeProperty', 'Property [ <default> public int $namedTypeProperty ]'],
            ['unionTypeProperty', 'Property [ <default> public int|bool $unionTypeProperty ]'],
            ['nullableTypeProperty', 'Property [ <default> public ?int $nullableTypeProperty ]'],
            ['readOnlyProperty', 'Property [ <default> public readonly int $readOnlyProperty ]'],
        ];
    }

    /**
     * @dataProvider toStringProvider
     */
    public function testToString(string $propertyName, string $expectedString): void
    {
        $reflector       = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../../Fixture/StringCastProperties.php', $this->astLocator));
        $classReflection = $reflector->reflectClass(StringCastProperties::class);

        self::assertSame($expectedString, (string) $classReflection->getProperty($propertyName));
    }
}
