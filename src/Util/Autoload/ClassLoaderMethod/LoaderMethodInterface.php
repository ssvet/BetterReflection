<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Util\Autoload\ClassLoaderMethod;

use PHPStan\BetterReflection\Reflection\ReflectionClass;

interface LoaderMethodInterface
{
    public function __invoke(ReflectionClass $classInfo) : void;
}
