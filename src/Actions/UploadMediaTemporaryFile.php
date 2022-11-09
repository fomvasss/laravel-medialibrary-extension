<?php

namespace Fomvasss\MediaLibraryExtension\Actions;

use Illuminate\Support\Arr;

class UploadMediaTemporaryFile
{
    public function handle(array $data)
    {
        // Create temporary model
        $mediaTemporaryClass = config('media-library-extension.temporary_upload_model');
        $mediaTemporaryInstance = new $mediaTemporaryClass;
        $mediaTemporaryInstance->save();

        $collectionName = Arr::get($data, 'collection_name', 'default');
        
        return $mediaTemporaryInstance->mediaSaveExpand(Arr::only($data, [
            'file', 'is_main', 'user_id', 'url',
        ]), $collectionName);
    }
}
