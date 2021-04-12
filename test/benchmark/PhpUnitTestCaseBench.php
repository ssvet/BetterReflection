<?php

declare(strict_types=1);

namespace Roave\BetterReflectionBenchmark;

use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;
use PHPStan\BetterReflection\Reflection\ReflectionParameter;
use PHPStan\BetterReflection\Reflection\ReflectionProperty;
use PHPStan\BetterReflection\Reflector\ClassReflector;
use function array_map;
use function array_merge;

/**
 * @Iterations(5)
 */
class PhpUnitTestCaseBench
{
    /** @var ClassReflector */
    private $reflector;

    /** @var ReflectionProperty[] */
    private $properties;

    /** @var ReflectionMethod[] */
    private $methods;

    /** @var ReflectionParameter[] */
    private $parameters = [];

    public function __construct()
    {
        $reflection       = new BetterReflection();
        $this->reflector  = $reflection->classReflector();
        $reflectionClass  = $this->reflector->reflect(TestCase::class);
        $this->methods    = $reflectionClass->getMethods();
        $this->properties = $reflectionClass->getProperties();
        $this->parameters = array_merge([], ...array_map(static function (ReflectionMethod $method) : array {
            return $method->getParameters();
        }, $this->methods));
    }

    public function benchReflectClass() : void
    {
        $this->reflector->reflect(TestCase::class);
    }

    public function benchReflectMethodParameters() : void
    {
        foreach ($this->parameters as $parameter) {
            $parameter->getType();
        }
    }

}
