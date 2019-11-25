<?php

namespace Fomvasss\MediaLibraryExtension;

class Facade extends \Illuminate\Support\Facades\Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return MediaLibraryManager::class;
    }
}
