<?php

namespace Roave\BetterReflectionTest\Fixture;

class ClassWithStaticMethod
{
    public static function sum(int $a, int $b) : int
    {
        return $a + $b;
    }
}

trait TraitWithStaticMethod
{
    public static function sum(int $a, int $b) : int
    {
        return $a + $b;
    }
}
