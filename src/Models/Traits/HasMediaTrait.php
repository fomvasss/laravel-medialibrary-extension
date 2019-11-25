<?php
/**
 * Created by PhpStorm.
 * User: fomvasss
 * Date: 06.01.19
 * Time: 18:48
 */

namespace Fomvasss\MediaLibraryExtension\Models\Traits\HasMedia;

use Illuminate\Support\Str;
use Spatie\MediaLibrary\Models\Media;

trait HasMediaTrait
{
    use \Spatie\MediaLibrary\HasMedia\HasMediaTrait;

    /**
     * Define this in your model.
     * @var int
     */
    // protected $mediaQuality;

    /**
     * Define this in your model.
     * @var array
     */
     // protected $mediaFieldsSingle = ['file', 'image',];

    /**
     * Define this in your model.
     * @var array
     */
     // protected $mediaFieldsMultiple = ['files', 'images',];

    /**
     * Define this in your model if needed validation.
     * @var array
     */
    /*
     protected $mediaFieldsValidation = [
        'file' => 'required|file',
        'images' => 'required|array|max:4',
        'images.*' => 'image|file|max:1024',
    ];*/

    /**
     * Redefine this in your model,
     * like spatie registerMediaConversions.
     *
     * @param \Spatie\MediaLibrary\Models\Media|null $media
     */
    public function customMediaConversions(Media $media = null)
    {
        //...
    }

    /**
     * @param string $collectionName
     * @param string $conversionName
     * @param string $defaultUrl
     * @return string
     */
    public function getMyFirstMediaUrl(string $collectionName = 'default', string $conversionName = '', string $defaultUrl = ''): string
    {
        if ($media = $this->getFirstMedia($collectionName)) {
            return $media->getUrl($conversionName);
        }

        return $defaultUrl;
    }

    /**
     * @param int $mediaQuality
     * @return HasMediaTrait
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
        return isset($this->mediaQuality) && is_int($this->mediaQuality) ? $this->mediaQuality : config('medialibrary-extension.default_img_quantity');
    }

    /**
     * @param Media|null $media
     * @throws \Spatie\Image\Exceptions\InvalidManipulation
     */
    public function defaultRegisterMediaConversions(Media $media = null)
    {
        //$this->addMediaCollection('image')
        //    ->singleFile();
        foreach (config('medialibrary-extension.default_conversions') as $conversionName => $params) {
            $this->addMediaConversion($conversionName)
                ->quality($params['quantity'] ?? $this->getMediaQuality())
                ->crop($params['crop-method'] ?? 'crop-center', $params['width'] ?? 50, $params['height'] ?? 50)
                ->performOnCollections(...$this->getPerformOnImageCollections($params['regex_perform_to_collections'] ?? null));
        }
    }

    /**
     * @return array
     */
    public function getPerformOnImageCollections(string $pattern = null): array
    {
        $mediaFields = array_values(array_merge($this->getMediaFieldsMultiple(), $this->getMediaFieldsSingle()));
        $pattern = $pattern ?: '/img|image|photo|gallery|scr/i';
        $performOnCollections = [];

        foreach ($mediaFields as $field) {
            if (preg_match($pattern ,$field)) {
                $performOnCollections[] = $field;
            }
        }

        return $performOnCollections;
    }

    /**
     * @param \Spatie\MediaLibrary\Models\Media|null $media
     * @throws \Spatie\Image\Exceptions\InvalidManipulation
     */
    public function registerMediaConversions(Media $media = null)
    {
        $this->defaultRegisterMediaConversions($media);

        $this->customMediaConversions($media);
    }

    /**
     * @return array
     */
    public function getMediaFieldsSingle(): array
    {
        $res = isset($this->mediaFieldsSingle) ? (is_array($this->mediaFieldsSingle) ? $this->mediaFieldsSingle : [$this->mediaFieldsSingle]) : [];

        return $res;
    }

    /**
     * @return array
     */
    public function getMediaFieldsMultiple(): array
    {
        $res = isset($this->mediaFieldsMultiple) ? (is_array($this->mediaFieldsMultiple) ? $this->mediaFieldsMultiple : [$this->mediaFieldsMultiple]) : [];

        return $res;
    }

    /**
     * @param array $mediaFieldsSingle
     * @return $this
     */
    public function setMediaFieldsSingle(array $mediaFieldsSingle)
    {
        $this->mediaFieldsSingle = $mediaFieldsSingle;

        return $this;
    }

    /**
     * @param array $mediaFieldsMultiple
     * @return $this
     */
    public function setMediaFieldsMultiple(array $mediaFieldsMultiple)
    {
        $this->mediaFieldsMultiple = $mediaFieldsMultiple;

        return $this;
    }

    /**
     * @param string|null $field
     * @return array|mixed|string
     */
    public function getMediaFieldsValidation(string $field = null): array
    {
        $allRules = isset($this->mediaFieldsValidation) && is_array($this->mediaFieldsValidation) ? $this->mediaFieldsValidation : [];

        $rules = [];

        if ($field) {
            $rules[$field] = $allRules[$field] ?? '';
            if (isset($allRules[$field . '.*']) ) {
                $rules[$field . '.*'] = $allRules[$field . '.*'];
            }
        } else {
            return $allRules;
        }

        return $rules;
    }

    /**
     * @param array $rules
     * @return $this
     */
    public function setMediaFieldsValidation(array $rules = [])
    {
        $this->mediaFieldsValidation = $rules;

        return $this;
    }

    /**
     * @param array $rules
     * @return $this
     */
    public function addMediaFieldsValidation(array $rules = [])
    {
        $rules = array_merge($this->getMediaFieldsValidation(), $rules);

        $this->setMediaFieldsValidation($rules);

        return $this;
    }
}