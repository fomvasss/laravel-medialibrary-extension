<?php

namespace Fomvasss\MediaLibraryExtension\FilenameGenerators;

use Fomvasss\MediaLibraryExtension\FilenameGenerators\FileNameGeneratorInterface;
use Illuminate\Support\Str;

class RandomFileNameGenerator implements FileNameGeneratorInterface
{
    public static function get(string $originalName): string
    {
        $fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);

        return Str::random(32) . '.' . $fileExtension;
    }
}
