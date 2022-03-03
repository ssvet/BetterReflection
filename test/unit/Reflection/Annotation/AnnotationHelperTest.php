<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Annotation;

use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\Reflection\Annotation\AnnotationHelper;

/**
 * @covers \PHPStan\BetterReflection\Reflection\Annotation\AnnotationHelper
 */
class AnnotationHelperTest extends TestCase
{
    public function deprecatedDocCommentProvider(): array
    {
        return [
            ['', false],
            [
                '/**
                 * @return string
                 */',
                false,
            ],
            [
                '/**
                 * @deprecatedPolicy
                 */',
                false,
            ],
            ['/** @deprecated */', true],
            ['/**@deprecated*/', true],
            [
                '/**
                 * @deprecated since 8.0.0
                 */',
                true,
            ],
        ];
    }

    /**
     * @dataProvider deprecatedDocCommentProvider
     */
    public function testIsDeprecated(string $docComment, bool $isDeprecated): void
    {
        self::assertSame($isDeprecated, AnnotationHelper::isDeprecated($docComment));
    }

    public function tentativeReturnTypeDocCommentProvider(): array
    {
        return [
            ['', false],
            [
                '/**
                 * @return string
                 */',
                false,
            ],
            ['/** @betterReflectionTentativeReturnType */', true],
            ['/**@betterReflectionTentativeReturnType*/', true],
            [
                '/**
                 * @betterReflectionTentativeReturnType
                 */',
                true,
            ],
        ];
    }

    /**
     * @dataProvider tentativeReturnTypeDocCommentProvider
     */
    public function testhasTentativeReturnType(string $docComment, bool $isDeprecated): void
    {
        self::assertSame($isDeprecated, AnnotationHelper::hasTentativeReturnType($docComment));
    }
}
