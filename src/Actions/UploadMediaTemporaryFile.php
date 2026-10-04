<?php

namespace Fomvasss\MediaLibraryExtension\Actions;

use Illuminate\Support\Arr;

class UploadMediaTemporaryFile
{
    /**
     * Custom property with a hash of the uploader's session for an upload without user_id.
     * In strict refresh mode such an upload is attached only from the same session
     */
    const SESSION_PROPERTY = 'temporary_session';

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
        $mediaTemporaryClass = config('media-library-extension.temporary.model');
        $mediaTemporaryInstance = new $mediaTemporaryClass;
        $mediaTemporaryInstance->save();

        $collectionName = Arr::get($attrs, 'collection_name', 'default');

        $media = $mediaTemporaryInstance->mediaSaveExpand($attrs, $collectionName);

        if ($media && empty($media->user_id) && request()->hasSession()) {
            $media->setCustomProperty(self::SESSION_PROPERTY, self::sessionHash(request()->session()->getId()))->save();
        }

        return $media;
    }

    public static function sessionHash(string $sessionId): string
    {
        return hash('sha256', $sessionId);
    }
}
