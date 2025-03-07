<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\StringCast;

use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

/**
 * @covers \PHPStan\BetterReflection\Reflection\StringCast\ReflectionConstantStringCast
 */
class ReflectionConstantStringCastTest extends TestCase
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

    public function toStringProvider(): array
    {
        return [
            ['Roave\BetterReflectionTest\Fixture\BY_CONST', "Constant [ <user> boolean Roave\BetterReflectionTest\Fixture\BY_CONST ] {\n  @@ %s/Fixture/StringCastConstants.php 5 - 5\n 1 }"],
            ['Roave\BetterReflectionTest\Fixture\BY_CONST_1', "Constant [ <user> integer Roave\BetterReflectionTest\Fixture\BY_CONST_1 ] {\n  @@ %s/Fixture/StringCastConstants.php 6 - 7\n 1 }"],
            ['Roave\BetterReflectionTest\Fixture\BY_CONST_2', "Constant [ <user> integer Roave\BetterReflectionTest\Fixture\BY_CONST_2 ] {\n  @@ %s/Fixture/StringCastConstants.php 6 - 7\n 2 }"],
            ['BY_DEFINE', "Constant [ <user> string BY_DEFINE ] {\n  @@ %s/Fixture/StringCastConstants.php 11 - 11\n define }"],
            ['E_ALL', 'Constant [ <internal:Core> integer E_ALL ] { %d }'],
        ];
    }

    /**
     * @dataProvider toStringProvider
     */
    public function testToString(string $constantName, string $expectedString): void
    {
        $sourceLocator = new AggregateSourceLocator([
            new SingleFileSourceLocator(__DIR__ . '/../../Fixture/StringCastConstants.php', $this->astLocator),
            new PhpInternalSourceLocator($this->astLocator, $this->sourceStubber),
        ]);

        $reflector          = new DefaultReflector($sourceLocator);
        $constantReflection = $reflector->reflectConstant($constantName);

        self::assertStringMatchesFormat($expectedString, (string) $constantReflection);
    }

    public function testToStringWithNoFileName(): void
    {
        $php = '<?php const CONSTANT_TO_STRING_CAST = "string";';

        $reflector          = new DefaultReflector(new StringSourceLocator($php, $this->astLocator));
        $constantReflection = $reflector->reflectConstant('CONSTANT_TO_STRING_CAST');

        self::assertSame('Constant [ <user> string CONSTANT_TO_STRING_CAST ] { string }', (string) $constantReflection);
    }
}
