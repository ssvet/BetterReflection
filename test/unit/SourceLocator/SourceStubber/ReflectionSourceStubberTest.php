<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\SourceStubber;

use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionException;
use ReflectionFunction as CoreReflectionFunction;
use ReflectionMethod as CoreReflectionMethod;
use ReflectionParameter as CoreReflectionParameter;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionType;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;
use PHPStan\BetterReflection\Reflection\ReflectionParameter;
use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Traversable;

use function array_filter;
use function array_map;
use function array_merge;
use function get_declared_classes;
use function get_declared_interfaces;
use function get_declared_traits;
use function get_defined_constants;
use function get_defined_functions;
use function in_array;
use function method_exists;
use function sort;

use const PHP_VERSION_ID;

/**
 * @covers \PHPStan\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber
 */
class ReflectionSourceStubberTest extends TestCase
{
    private const EXTENSIONS = ['Core', 'standard', 'pcre', 'SPL'];

    /**
     * @var \PHPStan\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber
     */
    private $stubber;

    /**
     * @var \PHPStan\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator
     */
    private $phpInternalSourceLocator;

    /**
     * @var \PHPStan\BetterReflection\Reflector\Reflector
     */
    private $reflector;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        if (PHP_VERSION_ID >= 80000) {
            return;
        }

