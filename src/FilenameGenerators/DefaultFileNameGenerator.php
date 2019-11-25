<?php


namespace Fomvasss\MediaLibraryExtension\FilenameGenerators;

class DefaultFileNameGenerator implements FileNameGeneratorInterface
{
    public static function get(string $originalName): string
    {
        $fileName = pathinfo($originalName, PATHINFO_FILENAME);

        $fileName = \Illuminate\Support\Str::slug($fileName);

        $fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);

        return $fileName . '.' . $fileExtension;
    }
}