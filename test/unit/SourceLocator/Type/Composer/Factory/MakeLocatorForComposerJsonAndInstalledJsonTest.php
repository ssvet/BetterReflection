<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type\Composer\Factory;

use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\FailedToParseJson;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\InvalidProjectDirectory;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\MissingComposerJson;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\MissingInstalledJson;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory\MakeLocatorForComposerJsonAndInstalledJson;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Psr\Psr0Mapping;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Psr\Psr4Mapping;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\PsrAutoloaderLocator;
use PHPStan\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

use function array_column;
use function array_combine;
use function realpath;

/**
 * @covers \PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory\MakeLocatorForComposerJsonAndInstalledJson
 */
class MakeLocatorForComposerJsonAndInstalledJsonTest extends TestCase
{
    /** @dataProvider expectedLocators */
    public function testLocatorEquality(string $projectDirectory, SourceLocator $expectedLocatorStructure): void
    {
        self::assertEquals($expectedLocatorStructure, (new MakeLocatorForComposerJsonAndInstalledJson())
            ->__invoke($projectDirectory, BetterReflectionSingleton::instance()->astLocator()));
    }

    /**
     * @return array<string, array{0: string, 1: SourceLocator}>
     */
    public function expectedLocators(): array
    {
        $astLocator = BetterReflectionSingleton::instance()->astLocator();

        $projectA                         = realpath(__DIR__ . '/../../../../Assets/ComposerLocators/project-a');
        $projectComposerV2                = realpath(__DIR__ . '/../../../../Assets/ComposerLocators/project-using-composer-v2');
        $projectWithPsrCollisions         = realpath(__DIR__ . '/../../../../Assets/ComposerLocators/project-with-psr-collisions');
        $projectALocator                  = new AggregateSourceLocator([
            new PsrAutoloaderLocator(Psr4Mapping::fromArrayMappings([
                'ProjectA\\'    => [
                    $projectA . '/src/root_PSR-4_Sources',
                ],
                'ProjectA\\B\\' => [
                    $projectA . '/src/root_PSR-4_Sources',
                ],
                'A\\B\\'        => [
                    $projectA . '/vendor/a/b/src/ab_PSR-4_Sources',
                ],
                'C\\D\\'        => [
                    $projectA . '/vendor/a/b/src/ab_PSR-4_Sources',
                ],
                'E\\F\\'        => [
                    $projectA . '/vendor/e/f/src/ef_PSR-4_Sources',
                ],
            ]), $astLocator),
            new PsrAutoloaderLocator(Psr0Mapping::fromArrayMappings([
                'ProjectA_A_' => [
                    $projectA . '/src/root_PSR-0_Sources',
                ],
                'ProjectA_B_' => [
                    $projectA . '/src/root_PSR-0_Sources',
                ],
                'A_B_'        => [
                    $projectA . '/vendor/a/b/src/ab_PSR-0_Sources',
                ],
                'C_D_'        => [
                    $projectA . '/vendor/a/b/src/ab_PSR-0_Sources',
                ],
                'E_F_'        => [
                    $projectA . '/vendor/e/f/src/ef_PSR-0_Sources',
                ],
            ]), $astLocator),
            new DirectoriesSourceLocator([
                $projectA . '/src/root_ClassmapDir',
                $projectA . '/vendor/a/b/src/ab_ClassmapDir',
                $projectA . '/vendor/e/f/src/ef_ClassmapDir',
            ], $astLocator),
            new SingleFileSourceLocator($projectA . '/src/root_ClassmapFile', $astLocator),
            new SingleFileSourceLocator($projectA . '/vendor/a/b/src/ab_ClassmapFile', $astLocator),
            new SingleFileSourceLocator($projectA . '/vendor/e/f/src/ef_ClassmapFile', $astLocator),
            new SingleFileSourceLocator($projectA . '/src/root_File1.php', $astLocator),
            new SingleFileSourceLocator($projectA . '/src/root_File2.php', $astLocator),
            new SingleFileSourceLocator($projectA . '/vendor/a/b/src/ab_File1.php', $astLocator),
            new SingleFileSourceLocator($projectA . '/vendor/a/b/src/ab_File2.php', $astLocator),
            new SingleFileSourceLocator($projectA . '/vendor/e/f/src/ef_File1.php', $astLocator),
            new SingleFileSourceLocator($projectA . '/vendor/e/f/src/ef_File2.php', $astLocator),
        ]);
        $projectCustomVendorDir           = realpath(__DIR__ . '/../../../../Assets/ComposerLocators/project-with-custom-vendor-dir');
        $projectCustomVendorDirLocator    = new AggregateSourceLocator([
            new PsrAutoloaderLocator(Psr4Mapping::fromArrayMappings([
                'ProjectA\\'    => [
                    $projectCustomVendorDir . '/src/root_PSR-4_Sources',
                ],
                'ProjectA\\B\\' => [
                    $projectCustomVendorDir . '/src/root_PSR-4_Sources',
                ],
                'A\\B\\'        => [
                    $projectCustomVendorDir . '/custom-vendor/a/b/src/ab_PSR-4_Sources',
                ],
                'C\\D\\'        => [
                    $projectCustomVendorDir . '/custom-vendor/a/b/src/ab_PSR-4_Sources',
                ],
                'E\\F\\'        => [
                    $projectCustomVendorDir . '/custom-vendor/e/f/src/ef_PSR-4_Sources',
                ],
            ]), $astLocator),
            new PsrAutoloaderLocator(Psr0Mapping::fromArrayMappings([
                'ProjectA_A_' => [
                    $projectCustomVendorDir . '/src/root_PSR-0_Sources',
                ],
                'ProjectA_B_' => [
                    $projectCustomVendorDir . '/src/root_PSR-0_Sources',
                ],
                'A_B_'        => [
                    $projectCustomVendorDir . '/custom-vendor/a/b/src/ab_PSR-0_Sources',
                ],
                'C_D_'        => [
                    $projectCustomVendorDir . '/custom-vendor/a/b/src/ab_PSR-0_Sources',
                ],
                'E_F_'        => [
                    $projectCustomVendorDir . '/custom-vendor/e/f/src/ef_PSR-0_Sources',
                ],
            ]), $astLocator),
            new DirectoriesSourceLocator([
                $projectCustomVendorDir . '/src/root_ClassmapDir',
                $projectCustomVendorDir . '/custom-vendor/a/b/src/ab_ClassmapDir',
                $projectCustomVendorDir . '/custom-vendor/e/f/src/ef_ClassmapDir',
            ], $astLocator),
            new SingleFileSourceLocator($projectCustomVendorDir . '/src/root_ClassmapFile', $astLocator),
            new SingleFileSourceLocator($projectCustomVendorDir . '/custom-vendor/a/b/src/ab_ClassmapFile', $astLocator),
            new SingleFileSourceLocator($projectCustomVendorDir . '/custom-vendor/e/f/src/ef_ClassmapFile', $astLocator),
            new SingleFileSourceLocator($projectCustomVendorDir . '/src/root_File1.php', $astLocator),
            new SingleFileSourceLocator($projectCustomVendorDir . '/src/root_File2.php', $astLocator),
            new SingleFileSourceLocator($projectCustomVendorDir . '/custom-vendor/a/b/src/ab_File1.php', $astLocator),
            new SingleFileSourceLocator($projectCustomVendorDir . '/custom-vendor/a/b/src/ab_File2.php', $astLocator),
            new SingleFileSourceLocator($projectCustomVendorDir . '/custom-vendor/e/f/src/ef_File1.php', $astLocator),
            new SingleFileSourceLocator($projectCustomVendorDir . '/custom-vendor/e/f/src/ef_File2.php', $astLocator),
        ]);
        $projectCustomVendorDirComposerV2 = realpath(__DIR__ . '/../../../../Assets/ComposerLocators/project-with-custom-vendor-dir-using-composer-v2');

        $expectedLocators = [
            [
                $projectA,
                $projectALocator,
            ],
            [
                $projectComposerV2,
                new AggregateSourceLocator([
                    new PsrAutoloaderLocator(Psr4Mapping::fromArrayMappings([
                        'A\\B\\'        => [
                            $projectComposerV2 . '/vendor/a/b/src/ab_PSR-4_Sources',
                        ],
                    ]), $astLocator),
                    new PsrAutoloaderLocator(Psr0Mapping::fromArrayMappings([]), $astLocator),
                    new DirectoriesSourceLocator([], $astLocator),
                ]),
            ],
            [
                $projectWithPsrCollisions,
                new AggregateSourceLocator([
                    new PsrAutoloaderLocator(Psr4Mapping::fromArrayMappings([
                        'A\\' => [
                            $projectWithPsrCollisions . '/src/root_PSR-4_Sources',
                            $projectWithPsrCollisions . '/vendor/a/b/src/ab_PSR-4_Sources',
                            $projectWithPsrCollisions . '/vendor/e/f/src/ef_PSR-4_Sources',
                        ],
                        'B\\' => [
                            $projectWithPsrCollisions . '/vendor/a/b/src/ab_PSR-4_Sources',
                        ],
                    ]), $astLocator),
                    new PsrAutoloaderLocator(Psr0Mapping::fromArrayMappings([
                        'A_' => [
                            $projectWithPsrCollisions . '/src/root_PSR-0_Sources',
                            $projectWithPsrCollisions . '/vendor/a/b/src/ab_PSR-0_Sources',
                            $projectWithPsrCollisions . '/vendor/e/f/src/ef_PSR-0_Sources',
                        ],
                        'B_' => [
                            $projectWithPsrCollisions . '/vendor/a/b/src/ab_PSR-0_Sources',
                        ],
                    ]), $astLocator),
                    new DirectoriesSourceLocator([], $astLocator),
                ]),
            ],
            [
                // Relative paths are turned into absolute paths too
                __DIR__ . '/../../../../Assets/ComposerLocators/project-a',
                $projectALocator,
            ],
            [
                $projectCustomVendorDir,
                $projectCustomVendorDirLocator,
            ],
            [
                $projectCustomVendorDirComposerV2,
                new AggregateSourceLocator([
                    new PsrAutoloaderLocator(Psr4Mapping::fromArrayMappings([
                        'A\\B\\'        => [
                            $projectCustomVendorDirComposerV2 . '/custom-vendor/a/b/src/ab_PSR-4_Sources',
                        ],
                    ]), $astLocator),
                    new PsrAutoloaderLocator(Psr0Mapping::fromArrayMappings([]), $astLocator),
                    new DirectoriesSourceLocator([], $astLocator),
                ]),
            ],
        ];

        return array_combine(array_column($expectedLocators, 0), $expectedLocators);
    }

