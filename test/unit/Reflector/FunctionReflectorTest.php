<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflector;

use PHPStan\BetterReflection\Reflection\ReflectionFunction;
use PHPStan\BetterReflection\Reflector\ClassReflector;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\Reflector\FunctionReflector;
use PHPStan\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\StringSourceLocator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use function assert;

/**
 * @covers \PHPStan\BetterReflection\Reflector\FunctionReflector
 */
class FunctionReflectorTest extends TestCase
{
    /** @var ClassReflector */
    private $classReflector;

    protected function setUp() : void
    {
        parent::setUp();

        $this->classReflector = BetterReflectionSingleton::instance()->classReflector();
    }

    public function testReflectProxiesToGenericReflectMethod() : void
    {
        $reflection = $this->createMock(ReflectionFunction::class);

        $sourceLocator = $this
            ->getMockBuilder(StringSourceLocator::class)
            ->disableOriginalConstructor()
            ->setMethods(['locateIdentifier'])
            ->getMock();
        assert($sourceLocator instanceof StringSourceLocator && $sourceLocator instanceof MockObject);

        $sourceLocator
            ->expects($this->once())
            ->method('locateIdentifier')
            ->will($this->returnValue($reflection));

        $reflector = new FunctionReflector($sourceLocator, $this->classReflector);
        self::assertSame($reflection, $reflector->reflect('foo'));
    }

    public function testGetFunctionsFromFile() : void
    {
        $functions = (new FunctionReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Functions.php', BetterReflectionSingleton::instance()->astLocator()),
            $this->classReflector
        ))->getAllFunctions();

        self::assertContainsOnlyInstancesOf(ReflectionFunction::class, $functions);
        self::assertCount(2, $functions);
    }

    public function testThrowsExceptionWhenIdentifierNotFound() : void
    {
        $defaultReflector = BetterReflectionSingleton::instance()->functionReflector();

        $this->expectException(IdentifierNotFound::class);

        $defaultReflector->reflect('Something\That\Should\not_exist');
    }
}
