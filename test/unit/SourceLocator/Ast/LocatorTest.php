<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflector;

use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflection\ReflectionFunction;
use PHPStan\BetterReflection\Reflector\ClassReflector;
use PHPStan\BetterReflection\Reflector\ConstantReflector;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\Reflector\FunctionReflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Exception\ParseToAstFailure;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\Located\LocatedSource;
use PHPStan\BetterReflection\SourceLocator\Type\StringSourceLocator;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

/**
 * @covers \PHPStan\BetterReflection\SourceLocator\Ast\Locator
 */
class LocatorTest extends TestCase
{
    /** @var Locator */
    private $locator;

    protected function setUp() : void
    {
        parent::setUp();

        $betterReflection = BetterReflectionSingleton::instance();

        $this->locator = new Locator($betterReflection->phpParser(), static function () use ($betterReflection) : FunctionReflector {
            return $betterReflection->functionReflector();
        });
    }

    private function getIdentifier(string $name, string $type) : Identifier
    {
        return new Identifier($name, new IdentifierType($type));
    }

    public function testReflectingWithinNamespace() : void
    {
        $php = '<?php
        namespace Foo;
        class Bar {}
        ';

        $classInfo = $this->locator->findReflection(
            new ClassReflector(new StringSourceLocator($php, $this->locator)),
            new LocatedSource($php, null),
            $this->getIdentifier('Foo\Bar', IdentifierType::IDENTIFIER_CLASS)
        );

        self::assertInstanceOf(ReflectionClass::class, $classInfo);
    }

    public function testTheReflectionLookupIsCaseInsensitive() : void
    {
        $php = '<?php
        namespace Foo;
        class Bar {}
        ';

        $classInfo = $this->locator->findReflection(
            new ClassReflector(new StringSourceLocator($php, $this->locator)),
            new LocatedSource($php, null),
            $this->getIdentifier('Foo\BAR', IdentifierType::IDENTIFIER_CLASS)
        );

        self::assertInstanceOf(ReflectionClass::class, $classInfo);
    }

    public function testReflectingTopLevelClass() : void
    {
        $php = '<?php
        class Foo {}
        ';

        $classInfo = $this->locator->findReflection(
            new ClassReflector(new StringSourceLocator($php, $this->locator)),
            new LocatedSource($php, null),
            $this->getIdentifier('Foo', IdentifierType::IDENTIFIER_CLASS)
        );

        self::assertInstanceOf(ReflectionClass::class, $classInfo);
    }

    public function testReflectingTopLevelFunction() : void
    {
        $php = '<?php
        function foo() {}
        ';

        $functionInfo = $this->locator->findReflection(
            new FunctionReflector(new StringSourceLocator($php, $this->locator), BetterReflectionSingleton::instance()->classReflector()),
            new LocatedSource($php, null),
            $this->getIdentifier('foo', IdentifierType::IDENTIFIER_FUNCTION)
        );

        self::assertInstanceOf(ReflectionFunction::class, $functionInfo);
    }

    public function testReflectingTopLevelConstantByConst() : void
    {
        $php = '<?php
        const FOO = 1;
        ';

        $constantInfo = $this->locator->findReflection(
            new ConstantReflector(new StringSourceLocator($php, $this->locator), BetterReflectionSingleton::instance()->classReflector()),
            new LocatedSource($php, null),
            $this->getIdentifier('FOO', IdentifierType::IDENTIFIER_CONSTANT)
        );

        self::assertInstanceOf(ReflectionConstant::class, $constantInfo);
    }

    public function testReflectingTopLevelConstantByDefine() : void
    {
        $php = '<?php
        define("FOO", 1);
        ';

        $constantInfo = $this->locator->findReflection(
            new ConstantReflector(new StringSourceLocator($php, $this->locator), BetterReflectionSingleton::instance()->classReflector()),
            new LocatedSource($php, null),
            $this->getIdentifier('FOO', IdentifierType::IDENTIFIER_CONSTANT)
        );

        self::assertInstanceOf(ReflectionConstant::class, $constantInfo);
    }

    public function testReflectThrowsExeptionWhenClassNotFoundAndNoNodesExist() : void
    {
        $php = '<?php';

        $this->expectException(IdentifierNotFound::class);
        $this->locator->findReflection(
            new ClassReflector(new StringSourceLocator($php, $this->locator)),
            new LocatedSource($php, null),
            $this->getIdentifier('Foo', IdentifierType::IDENTIFIER_CLASS)
        );
    }

    public function testReflectThrowsExeptionWhenClassNotFoundButNodesExist() : void
    {
        $php = "<?php
        namespace Foo;
        echo 'Hello world';
        ";

        $this->expectException(IdentifierNotFound::class);
        $this->locator->findReflection(
            new ClassReflector(new StringSourceLocator($php, $this->locator)),
            new LocatedSource($php, null),
            $this->getIdentifier('Foo', IdentifierType::IDENTIFIER_CLASS)
        );
    }

    public function testFindReflectionsOfTypeThrowsParseToAstFailureExceptionWithInvalidCode() : void
    {
        $phpCode = '<?php syntax error';

        $identifierType = new IdentifierType(IdentifierType::IDENTIFIER_CLASS);
        $sourceLocator  = new StringSourceLocator($phpCode, $this->locator);
        $reflector      = new ClassReflector($sourceLocator);

        $locatedSource = new LocatedSource($phpCode, null);

        $this->expectException(ParseToAstFailure::class);
        $this->locator->findReflectionsOfType($reflector, $locatedSource, $identifierType);
    }
}
