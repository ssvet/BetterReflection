<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\SourceLocator\Located;

use InvalidArgumentException;
use PHPStan\BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use PHPStan\BetterReflection\SourceLocator\FileChecker;
use PHPStan\BetterReflection\Util\FileHelper;

/**
 * Value object containing source code that has been located.
 *
 * @internal
 */
class LocatedSource
{
    /** @var string */
    private $source;

    /** @var string|null */
    private $filename;

    /**
     * @throws InvalidArgumentException
     * @throws InvalidFileLocation
     */
    public function __construct(string $source, ?string $filename)
    {
        if ($filename !== null) {
            FileChecker::assertReadableFile($filename);

            $filename = FileHelper::normalizeWindowsPath($filename);
        }

        $this->source   = $source;
        $this->filename = $filename;
    }

    public function getSource() : string
    {
        return $this->source;
    }

    public function getFileName() : ?string
    {
        return $this->filename;
    }

    /**
     * Is the located source in PHP internals?
     */
    public function isInternal() : bool
    {
        return false;
    }

    public function getExtensionName() : ?string
    {
        return null;
    }

    /**
     * Is the located source produced by eval() or \function_create()?
     */
    public function isEvaled() : bool
    {
        return false;
    }
}
