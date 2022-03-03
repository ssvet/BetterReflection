--TEST--
Reflection class can not be cloned
--SKIPIF--
<?php
if (!extension_loaded('reflection')) print 'skip';
?>
--FILE--
<?php
require __DIR__ . '/../../vendor/autoload.php';

$classInfo = \PHPStan\BetterReflection\Reflection\ReflectionClass::createFromName('stdClass');
$clone = clone($classInfo);
?>
--EXPECTF--
Fatal error: Uncaught %SPHPStan\BetterReflection\Reflection\Exception\Uncloneable%s
Stack trace:
%a