        self::markTestSkipped('Test requires PHP 8.0');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->stubber                  = new ReflectionSourceStubber();
        $this->phpInternalSourceLocator = new PhpInternalSourceLocator(BetterReflectionSingleton::instance()->astLocator(), $this->stubber);
        $this->reflector                = new DefaultReflector($this->phpInternalSourceLocator);
    }

    public function testCanStubClass(): void
    {
        $stubData = $this->stubber->generateClassStub('stdClass');

        self::assertNotNull($stubData);
        self::assertStringMatchesFormat('%Aclass stdClass%A{%A}%A', $stubData->getStub());
        self::assertSame('Core', $stubData->getExtensionName());
    }

    public function testCanStubInterface(): void
    {
        $stubData = $this->stubber->generateClassStub(Traversable::class);

        self::assertNotNull($stubData);
        self::assertStringMatchesFormat('%Ainterface Traversable%A{%A}%A', $stubData->getStub());
        self::assertSame('Core', $stubData->getExtensionName());
    }

    public function testUnknownClass(): void
    {
        self::assertNull($this->stubber->generateClassStub('SomeClass'));
    }

    /**
     * @return list<list<string>>
     */
    public function internalClassesProvider(): array
    {
        $allSymbols = array_merge(get_declared_classes(), get_declared_interfaces(), get_declared_traits());

        return array_map(static function (string $symbol) : array {
            return [$symbol];
        }, array_filter($allSymbols, static function (string $symbol): bool {
            $reflection = new CoreReflectionClass($symbol);

            if (! $reflection->isInternal()) {
                return false;
            }

            // Check only always enabled extensions
            return in_array($reflection->getExtensionName(), self::EXTENSIONS, true);
        }));
    }

    /**
     * @throws ReflectionException
     *
     * @dataProvider internalClassesProvider
     */
    public function testInternalClasses(string $className): void
    {
        $class = $this->reflector->reflectClass($className);

        self::assertInstanceOf(ReflectionClass::class, $class);
        self::assertSame($className, $class->getName());
        self::assertTrue($class->isInternal());
        self::assertFalse($class->isUserDefined());

        $internalReflection = new CoreReflectionClass($className);

        self::assertSame($internalReflection->isInterface(), $class->isInterface());
        self::assertSame($internalReflection->isTrait(), $class->isTrait());

        self::assertSameClassAttributes($internalReflection, $class);
    }

    private function assertSameParentClass(CoreReflectionClass $original, ReflectionClass $stubbed): void
    {
        $originalParentClass = $original->getParentClass();
        $stubbedParentClass  = $stubbed->getParentClass();

        self::assertSame($originalParentClass ? $originalParentClass->getName() : null, $stubbedParentClass ? $stubbedParentClass->getName() : null);
    }

    private function assertSameInterfaces(CoreReflectionClass $original, ReflectionClass $stubbed): void
    {
        $originalInterfacesNames = $original->getInterfaceNames();
        $stubbedInterfacesNames  = $stubbed->getInterfaceNames();

        sort($originalInterfacesNames);
        sort($stubbedInterfacesNames);

        self::assertSame($originalInterfacesNames, $stubbedInterfacesNames);
    }

    private function assertSameClassAttributes(CoreReflectionClass $original, ReflectionClass $stubbed): void
    {
        self::assertSame($original->getName(), $stubbed->getName());

        $this->assertSameParentClass($original, $stubbed);
        $this->assertSameInterfaces($original, $stubbed);

        foreach ($original->getMethods() as $method) {
            $this->assertSameMethodAttributes($method, $stubbed->getMethod($method->getName()));
        }

        self::assertEquals($original->getConstants(), $stubbed->getConstants());
    }

    private function assertSameMethodAttributes(CoreReflectionMethod $original, ReflectionMethod $stubbed): void
    {
        $originalParameterNames = array_map(static function (CoreReflectionParameter $parameter) : string {
            return $parameter->getDeclaringFunction()->getName() . '.' . $parameter->getName();
        }, $original->getParameters());
        $stubParameterNames     = array_map(static function (ReflectionParameter $parameter) : string {
            return $parameter->getDeclaringFunction()->getName() . '.' . $parameter->getName();
        }, $stubbed->getParameters());

        self::assertSame($originalParameterNames, $stubParameterNames);

        foreach ($original->getParameters() as $parameter) {
            $this->assertSameParameterAttributes($original, $parameter, $stubbed->getParameter($parameter->getName()));
        }

        self::assertSame($original->isPublic(), $stubbed->isPublic());
        self::assertSame($original->isPrivate(), $stubbed->isPrivate());
        self::assertSame($original->isProtected(), $stubbed->isProtected());
        self::assertSame($original->returnsReference(), $stubbed->returnsReference());
        self::assertSame($original->isStatic(), $stubbed->isStatic());
        self::assertSame($original->isFinal(), $stubbed->isFinal());
        self::assertSame($original->isAbstract(), $stubbed->isAbstract());
        self::assertSame($original->isDeprecated(), $stubbed->isDeprecated());

        if (method_exists($original, 'hasTentativeReturnType')) {
            self::assertSame($original->hasTentativeReturnType(), $stubbed->hasTentativeReturnType(), $original->getName());
            self::assertSame((string) $original->getTentativeReturnType(), (string) ReflectionType::fromTypeOrNull($stubbed->getTentativeReturnType()), $original->getName());
        }

        self::assertSame($original->hasReturnType(), $stubbed->hasReturnType(), $original->getName());
        self::assertSame((string) $original->getReturnType(), (string) ReflectionType::fromTypeOrNull($stubbed->getReturnType()), $original->getName());
    }

    private function assertSameParameterAttributes(CoreReflectionMethod $originalMethod, CoreReflectionParameter $original, ReflectionParameter $stubbed): void
    {
        $methodName    = $original->getDeclaringClass()->getName() . '#' . $originalMethod->getName();
        $parameterName = $methodName . '.' . $original->getName();
        self::assertSame($original->getName(), $stubbed->getName(), $parameterName);
        if ($original->isDefaultValueAvailable()) {
            self::assertSame($original->getDefaultValue(), $stubbed->getDefaultValue(), $parameterName);
        } else {
            self::assertSame($original->isDefaultValueAvailable(), $stubbed->isDefaultValueAvailable(), $parameterName);
        }
        // @ because isArray() and isCallable() are deprecated
        self::assertSame(@$original->isArray(), $stubbed->isArray(), $parameterName);
        self::assertSame(@$original->isCallable(), $stubbed->isCallable(), $parameterName);
        //self::assertSame($original->allowsNull(), $stubbed->allowsNull()); @TODO WTF?
        self::assertSame($original->canBePassedByValue(), $stubbed->canBePassedByValue(), $parameterName);
        self::assertSame($original->isOptional(), $stubbed->isOptional(), $parameterName);
        self::assertSame($original->isPassedByReference(), $stubbed->isPassedByReference(), $parameterName);
        self::assertSame($original->isVariadic(), $stubbed->isVariadic(), $parameterName);
        // @ because getClass() is deprecated
        $class = @$original->getClass();
        if ($class) {
            $stubbedClass = $stubbed->getClass();

            self::assertInstanceOf(ReflectionClass::class, $stubbedClass, $parameterName);
            self::assertSame($class->getName(), $stubbedClass->getName(), $parameterName);
        } else {
            self::assertNull($stubbed->getClass(), $parameterName);
        }
    }

    /**
     * @return list<list<string>>
     */
    public function internalFunctionsProvider(): array
    {
        $functionNames = get_defined_functions()['internal'];

        return array_map(static function (string $functionName) : array {
            return [$functionName];
        }, array_filter($functionNames, static function (string $functionName): bool {
            $reflection = new CoreReflectionFunction($functionName);

            return $reflection->isInternal();
        }));
    }

    /**
     * @dataProvider internalFunctionsProvider
     */
    public function testInternalFunctionsReturnType(string $functionName): void
    {
        $stubbedReflection  = $this->reflector->reflectFunction($functionName);
        $originalReflection = new CoreReflectionFunction($functionName);

        if (method_exists($originalReflection, 'hasTentativeReturnType') && $originalReflection->hasTentativeReturnType()) {
            self::assertSame($originalReflection->hasTentativeReturnType(), $stubbedReflection->hasTentativeReturnType());
            self::assertSame((string) $originalReflection->getTentativeReturnType(), (string) ReflectionType::fromTypeOrNull($stubbedReflection->getTentativeReturnType()));
        } else {
            self::assertSame($originalReflection->hasReturnType(), $stubbedReflection->hasReturnType());
            self::assertSame((string) $originalReflection->getReturnType(), (string) ReflectionType::fromTypeOrNull($stubbedReflection->getReturnType()));
        }
    }

    public function testFunctionWithParameterPassedByReference(): void
    {
        $functionReflection = $this->reflector->reflectFunction('sort');

        self::assertSame('sort', $functionReflection->getName());
        self::assertSame(2, $functionReflection->getNumberOfParameters());

        $parameterReflection = $functionReflection->getParameters()[0];
        self::assertSame('array', $parameterReflection->getName());
        self::assertFalse($parameterReflection->isOptional());
        self::assertTrue($parameterReflection->isPassedByReference());
        self::assertFalse($parameterReflection->canBePassedByValue());
    }

    public function testFunctionWithOptionalParameter(): void
    {
        $functionReflection = $this->reflector->reflectFunction('preg_match');

        self::assertSame('preg_match', $functionReflection->getName());
        self::assertSame(5, $functionReflection->getNumberOfParameters());
        self::assertSame(2, $functionReflection->getNumberOfRequiredParameters());

        $parameterReflection = $functionReflection->getParameters()[2];
        self::assertSame('matches', $parameterReflection->getName());
        self::assertTrue($parameterReflection->isOptional());
    }

    public function variadicParametersProvider(): array
    {
        return [
            ['sprintf', 1, true, true],
            ['printf', 1, true, true],
        ];
    }

    /**
     * @dataProvider variadicParametersProvider
     */
    public function testFunctionWithVariadicParameter(string $functionName, int $parameterPosition, bool $parameterIsVariadic, bool $parameterIsOptional): void
    {
        $functionReflection = $this->reflector->reflectFunction($functionName);

        self::assertSame($functionName, $functionReflection->getName());

        $parametersReflections = $functionReflection->getParameters();
        self::assertArrayHasKey($parameterPosition, $parametersReflections);
        self::assertSame($parameterIsVariadic, $parametersReflections[$parameterPosition]->isVariadic());
        self::assertSame($parameterIsOptional, $parametersReflections[$parameterPosition]->isOptional());
    }

    public function testUnknownFunction(): void
    {
        self::assertNull($this->stubber->generateFunctionStub('someFunction'));
    }

    public function testCanStubConstant(): void
    {
        $stubData = $this->stubber->generateConstantStub('E_ALL');

        self::assertNotNull($stubData);
        self::assertStringMatchesFormat("%Adefine('E_ALL',%w%d);", $stubData->getStub());
        self::assertSame('Core', $stubData->getExtensionName());
    }

    /**
     * @return list<list<mixed>>
     */
    public function internalConstantsProvider(): array
    {
        $provider = [];

        /** @var array<string, array<string, int|string|float|bool|array|resource|null>> $constants */
        $constants = get_defined_constants(true);

        foreach ($constants as $extensionName => $extensionConstants) {
            // Check only always enabled extensions
            if (! in_array($extensionName, self::EXTENSIONS, true)) {
                continue;
            }

            foreach ($extensionConstants as $constantName => $constantValue) {
                $provider[] = [$constantName, $constantValue, $extensionName];
            }
        }

        return $provider;
    }

    /**
     * @dataProvider internalConstantsProvider
     * @param mixed $constantValue
     */
    public function testInternalConstants(string $constantName, $constantValue, string $extensionName): void
    {
        $constantReflection = $this->reflector->reflectConstant($constantName);

        self::assertInstanceOf(ReflectionConstant::class, $constantReflection);
        self::assertSame($constantName, $constantReflection->getName());
        self::assertSame($constantName, $constantReflection->getShortName());

        self::assertNotNull($constantReflection->getNamespaceName());
        self::assertFalse($constantReflection->inNamespace());
        self::assertTrue($constantReflection->isInternal());
        self::assertFalse($constantReflection->isUserDefined());
        self::assertSame($extensionName, $constantReflection->getExtensionName());

        // NAN cannot be compared
        if ($constantName === 'NAN') {
            return;
        }

        self::assertSame($constantValue, $constantReflection->getValue());
    }

    public function testUnknownConstant(): void
    {
        self::assertNull($this->stubber->generateConstantStub('SOME_CONSTANT'));
    }
}
