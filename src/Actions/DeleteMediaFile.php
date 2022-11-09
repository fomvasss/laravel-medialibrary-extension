<?php

namespace Fomvasss\MediaLibraryExtension\Actions;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DeleteMediaFile
{
    public function handle(Media $media): bool
    {
        return $media->delete();
    }
}