    public function testWillFailToProduceLocatorForProjectWithoutComposerJson(): void
    {
        $this->expectException(MissingComposerJson::class);

        (new MakeLocatorForComposerJsonAndInstalledJson())->__invoke(__DIR__ . '/../../../../Assets/ComposerLocators/empty-project', BetterReflectionSingleton::instance()->astLocator());
    }

    public function testWillFailToProduceLocatorForProjectWithoutInstalledJson(): void
    {
        $this->expectException(MissingInstalledJson::class);

        (new MakeLocatorForComposerJsonAndInstalledJson())->__invoke(__DIR__ . '/../../../../Assets/ComposerLocators/project-without-installed.json', BetterReflectionSingleton::instance()->astLocator());
    }

    public function testWillFailToProduceLocatorForProjectWithInvalidComposerJson(): void
    {
        $this->expectException(FailedToParseJson::class);

        (new MakeLocatorForComposerJsonAndInstalledJson())->__invoke(__DIR__ . '/../../../../Assets/ComposerLocators/project-with-invalid-composer-json', BetterReflectionSingleton::instance()->astLocator());
    }

    public function testWillFailToProduceLocatorForProjectWithInvalidInstalledJson(): void
    {
        $this->expectException(FailedToParseJson::class);

        (new MakeLocatorForComposerJsonAndInstalledJson())->__invoke(__DIR__ . '/../../../../Assets/ComposerLocators/project-with-invalid-installed-json', BetterReflectionSingleton::instance()->astLocator());
    }

    public function testWillFailToProduceLocatorForInvalidProjectDirectory(): void
    {
        $this->expectException(InvalidProjectDirectory::class);

        (new MakeLocatorForComposerJsonAndInstalledJson())->__invoke(__DIR__ . '/../../../../Assets/ComposerLocators/non-existing', BetterReflectionSingleton::instance()->astLocator());
    }
}
