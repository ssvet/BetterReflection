--TEST--
Retrieve the AST or code directly from a function body
--FILE--
<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$source = <<<'EOF'
<?php

function adder(int $a, int $b)
{
    return $a + $b;
}
EOF;

$sourceLocator = new PHPStan\BetterReflection\SourceLocator\Type\StringSourceLocator(
    $source,
    (new PHPStan\BetterReflection\BetterReflection())->astLocator()
);

$reflector = new \PHPStan\BetterReflection\Reflector\FunctionReflector(
    $sourceLocator,
    new \PHPStan\BetterReflection\Reflector\ClassReflector($sourceLocator)
);

$functionInfo = $reflector->reflect('adder');
var_dump($functionInfo->getName());

var_dump($functionInfo->getBodyCode());

?>
--EXPECTF--
string(5) "adder"
string(15) "return $a + $b;"
