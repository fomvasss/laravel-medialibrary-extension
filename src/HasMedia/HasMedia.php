<?php

namespace Fomvasss\MediaLibraryExtension\HasMedia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

interface HasMedia extends \Spatie\MediaLibrary\HasMedia
{
    /**
     * @param Media|null $media
     * @return mixed
     */
    public function customMediaConversions(Media $media = null): void;
    
    /**
     * @return array
     */
    public function getMediaSingleCollections(): array;

    /**
     * @return array
     */
    public function getMediaMultipleCollections(): array;

    /**
     * @param array $collections
     */
    public function setMediaSingleCollections(array $collections);

    /**
     * @param array $collections
     */
    public function setMediaMultipleCollections(array $collections);

    /**
     * @param string $collectionName
     * @param string $conversionName
     * @param string $defaultUrl
     * @return string
     */
    public function getMyFirstMediaUrl(
        string $collectionName = 'default',
        string $conversionName = '',
        string $defaultUrl = ''
    ): string;

    public function getMyFirstMediaFullUrl(
        string $collectionName = 'default',
        string $conversionName = '',
        string $defaultUrl = ''
    ): string;

    public function getMainMedia(string $collectionName = 'default');

    public function getMainMediaUrl(
        string $collectionName = 'default',
        string $conversionName = '',
        string $defaultUrl = ''
    ): string;

    public function mediaManage(\Illuminate\Http\Request $request);

    /**
     * @param array $attrs
     *  id int sometimes
     *  id file sometimes File for upload. If empty - update Media
     *  is_active=true boolean sometimes
     *  is_main=false boolean sometimes
     *  weight int sometimes
     *  title string sometimes
     *  alt int sometimes
     *  delete boolean sometimes If true - delete the file
     * @param string $collectionName
     * @return null|\Spatie\MediaLibrary\Models\Media
     */
    public function mediaSaveExpand(array $attrs, string $collectionName);

    /**
     * @param Model $entity
     * @param UploadedFile $uploadedFile
     * @param string $collectionName
     * @return \Spatie\MediaLibrary\Models\Media
     */
    public function mediaSaveSimple(UploadedFile $uploadedFile, string $collectionName);
}