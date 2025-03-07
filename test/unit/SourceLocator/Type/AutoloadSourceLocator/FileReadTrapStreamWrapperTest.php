<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type\AutoloadSourceLocator;

use Exception;
use LogicException;
use PHPUnit\Framework\Error\Warning;
use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\SourceLocator\Type\AutoloadSourceLocator\FileReadTrapStreamWrapper;
use Throwable;
use UnexpectedValueException;

use function class_exists;
use function file_get_contents;
use function is_file;
use function sprintf;
use function uniqid;

/**
 * @covers \PHPStan\BetterReflection\SourceLocator\Type\AutoloadSourceLocator\FileReadTrapStreamWrapper
 *
 * Note: stream wrappers interfere **HEAVILY** with autoloaders, therefore we need to operate with raw
 *       assertions rather than with PHPUnit's assertion utilities, which lead to autoloading in case
 *       of lazy assertions or assertion failures.
 */
class FileReadTrapStreamWrapperTest extends TestCase
{
    public function testWillFindFileLocationOfExistingFileWhenFileIsRead(): void
    {
        self::assertNull(FileReadTrapStreamWrapper::$autoloadLocatedFile);

        self::assertSame('value produced by the function', FileReadTrapStreamWrapper::withStreamWrapperOverride(static function (): string {
            if (FileReadTrapStreamWrapper::$autoloadLocatedFile !== null) {
                throw new UnexpectedValueException('FileReadTrapStreamWrapper::$autoloadLocatedFile should be null when being first used');
            }

            if (! is_file(__FILE__)) {
                throw new UnexpectedValueException('is_file() should operate as usual');
            }

            if (FileReadTrapStreamWrapper::$autoloadLocatedFile !== null) {
                throw new UnexpectedValueException('FileReadTrapStreamWrapper::$autoloadLocatedFile should not be populated when file existence is checked');
            }

            if (@file_get_contents(__FILE__)) {
                throw new UnexpectedValueException('file_get_contents() should fail: file should not be readable when this stream wrapper is active');
            }

            if (__FILE__ !== FileReadTrapStreamWrapper::$autoloadLocatedFile) {
                throw new UnexpectedValueException('FileReadTrapStreamWrapper::$autoloadLocatedFile should have been populated by the failed file access');
            }

            return 'value produced by the function';
        }, ['file']));

        self::assertNull(FileReadTrapStreamWrapper::$autoloadLocatedFile);
        self::assertNotEmpty(file_get_contents(__FILE__), 'Stream wrapper was removed, file reads work again');
    }

    public function testWillReportAttemptedAccessesToNonExistingFiles(): void
    {
        self::assertNull(FileReadTrapStreamWrapper::$autoloadLocatedFile);

        $nonExistingFilePath = __DIR__ . '/' . uniqid('non-existing-file', true);

        self::assertSame('the value produced by the function', FileReadTrapStreamWrapper::withStreamWrapperOverride(static function () use ($nonExistingFilePath): string {
            if (FileReadTrapStreamWrapper::$autoloadLocatedFile !== null) {
                throw new UnexpectedValueException('FileReadTrapStreamWrapper::$autoloadLocatedFile should be null when being first used');
            }

            if (is_file($nonExistingFilePath)) {
                throw new UnexpectedValueException('is_file() should operate as usual - file does indeed not exist');
            }

            if (FileReadTrapStreamWrapper::$autoloadLocatedFile !== null) {
                throw new UnexpectedValueException('FileReadTrapStreamWrapper::$autoloadLocatedFile should not be populated when file existence is checked');
            }

            if (@file_get_contents($nonExistingFilePath)) {
                throw new UnexpectedValueException('file_get_contents() should fail: file should not be readable when this stream wrapper is active');
            }

            if ($nonExistingFilePath !== FileReadTrapStreamWrapper::$autoloadLocatedFile) {
                throw new UnexpectedValueException('FileReadTrapStreamWrapper::$autoloadLocatedFile should have been populated by the failed file access');
            }

            return 'the value produced by the function';
        }, ['file']));

        self::assertNull(FileReadTrapStreamWrapper::$autoloadLocatedFile);
        self::assertNotEmpty(file_get_contents(__FILE__), 'Stream wrapper was removed, file reads work again');
    }

