<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory;

use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\FailedToParseJson;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\InvalidProjectDirectory;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\MissingComposerJson;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\MissingInstalledJson;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Psr\Psr0Mapping;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Psr\Psr4Mapping;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\PsrAutoloaderLocator;
use PHPStan\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;

use function array_filter;
use function array_map;
use function array_merge;
use function array_merge_recursive;
use function array_values;
use function file_get_contents;
use function is_array;
use function is_dir;
use function is_file;
use function json_decode;
use function realpath;
use function rtrim;

/**
 * @psalm-import-type ComposerAutoload from MakeLocatorForComposerJson
 */
final class MakeLocatorForComposerJsonAndInstalledJson
{
    public function __invoke(string $installationPath, Locator $astLocator): SourceLocator
    {
        $realInstallationPath = (string) realpath($installationPath);

        if (! is_dir($realInstallationPath)) {
            throw InvalidProjectDirectory::atPath($installationPath);
        }

        $composerJsonPath = $realInstallationPath . '/composer.json';

        if (! is_file($composerJsonPath)) {
            throw MissingComposerJson::inProjectPath($installationPath);
        }

        /**
         * @psalm-var array{autoload: ComposerAutoload, config: array{vendor-dir?: string}}|null $composer
         */
        $composer  = json_decode((string) file_get_contents($composerJsonPath), true);
        $vendorDir = $composer['config']['vendor-dir'] ?? 'vendor';
        $vendorDir = rtrim($vendorDir, '/');

        $installedJsonPath = $realInstallationPath . '/' . $vendorDir . '/composer/installed.json';

        if (! is_file($installedJsonPath)) {
            throw MissingInstalledJson::inProjectPath($realInstallationPath . '/' . $vendorDir);
        }

        /** @psalm-var array{packages: list<mixed[]>}|list<mixed[]>|null $installedJson */
        $installedJson = json_decode((string) file_get_contents($installedJsonPath), true);

        if (! is_array($composer)) {
            throw FailedToParseJson::inFile($composerJsonPath);
        }

        if (! is_array($installedJson)) {
            throw FailedToParseJson::inFile($installedJsonPath);
        }

        /** @psalm-var list<array{name: string, autoload: ComposerAutoload}> $installed */
        $installed = $installedJson['packages'] ?? $installedJson;

        $classMapPaths       = array_merge($this->prefixPaths($this->packageToClassMapPaths($composer), $realInstallationPath . '/'), ...array_map(function (array $package) use ($realInstallationPath, $vendorDir) : array {
            return $this->prefixPaths($this->packageToClassMapPaths($package), $this->packagePrefixPath($realInstallationPath, $package, $vendorDir));
        }, $installed));
        $classMapFiles       = array_filter($classMapPaths, 'is_file');
        $classMapDirectories = array_values(array_filter($classMapPaths, 'is_dir'));
        $filePaths           = array_merge($this->prefixPaths($this->packageToFilePaths($composer), $realInstallationPath . '/'), ...array_map(function (array $package) use ($realInstallationPath, $vendorDir) : array {
            return $this->prefixPaths($this->packageToFilePaths($package), $this->packagePrefixPath($realInstallationPath, $package, $vendorDir));
        }, $installed));

        return new AggregateSourceLocator(array_merge([
            new PsrAutoloaderLocator(Psr4Mapping::fromArrayMappings(array_merge_recursive($this->prefixWithInstallationPath($this->packageToPsr4AutoloadNamespaces($composer), $realInstallationPath), ...array_map(function (array $package) use ($realInstallationPath, $vendorDir) : array {
                return $this->prefixWithPackagePath($this->packageToPsr4AutoloadNamespaces($package), $realInstallationPath, $package, $vendorDir);
            }, $installed))), $astLocator),
            new PsrAutoloaderLocator(Psr0Mapping::fromArrayMappings(array_merge_recursive($this->prefixWithInstallationPath($this->packageToPsr0AutoloadNamespaces($composer), $realInstallationPath), ...array_map(function (array $package) use ($realInstallationPath, $vendorDir) : array {
                return $this->prefixWithPackagePath($this->packageToPsr0AutoloadNamespaces($package), $realInstallationPath, $package, $vendorDir);
            }, $installed))), $astLocator),
            new DirectoriesSourceLocator($classMapDirectories, $astLocator),
        ], ...array_map(static function (string $file) use ($astLocator) : array {
            return [new SingleFileSourceLocator($file, $astLocator)];
        }, array_merge($classMapFiles, $filePaths))));
    }

    /**
     * @param array{autoload: ComposerAutoload} $package
     *
     * @return array<string, list<string>>
     */
    private function packageToPsr4AutoloadNamespaces(array $package): array
    {
        return array_map(static function ($namespacePaths) : array {
            return (array) $namespacePaths;
        }, $package['autoload']['psr-4'] ?? []);
    }

    /**
     * @param array{autoload: ComposerAutoload} $package
     *
     * @return array<string, list<string>>
     */
    private function packageToPsr0AutoloadNamespaces(array $package): array
    {
        return array_map(static function ($namespacePaths) : array {
            return (array) $namespacePaths;
        }, $package['autoload']['psr-0'] ?? []);
    }

    /**
     * @param array{autoload: ComposerAutoload} $package
     *
     * @return list<string>
     */
    private function packageToClassMapPaths(array $package): array
    {
        return $package['autoload']['classmap'] ?? [];
    }

    /**
     * @param array{autoload: ComposerAutoload} $package
     *
     * @return list<string>
     */
    private function packageToFilePaths(array $package): array
    {
        return $package['autoload']['files'] ?? [];
    }

    /**
     * @param array{name: string} $package
     */
    private function packagePrefixPath(string $trimmedInstallationPath, array $package, string $vendorDir): string
    {
        return $trimmedInstallationPath . '/' . $vendorDir . '/' . $package['name'] . '/';
    }

    /**
     * @param array<string, list<string>> $paths
     * @param array{name: string}         $package $package
     *
     * @return array<string, list<string>>
     */
    private function prefixWithPackagePath(array $paths, string $trimmedInstallationPath, array $package, string $vendorDir): array
    {
        $prefix = $this->packagePrefixPath($trimmedInstallationPath, $package, $vendorDir);

        return array_map(function (array $paths) use ($prefix) : array {
            return $this->prefixPaths($paths, $prefix);
        }, $paths);
    }

    /**
     * @param array<string, list<string>> $paths
     *
     * @return array<string, list<string>>
     */
    private function prefixWithInstallationPath(array $paths, string $trimmedInstallationPath): array
    {
        return array_map(function (array $paths) use ($trimmedInstallationPath) : array {
            return $this->prefixPaths($paths, $trimmedInstallationPath . '/');
        }, $paths);
    }

    /**
     * @param list<string> $paths
     *
     * @return list<string>
     */
    private function prefixPaths(array $paths, string $prefix): array
    {
        return array_map(static function (string $path) use ($prefix) : string {
            return $prefix . $path;
        }, $paths);
    }
}
