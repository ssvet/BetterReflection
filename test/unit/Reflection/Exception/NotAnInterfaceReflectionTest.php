<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Exception;

use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\Reflection\Exception\NotAnInterfaceReflection;
use PHPStan\BetterReflection\Reflector\ClassReflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\Fixture;

/**
 * @covers \PHPStan\BetterReflection\Reflection\Exception\NotAnInterfaceReflection
 */
class NotAnInterfaceReflectionTest extends TestCase
{
    /** @var Locator */
    private $astLocator;

    protected function setUp() : void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
    }

    public function testFromClass() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../../Fixture/ExampleClass.php', $this->astLocator));
        $exception = NotAnInterfaceReflection::fromReflectionClass($reflector->reflect(Fixture\ExampleClass::class));

        self::assertInstanceOf(NotAnInterfaceReflection::class, $exception);
        self::assertSame(
            'Provided node "' . Fixture\ExampleClass::class . '" is not interface, but "class"',
            $exception->getMessage()
        );
    }

    public function testFromTrait() : void
    {
        $reflector = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../../Fixture/ExampleClass.php', $this->astLocator));
        $exception = NotAnInterfaceReflection::fromReflectionClass($reflector->reflect(Fixture\ExampleTrait::class));

        self::assertInstanceOf(NotAnInterfaceReflection::class, $exception);
        self::assertSame(
            'Provided node "' . Fixture\ExampleTrait::class . '" is not interface, but "trait"',
            $exception->getMessage()
        );
    }
}
