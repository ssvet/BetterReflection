<?php

// Loading a specific file (not from autoloader)

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPStan\BetterReflection\BetterReflection;
use PHPStan\BetterReflection\Reflector\ClassReflector;
use PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;

$reflector = new ClassReflector(new AggregateSourceLocator([
    new SingleFileSourceLocator(__DIR__ . '/assets/MyClass.php', (new BetterReflection())->astLocator()),
]));

$reflection = $reflector->reflect('MyClass');

echo $reflection->getName() . "\n"; // MyClass
echo ($reflection->getProperty('foo')->isPrivate() === true ? 'private' : 'not private'); // private
