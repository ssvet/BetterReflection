<?php

namespace Roave\BetterReflectionTest\Fixture;

class StaticPropertyGetSet
{
    public static $baz;
    protected static $bat;
    private static $qux;
}

trait StaticPropertyTraitGetSet
{
    public static $baz;
    protected static $bat;
    private static $qux;
}