    public function testWillOnlyOverrideProvidedProtocols(): void
    {
        self::assertNull(FileReadTrapStreamWrapper::$autoloadLocatedFile);

        self::assertSame('another value produced by the function', FileReadTrapStreamWrapper::withStreamWrapperOverride(static function (): string {
            if (FileReadTrapStreamWrapper::$autoloadLocatedFile !== null) {
                throw new UnexpectedValueException('FileReadTrapStreamWrapper::$autoloadLocatedFile should be null when being first used');
            }

            if (! is_file(__FILE__)) {
                throw new UnexpectedValueException('is_file() should operate as usual - stream wrapper not active');
            }

            if (FileReadTrapStreamWrapper::$autoloadLocatedFile !== null) {
                throw new UnexpectedValueException('FileReadTrapStreamWrapper::$autoloadLocatedFile should not be populated when file existence is checked');
            }

            if (! @file_get_contents(__FILE__)) {
                throw new UnexpectedValueException('file_get_contents() should work: file access not on this protocol');
            }

            if (FileReadTrapStreamWrapper::$autoloadLocatedFile !== null) {
                throw new UnexpectedValueException('FileReadTrapStreamWrapper::$autoloadLocatedFile should not have been populated: unrelated protocol');
            }

            return 'another value produced by the function';
        }, ['http']));

        self::assertNull(FileReadTrapStreamWrapper::$autoloadLocatedFile);
        self::assertNotEmpty(file_get_contents(__FILE__), 'Stream wrapper was removed, file reads work again');
    }

    public function testStreamWrapperIsRestoredWhenAnExceptionIsThrown(): void
    {
        $thrown = new Exception();

        try {
            self::assertSame('another value produced by the function', FileReadTrapStreamWrapper::withStreamWrapperOverride(static function () use ($thrown): string {
                if (! is_file(__FILE__)) {
                    throw new UnexpectedValueException('is_file() should operate as usual');
                }

                throw $thrown;
            }, ['http']));

            self::fail('No exception was raised');
        } catch (Throwable $caught) {
            self::assertSame($thrown, $caught);
        }

        self::assertNull(FileReadTrapStreamWrapper::$autoloadLocatedFile);
        self::assertNotEmpty(file_get_contents(__FILE__), 'Stream wrapper was removed, file reads work again');
    }

    public function testWillRaiseWarningWhenTryingToCheckFileExistenceForNonExistingFileWithoutSilencingModifier(): void
    {
        self::assertTrue(class_exists(Warning::class), 'The warning class should not be autoloaded lazily for this specific test');

        $nonExistingFile = __DIR__ . uniqid('non-existing-file', true);

        $this->expectWarning();

        self::assertSame('another value produced by the function', FileReadTrapStreamWrapper::withStreamWrapperOverride(static function () use ($nonExistingFile): string {
            if (is_file($nonExistingFile)) {
                throw new UnexpectedValueException('is_file() should report `false` for a non-existing file');
            }

            if (file_get_contents($nonExistingFile) !== false) {
                throw new UnexpectedValueException('file_get_contents() should report `false` for a non-existing file');
            }

            return 'another value produced by the function';
        }, ['file']));

        self::assertNull(FileReadTrapStreamWrapper::$autoloadLocatedFile);
        self::assertNotEmpty(file_get_contents(__FILE__), 'Stream wrapper was removed, file reads work again');
    }

    public function testUrlStatThrowsExceptionWhenCalledDirectly(): void
    {
        $fileReadTrapStreamWrapper = new FileReadTrapStreamWrapper();

        self::expectException(LogicException::class);
        self::expectExceptionMessage(sprintf('%s not registered: cannot operate. Do not call this method directly.', FileReadTrapStreamWrapper::class));
        $fileReadTrapStreamWrapper->url_stat('some-file.php', 0);
    }
}
