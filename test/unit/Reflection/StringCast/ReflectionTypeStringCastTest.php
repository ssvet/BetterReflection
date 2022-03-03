<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\StringCast;

use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\Reflection\ReflectionIntersectionType;
use PHPStan\BetterReflection\Reflection\ReflectionNamedType;
use PHPStan\BetterReflection\Reflection\ReflectionUnionType;
use PHPStan\BetterReflection\Reflection\StringCast\ReflectionTypeStringCast;
use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

use function assert;

/**
 * @covers \PHPStan\BetterReflection\Reflection\StringCast\ReflectionTypeStringCast
 */
final class ReflectionTypeStringCastTest extends TestCase
{
    public function toStringProvider(): array
    {
        $reflector = new DefaultReflector(new StringSourceLocator(<<<'PHP'
<?php

interface A {}
interface B {}
function a(): int|string|null {}
function b(): int|null {}
function c(): ?int {}
function d(): A&B {}
function e(): int {}
PHP, BetterReflectionSingleton::instance()
            ->astLocator()));

        $returnTypeForFunction = static function (string $function) use ($reflector) {
            $type = $reflector->reflectFunction($function)
                ->getReturnType();

            assert($type !== null);

            return $type;
        };

        return [
            [$returnTypeForFunction('a'), 'int|string|null'],
            [$returnTypeForFunction('b'), '?int'],
            [$returnTypeForFunction('c'), '?int'],
            [$returnTypeForFunction('d'), 'A&B'],
            [$returnTypeForFunction('e'), 'int'],
        ];
    }

    /**
     * @dataProvider toStringProvider
     * @param \PHPStan\BetterReflection\Reflection\ReflectionIntersectionType|\PHPStan\BetterReflection\Reflection\ReflectionNamedType|\PHPStan\BetterReflection\Reflection\ReflectionUnionType $type
     */
    public function testToString($type, string $expectedString): void
    {
        self::assertSame($expectedString, ReflectionTypeStringCast::toString($type));
    }
}
