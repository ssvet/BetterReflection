<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use PhpParser\Node;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionIntersectionType;
use Roave\BetterReflection\Reflection\ReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionType;
use Roave\BetterReflection\Reflection\ReflectionUnionType;
use Roave\BetterReflection\Reflector\Reflector;

/**
 * @covers \Roave\BetterReflection\Reflection\ReflectionType
 * @covers \Roave\BetterReflection\Reflection\ReflectionNamedType
 * @covers \Roave\BetterReflection\Reflection\ReflectionIntersectionType
 * @covers \Roave\BetterReflection\Reflection\ReflectionUnionType
 */
class ReflectionTypeTest extends TestCase
{
    /**
     * @var \Roave\BetterReflection\Reflector\Reflector
     */
    private $reflector;
    /**
     * @var \Roave\BetterReflection\Reflection\ReflectionParameter
     */
    private $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reflector = $this->createMock(Reflector::class);
        $this->owner     = $this->createMock(ReflectionParameter::class);
    }

    public function dataProvider(): array
    {
        return [
            [new Node\Name('A'), false, ReflectionNamedType::class, false],
            [new Node\Identifier('string'), false, ReflectionNamedType::class, false],
            'Forcing a type to be nullable turns it into a `T|null` ReflectionUnionType' => [
                new Node\Identifier('string'),
                true,
                ReflectionUnionType::class,
                true,
            ],
            'Nullable types are converted into `T|null` ReflectionUnionType instances' => [
                new Node\NullableType(new Node\Identifier('string')),
                false,
                ReflectionUnionType::class,
                true,
            ],
            [new Node\IntersectionType([new Node\Name('A'), new Node\Name('B')]), false, ReflectionIntersectionType::class, false],
            [new Node\UnionType([new Node\Name('A'), new Node\Name('B')]), false, ReflectionUnionType::class, false],
            'Union types composed of just `null` and a type are kept as `T|null` ReflectionUnionType' => [
                new Node\UnionType([new Node\Name('A'), new Node\Name('null')]),
                false,
                ReflectionUnionType::class,
                true,
            ],
            'Union types composed of `null` and more than one type are kept as `T|U|null` ReflectionUnionType' => [
                new Node\UnionType([new Node\Name('A'), new Node\Name('B'), new Node\Name('null')]),
                false,
                ReflectionUnionType::class,
                true,
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     * @param \PhpParser\Node\Identifier|\PhpParser\Node\IntersectionType|\PhpParser\Node\Name|\PhpParser\Node\NullableType|\PhpParser\Node\UnionType $node
     */
    public function test($node, bool $forceAllowsNull, string $expectedReflectionClass, bool $expectedAllowsNull): void
    {
        $reflectionType = ReflectionType::createFromNode($this->reflector, $this->owner, $node, $forceAllowsNull);
        self::assertInstanceOf($expectedReflectionClass, $reflectionType);
        self::assertSame($expectedAllowsNull, $reflectionType->allowsNull());
    }
}
