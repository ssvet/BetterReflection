<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Identifier;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\Identifier\IdentifierType;

/**
 * @covers \PHPStan\BetterReflection\Identifier\IdentifierType
 */
class IdentifierTypeTest extends TestCase
{
    /**
     * @return list<list<string>>
     */
    public function possibleIdentifierTypesProvider(): array
    {
        return [
            [IdentifierType::IDENTIFIER_CLASS],
            [IdentifierType::IDENTIFIER_FUNCTION],
            [IdentifierType::IDENTIFIER_CONSTANT],
        ];
    }

    /**
     * @dataProvider possibleIdentifierTypesProvider
     */
    public function testPossibleIdentifierTypes(string $full): void
    {
        $type = new IdentifierType($full);
        self::assertSame($full, $type->getName());
    }

    public function testThrowsAnExceptionWhenInvalidTypeGiven(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('foo is not a valid identifier type');
        new IdentifierType('foo');
    }

    public function testIsTypesForClass(): void
    {
        $classType = new IdentifierType(IdentifierType::IDENTIFIER_CLASS);

        self::assertTrue($classType->isClass());
        self::assertFalse($classType->isFunction());
        self::assertFalse($classType->isConstant());
    }

    public function testIsTypesForFunction(): void
    {
        $functionType = new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION);

        self::assertFalse($functionType->isClass());
        self::assertTrue($functionType->isFunction());
        self::assertFalse($functionType->isConstant());
    }

    public function testIsTypesForConstant(): void
    {
        $constantType = new IdentifierType(IdentifierType::IDENTIFIER_CONSTANT);

        self::assertFalse($constantType->isClass());
        self::assertFalse($constantType->isFunction());
        self::assertTrue($constantType->isConstant());
    }
}
