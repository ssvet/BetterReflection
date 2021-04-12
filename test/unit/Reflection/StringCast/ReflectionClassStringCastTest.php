<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\StringCast;

use Exception;
use PHPStan\BetterReflection\Reflection\ReflectionObject;
use PHPStan\BetterReflection\Reflector\ClassReflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\StringSourceLocator;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\Fixture\StringCastClass;
use Roave\BetterReflectionTest\Fixture\StringCastClassObject;
use function file_get_contents;

/**
 * @covers \PHPStan\BetterReflection\Reflection\StringCast\ReflectionClassStringCast
 */
class ReflectionClassStringCastTest extends TestCase
{
    /** @var Locator */
    private $astLocator;

    /** @var SourceStubber */
    private $sourceStubber;

    protected function setUp() : void
    {
        parent::setUp();

        $betterReflection = BetterReflectionSingleton::instance();

        $this->astLocator    = $betterReflection->astLocator();
        $this->sourceStubber = $betterReflection->sourceStubber();
    }

    public function testToString() : void
    {
        $reflector       = new ClassReflector(new SingleFileSourceLocator(__DIR__ . '/../../Fixture/StringCastClass.php', $this->astLocator));
        $classReflection = $reflector->reflect(StringCastClass::class);

        self::assertStringMatchesFormat(
            file_get_contents(__DIR__ . '/../../Fixture/StringCastClassExpected.txt'),
            $classReflection->__toString()
        );
    }

    public function testFinalClassToString() : void
    {
        $php = '<?php final class StringCastFinalClass {}';

        $reflector       = new ClassReflector(new StringSourceLocator($php, $this->astLocator));
        $classReflection = $reflector->reflect('StringCastFinalClass');

        self::assertStringStartsWith('Class [ <user> final class StringCastFinalClass ]', (string) $classReflection);
    }

    public function testInternalClassToString() : void
    {
        $reflector = new ClassReflector(new PhpInternalSourceLocator($this->astLocator, $this->sourceStubber));

        // phpcs:disable SlevomatCodingStandard.Exceptions.ReferenceThrowableOnly.ReferencedGeneralException
        $classReflection = $reflector->reflect(Exception::class);
        // phpcs:enable

        self::assertStringStartsWith('Class [ <internal:Core> class Exception implements Throwable ]', (string) $classReflection);
    }

    public function testInterfaceToString() : void
    {
        $php = '<?php interface StringCastInterface {}';

        $reflector       = new ClassReflector(new StringSourceLocator($php, $this->astLocator));
        $classReflection = $reflector->reflect('StringCastInterface');

        self::assertStringStartsWith('Interface [ <user> interface StringCastInterface ]', (string) $classReflection);
    }

    public function testTraitToString() : void
    {
        $php = '<?php trait StringCastTrait {}';

        $reflector       = new ClassReflector(new StringSourceLocator($php, $this->astLocator));
        $classReflection = $reflector->reflect('StringCastTrait');

        self::assertStringStartsWith('Trait [ <user> trait StringCastTrait ]', (string) $classReflection);
    }

    public function testClassObjectToString() : void
    {
        $stringCastClassObjectFilename = __DIR__ . '/../../Fixture/StringCastClassObject.php';
        require_once $stringCastClassObjectFilename;

        $reflector       = new ClassReflector(new SingleFileSourceLocator($stringCastClassObjectFilename, $this->astLocator));
        $classReflection = $reflector->reflect(StringCastClassObject::class);

        $object = new StringCastClassObject();

        $object->dynamicProperty = 'string';

        $objectReflection = ReflectionObject::createFromInstance($object);

        self::assertStringMatchesFormat(
            file_get_contents(__DIR__ . '/../../Fixture/StringCastClassObjectExpected.txt'),
            $objectReflection->__toString()
        );
    }
}
