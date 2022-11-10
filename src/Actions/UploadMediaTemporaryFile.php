<?php

namespace Fomvasss\MediaLibraryExtension\Actions;

use Illuminate\Support\Arr;

class UploadMediaTemporaryFile
{
    /**
     * @param array $attrs
     *  id int
     *  url string URL to download media
     *  file file File for upload
     *  base64 string Base64 string
     *  is_active=true boolean
     *  is_main=false boolean 
     *  weight int 
     *  title string
     *  alt int 
     *  delete boolean
     *  user_id string
     *  collection_name string
     * @return mixed
     */
    public function handle(array $attrs)
    {
        $mediaTemporaryClass = config('media-library-extension.temporary_upload_model');
        $mediaTemporaryInstance = new $mediaTemporaryClass;
        $mediaTemporaryInstance->save();

        $collectionName = Arr::get($attrs, 'collection_name', 'default');
        
        return $mediaTemporaryInstance->mediaSaveExpand($attrs, $collectionName);
    }
}
