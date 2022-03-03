<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\StringCast;

use Exception;
use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\Reflection\ReflectionObject;
use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\Fixture\StringCastBackedEnum;
use Roave\BetterReflectionTest\Fixture\StringCastClass;
use Roave\BetterReflectionTest\Fixture\StringCastClassObject;
use Roave\BetterReflectionTest\Fixture\StringCastPureEnum;

use function file_get_contents;

/**
 * @covers \PHPStan\BetterReflection\Reflection\StringCast\ReflectionClassStringCast
 */
class ReflectionClassStringCastTest extends TestCase
{
    /**
     * @var \PHPStan\BetterReflection\SourceLocator\Ast\Locator
     */
    private $astLocator;

    /**
     * @var \PHPStan\BetterReflection\SourceLocator\SourceStubber\SourceStubber
     */
    private $sourceStubber;

    protected function setUp(): void
    {
        parent::setUp();

        $betterReflection = BetterReflectionSingleton::instance();

        $this->astLocator    = $betterReflection->astLocator();
        $this->sourceStubber = $betterReflection->sourceStubber();
    }

    public function testToString(): void
    {
        $reflector       = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../../Fixture/StringCastClass.php', $this->astLocator));
        $classReflection = $reflector->reflectClass(StringCastClass::class);

        self::assertStringMatchesFormat(file_get_contents(__DIR__ . '/../../Fixture/StringCastClassExpected.txt'), $classReflection->__toString());
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testPureEnumToString(): void
    {
        $reflector       = new DefaultReflector(new AggregateSourceLocator([
            new SingleFileSourceLocator(__DIR__ . '/../../Fixture/StringCastPureEnum.php', $this->astLocator),
            new PhpInternalSourceLocator($this->astLocator, $this->sourceStubber),
        ]));
        $classReflection = $reflector->reflectClass(StringCastPureEnum::class);

        self::assertStringMatchesFormat(file_get_contents(__DIR__ . '/../../Fixture/StringCastPureEnumExpected.txt'), $classReflection->__toString());
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testBackedEnumToString(): void
    {
        $reflector       = new DefaultReflector(new AggregateSourceLocator([
            new SingleFileSourceLocator(__DIR__ . '/../../Fixture/StringCastBackedEnum.php', $this->astLocator),
            new PhpInternalSourceLocator($this->astLocator, $this->sourceStubber),
        ]));
        $classReflection = $reflector->reflectClass(StringCastBackedEnum::class);

        self::assertStringMatchesFormat(file_get_contents(__DIR__ . '/../../Fixture/StringCastBackedEnumExpected.txt'), $classReflection->__toString());
    }

    public function testFinalClassToString(): void
    {
        $php = '<?php final class StringCastFinalClass {}';

        $reflector       = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $classReflection = $reflector->reflectClass('StringCastFinalClass');

        self::assertStringStartsWith('Class [ <user> final class StringCastFinalClass ]', (string) $classReflection);
    }

    public function testInternalClassToString(): void
    {
        $reflector = new DefaultReflector(new PhpInternalSourceLocator($this->astLocator, $this->sourceStubber));

        // phpcs:disable SlevomatCodingStandard.Exceptions.ReferenceThrowableOnly.ReferencedGeneralException
        $classReflection = $reflector->reflectClass(Exception::class);
        // phpcs:enable

        self::assertStringStartsWith('Class [ <internal:Core> class Exception implements Throwable, Stringable ]', (string) $classReflection);
    }

    public function testInterfaceToString(): void
    {
        $php = '<?php interface StringCastInterface {}';

        $reflector       = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $classReflection = $reflector->reflectClass('StringCastInterface');

        self::assertStringStartsWith('Interface [ <user> interface StringCastInterface ]', (string) $classReflection);
    }

    public function testTraitToString(): void
    {
        $php = '<?php trait StringCastTrait {}';

        $reflector       = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $classReflection = $reflector->reflectClass('StringCastTrait');

        self::assertStringStartsWith('Trait [ <user> trait StringCastTrait ]', (string) $classReflection);
    }

    public function testClassObjectToString(): void
    {
        $stringCastClassObjectFilename = __DIR__ . '/../../Fixture/StringCastClassObject.php';
        require_once $stringCastClassObjectFilename;

        $reflector       = new DefaultReflector(new SingleFileSourceLocator($stringCastClassObjectFilename, $this->astLocator));
        $classReflection = $reflector->reflectClass(StringCastClassObject::class);

        $object = new StringCastClassObject();

        $object->dynamicProperty = 'string';

        $objectReflection = ReflectionObject::createFromInstance($object);

        self::assertStringMatchesFormat(file_get_contents(__DIR__ . '/../../Fixture/StringCastClassObjectExpected.txt'), $objectReflection->__toString());
    }
}
