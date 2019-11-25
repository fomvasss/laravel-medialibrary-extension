<?php


namespace Fomvasss\MediaLibraryExtension\FilenameGenerators;


interface FileNameGeneratorInterface
{
    public static function get(string $originalName): string;
}