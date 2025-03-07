<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\Exception\EmptyPhpSourceCode;
use PHPStan\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

/**
 * @covers \PHPStan\BetterReflection\SourceLocator\Type\StringSourceLocator
 */
class StringSourceLocatorTest extends TestCase
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

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\PHPStan\BetterReflection\Reflector\Reflector
     */
    private function getMockReflector()
    {
        return $this->createMock(Reflector::class);
    }

    public function testReturnsNullWhenSourceDoesNotContainClass(): void
    {
        $sourceCode = '<?php echo "Hello world!";';

        $locator = new StringSourceLocator($sourceCode, $this->astLocator);

        self::assertNull($locator->locateIdentifier($this->getMockReflector(), new Identifier('does not matter what the class name is', new IdentifierType(IdentifierType::IDENTIFIER_CLASS))));
    }

    public function testReturnsReflectionWhenSourceHasClass(): void
    {
        $sourceCode = '<?php class Foo {}';

        $locator = new StringSourceLocator($sourceCode, $this->astLocator);

        $reflectionClass = $locator->locateIdentifier($this->getMockReflector(), new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)));

        self::assertSame('Foo', $reflectionClass->getName());
    }

    public function testConstructorThrowsExceptionIfEmptyStringGiven(): void
    {
        $this->expectException(EmptyPhpSourceCode::class);
        new StringSourceLocator('', $this->astLocator);
    }
}
