<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util;

use Closure;
use IteratorAggregate;
use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\Util\ClassExistenceChecker;
use Roave\BetterReflectionTest\Fixture\ExampleClass;
use Roave\BetterReflectionTest\Fixture\ExampleInterface;
use Roave\BetterReflectionTest\Fixture\ExampleTrait;
use stdClass;
use TraitFixtureTraitA;

use function spl_autoload_register;
use function spl_autoload_unregister;

/**
 * @covers \PHPStan\BetterReflection\Util\ClassExistenceChecker
 */
class ClassExistenceCheckerTest extends TestCase
{
    /**
     * @var \Closure|null
     */
    private $loader;

    protected function setUp(): void
    {
        parent::setUp();

        require_once __DIR__ . '/../Fixture/TraitFixture.php';

        $this->loader = static function (): void {
            // Should not be called
            self::fail();
        };
        spl_autoload_register($this->loader);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        spl_autoload_unregister($this->loader);
    }

    public function dataClassExists(): array
    {
        return [
            [stdClass::class, true],
            [IteratorAggregate::class, false],
            [ExampleClass::class, false],
            ['NotExistingClass', false],
        ];
    }

    /**
     * @dataProvider dataClassExists
     */
    public function testClassExists(string $name, bool $exists): void
    {
        self::assertSame($exists, ClassExistenceChecker::classExists($name));
    }

    public function dataExists(): array
    {
        return [
            [stdClass::class, true],
            [IteratorAggregate::class, true],
            [TraitFixtureTraitA::class, true],
            [ExampleClass::class, false],
            ['NotExistingClass', false],
            [ExampleInterface::class, false],
            ['NotExistInterface', false],
            [ExampleTrait::class, false],
            ['NotExistTrait', false],
        ];
    }

    /**
     * @dataProvider dataExists
     */
    public function testExists(string $name, bool $exists): void
    {
        self::assertSame($exists, ClassExistenceChecker::exists($name));
    }

    public function dataInterfaceExists(): array
    {
        return [
            [IteratorAggregate::class, true],
            [stdClass::class, false],
            [ExampleInterface::class, false],
            ['NotExistInterface', false],
        ];
    }

    /**
     * @dataProvider dataInterfaceExists
     */
    public function testInterfaceExists(string $name, bool $exists): void
    {
        self::assertSame($exists, ClassExistenceChecker::interfaceExists($name));
    }

    public function dataTraitExists(): array
    {
        return [
            [stdClass::class, false],
            [TraitFixtureTraitA::class, true],
            [ExampleTrait::class, false],
            ['NotExistTrait', false],
        ];
    }

    /**
     * @dataProvider dataTraitExists
     */
    public function testTraitExists(string $name, bool $exists): void
    {
        self::assertSame($exists, ClassExistenceChecker::traitExists($name));
    }
}
