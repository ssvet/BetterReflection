<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Located;

use InvalidArgumentException;
use Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use Roave\BetterReflection\SourceLocator\FileChecker;
use Roave\BetterReflection\Util\FileHelper;

/**
 * Value object containing source code that has been located.
 *
 * @internal
 */
class LocatedSource
{
    /**
     * @var string|null
     */
    private $filename;
    /**
     * @var string
     */
    private $source;
    /**
     * @var string|null
     */
    private $name;

    /**
     * @throws InvalidArgumentException
     * @throws InvalidFileLocation
     */
    public function __construct(string $source, ?string $name, ?string $filename = null)
    {
        $this->source = $source;
        $this->name = $name;
        if ($filename !== null) {
            FileChecker::assertReadableFile($filename);

            $filename = FileHelper::normalizeWindowsPath($filename);
        }

        $this->filename = $filename;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getFileName(): ?string
    {
        return $this->filename;
    }

    /**
     * Is the located source in PHP internals?
     */
    public function isInternal(): bool
    {
        return false;
    }

    public function getExtensionName(): ?string
    {
        return null;
    }

    /**
     * Is the located source produced by eval() or \function_create()?
     */
    public function isEvaled(): bool
    {
        return false;
    }

    public function getAliasName(): ?string
    {
        return null;
    }
}
