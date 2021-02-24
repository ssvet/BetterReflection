<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Util\Autoload\ClassLoaderMethod;

use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Util\Autoload\ClassPrinter\ClassPrinterInterface;

final class EvalLoader implements LoaderMethodInterface
{
    /** @var ClassPrinterInterface */
    private $classPrinter;

    public function __construct(ClassPrinterInterface $classPrinter)
    {
        $this->classPrinter = $classPrinter;
    }

    public function __invoke(ReflectionClass $classInfo) : void
    {
        eval($this->classPrinter->__invoke($classInfo));
    }
}
