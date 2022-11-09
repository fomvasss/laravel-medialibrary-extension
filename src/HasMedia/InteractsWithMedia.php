<?php

namespace Fomvasss\MediaLibraryExtension\HasMedia;

use Fomvasss\MediaLibraryExtension\MediaManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait InteractsWithMedia
{
    use \Spatie\MediaLibrary\InteractsWithMedia;

    // Define this options in your model:
    // protected array $mediaMultipleCollections = ['files', 'images',];
    // protected array $mediaSingleCollections = ['file', 'image',];
    // protected $mediaQuality;

    /**
     * Redefine this in your model, like spatie registerMediaConversions.
     *
     * @param Media $media
     */
    public function customMediaConversions(Media $media = null): void
    {
        //...
    }

    /**
     * @param Media|null $media
     * @throws \Spatie\Image\Exceptions\InvalidManipulation
     */
    public function defaultRegisterMediaConversions(Media $media = null)
    {
        foreach (config('media-library-extension.default_conversions') as $conversionName => $params) {
            if (is_array($params) && count($params)) {
                $this->addMediaConversion($conversionName)
                    ->quality($params['quantity'] ?? $this->getMediaQuality())
                    ->crop($params['crop-method'] ?? 'crop-center', $params['width'] ?? 100, $params['height'] ?? 100)
                    ->performOnCollections(...$this->getPerformOnImageCollections($params['regex_perform_to_collections'] ?? null));
            }
        }
    }

    /**
     * @param Media $media
     * @throws \Spatie\Image\Exceptions\InvalidManipulation
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->defaultRegisterMediaConversions($media);

        $this->customMediaConversions($media);
    }


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
    ): string {
        if ($media = $this->getFirstMedia($collectionName)) {
            return $media->getUrl($conversionName);
        }

        return $defaultUrl;
    }

    /**
     * @param string $collectionName
     * @param string $conversionName
     * @param string $defaultUrl
     * @return string
     */
    public function getMyFirstMediaFullUrl(
        string $collectionName = 'default',
        string $conversionName = '',
        string $defaultUrl = ''
    ): string {
        if ($media = $this->getFirstMedia($collectionName)) {
            return $media->getFullUrl($conversionName);
        }

        return $defaultUrl;
    }

    /**
     * @return array
     */
    public function getMediaSingleCollections(): array
    {
        if (isset($this->mediaSingleCollections)) {
            return Arr::wrap($this->mediaSingleCollections);
        }

        return [];
    }

    /**
     * @return array
     */
    public function getMediaMultipleCollections(): array
    {
        if (isset($this->mediaMultipleCollections)) {
            return Arr::wrap($this->mediaMultipleCollections);
        }

        return [];
    }

    public function getMediaFieldsMultiple(): array
    {
        return  $this->getMediaMultipleCollections();
    }

    /**
     * @param array $mediaSingleCollections
     * @return $this
     */
    public function setMediaSingleCollections(array $collections)
    {
        $this->mediaSingleCollections = $collections;

        return $this;
    }

    /**
     * @param array $fields
     * @return $this
     */
    public function setMediaMultipleCollections(array $collections)
    {
        $this->mediaMultipleCollections = $collections;

        return $this;
    }

    /**
     * @param string $collectionName
     * @return mixed
     */
    public function getMainMedia(string $collectionName = 'default')
    {
        return $this->getMedia($collectionName)
            ->where('is_main', true)
            ->where('is_active', true)
            ->first() ?: $this->getMedia($collectionName)->first();
    }

    /**
     * @param string $collectionName
     * @param string $conversionName
     * @param string $defaultUrl
     * @return string
     */
    public function getMainMediaUrl(
        string $collectionName = 'default',
        string $conversionName = '',
        string $defaultUrl = ''
    ): string {
        if ($media = $this->getMainMedia($collectionName)) {
            return $media->getUrl($conversionName);
        }

        return $defaultUrl;
    }

    /**
     * @param int $mediaQuality
     * @return InteractsWithMedia
     */
    public function setMediaQuality(int $mediaQuality): self
    {
        $this->mediaQuality = $mediaQuality;

        return $this;
    }

    /**
     * @return int
     */
    public function getMediaQuality(): int
    {
        return isset($this->mediaQuality) && is_int($this->mediaQuality)
            ? $this->mediaQuality
            : config('media-library-extension.default_img_quantity');
    }

    /**
     * @return array
     */
    protected function getPerformOnImageCollections(string $pattern = null): array
    {
        $mediaFields = array_values(array_merge($this->getMediaMultipleCollections(), $this->getMediaSingleCollections()));
        $pattern = $pattern ?: '/img|image|photo|gallery|avatar/scr/i';
        $performOnCollections = [];

        foreach ($mediaFields as $field) {
            if (preg_match($pattern, $field)) {
                $performOnCollections[] = $field;
            }
        }

        return $performOnCollections;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function mediaManage(\Illuminate\Http\Request $request)
    {
        $manager = app(MediaManager::class);

        return $manager->manage($this, $request);
    }

    /**
     * @param array $data
     * @param null $user
     * @return mixed
     */
    public function mediaManageRefresh(array $data, $user = null)
    {
        $manager = app(MediaManager::class);

        return $manager->manageRefresh($this, $data, $user);
    }

    /**
     * @param array $attrs
     * @param string $collectionName
     * @return mixed
     */
    public function mediaSaveExpand(array $attrs, string $collectionName)
    {
        $manager = app(MediaManager::class);

        return $manager->saveExpand($this, $attrs, $collectionName);
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param string $collectionName
     * @return mixed
     */
    public function mediaSaveSimple(UploadedFile $uploadedFile, string $collectionName)
    {
        $manager = app(MediaManager::class);

        return $manager->saveSimple($this, $uploadedFile, $collectionName);
    }
    
    /**
     * @param $mediaIds
     */
    public function deleteMedias($mediaIds)
    {
        $mediaIds = is_array($mediaIds) ? $mediaIds : [$mediaIds];
        $issetIds = $this->media->pluck('id')->toArray();
        $ids = array_intersect($mediaIds, $issetIds);

        foreach ($ids as $mediaId) {
            if ($media = $this->media->where('id', $mediaId)->first()) {
                $media->delete();
            }
        }
    }
}