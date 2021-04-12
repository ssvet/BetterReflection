<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util\Autoload;

use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Util\Autoload\ClassLoader;
use PHPStan\BetterReflection\Util\Autoload\ClassLoaderMethod\LoaderMethodInterface;
use PHPStan\BetterReflection\Util\Autoload\ClassPrinter\PhpParserPrinter;
use PHPStan\BetterReflection\Util\Autoload\Exception\ClassAlreadyLoaded;
use PHPStan\BetterReflection\Util\Autoload\Exception\ClassAlreadyRegistered;
use PHPStan\BetterReflection\Util\Autoload\Exception\FailedToLoadClass;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflectionTest\Fixture\AnotherTestClassForAutoloader;
use Roave\BetterReflectionTest\Fixture\TestClassForAutoloader;
use stdClass;
use function class_exists;
use function count;
use function spl_autoload_functions;
use function spl_autoload_unregister;

/**
 * @covers \PHPStan\BetterReflection\Util\Autoload\ClassLoader
 */
final class ClassLoaderTest extends TestCase
{
    public function testAutoloadSelfRegisters() : void
    {
        $initialAutoloaderCount = count(spl_autoload_functions());

        $loaderMethod = $this->createMock(LoaderMethodInterface::class);
        $loader       = new ClassLoader($loaderMethod);

        self::assertCount($initialAutoloaderCount + 1, spl_autoload_functions());

        spl_autoload_unregister($loader);

        self::assertCount($initialAutoloaderCount, spl_autoload_functions());
    }

    public function testAutoloadTriggersLoaderMethod() : void
    {
        $reflection = ReflectionClass::createFromName(TestClassForAutoloader::class);
        self::assertFalse(class_exists(TestClassForAutoloader::class, false));

        $loaderMethod = $this->createMock(LoaderMethodInterface::class);
        $loaderMethod->expects(self::once())
            ->method('__invoke')
            ->with($reflection)
            ->willReturnCallback(static function () use ($reflection) : void {
                eval((new PhpParserPrinter())->__invoke($reflection));
            });

        $loader = new ClassLoader($loaderMethod);
        $loader->addClass($reflection);

        new TestClassForAutoloader();

        spl_autoload_unregister($loader);
    }

    public function testAddClassThrowsExceptionWhenClassAlreadyRegisteredInAutoload() : void
    {
        $reflection = ReflectionClass::createFromName(AnotherTestClassForAutoloader::class);

        $loaderMethod = $this->createMock(LoaderMethodInterface::class);
        $loader       = new ClassLoader($loaderMethod);

        $loader->addClass($reflection);

        $this->expectException(ClassAlreadyRegistered::class);
        $loader->addClass($reflection);

        spl_autoload_unregister($loader);
    }

    public function testAddClassThrowsExceptionWhenClassAlreadyLoaded() : void
    {
        $loaderMethod = $this->createMock(LoaderMethodInterface::class);
        $loader       = new ClassLoader($loaderMethod);

        $this->expectException(ClassAlreadyLoaded::class);
        $loader->addClass(ReflectionClass::createFromName(stdClass::class));

        spl_autoload_unregister($loader);
    }

    /**
     * @todo I'd like to figure out a better way of doing this; weird interactions with other tests here, but it works
     * @runInSeparateProcess
     */
    public function testAutoloadThrowsExceptionWhenClassIsNotLoadedCorrectlyAfterAttemptingToLoad() : void
    {
        $reflection = ReflectionClass::createFromName(AnotherTestClassForAutoloader::class);
        self::assertFalse(class_exists(AnotherTestClassForAutoloader::class, false));

        $loaderMethod = $this->createMock(LoaderMethodInterface::class);
        $loaderMethod->expects(self::once())
            ->method('__invoke')
            ->with($reflection);

        $loader = new ClassLoader($loaderMethod);
        $loader->addClass($reflection);

        $this->expectException(FailedToLoadClass::class);
        new AnotherTestClassForAutoloader();

        spl_autoload_unregister($loader);
    }
}
