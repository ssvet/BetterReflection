<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util;

use function str_replace;
use const DIRECTORY_SEPARATOR;

class FileHelper
{
    public static function normalizeWindowsPath(string $path) : string
    {
        return str_replace('\\', '/', $path);
    }

    public static function normalizePath(string $path) : string
    {
        $path = str_replace('\\', '/', $path);
        if (DIRECTORY_SEPARATOR !== '\\') {
            return $path;
        }

        return str_replace('/', '\\', $path);
    }
}
