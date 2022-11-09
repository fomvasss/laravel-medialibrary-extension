<?php

namespace Fomvasss\MediaLibraryExtension;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaManager
{
    protected $userId = null;

    /**
     * Manage (upload, update, delete) files & save Media with Request object.
     *
     * @param Model $model
     * @param Request $request
     * @throws \Exception
     */
    public function manage(Model $model, Request $request)
    {
        if (($user = $request->user()) && config('media-library-extension.use_auth_user')) {
            $this->userId = $user->id;
        }

        // Multiple
        foreach ($model->getMediaMultipleCollections() as $collectionName) {
            $this->processMultiple($model, $request, $collectionName);
        }

        // Single
        foreach ($model->getMediaSingleCollections() as $collectionName) {
            $this->processSingle($model, $request, $collectionName);
        }

        // Process deleted
        $this->processDeleted($model, $request);
    }

    /**
     * Sync, update, delete files & save Media with PHP array.
     *
     * @param Model $model
     * @param array $data
     * @param null $user
     */
    public function manageRefresh(Model $model, array $data, $user = null)
    {
        if ($user && config('media-library-extension.use_auth_user')) {
            $this->userId = $user->id;
        }

        // Multiple
        foreach ($model->getMediaMultipleCollections() as $collectionName) {
            if (isset($data[$collectionName])) {
                foreach ($data[$collectionName] as $item) {
                    if (is_array($item)) {
                        $this->manageRefreshItem($model, $item, $collectionName);
                    }
                }
            }
        }

        // Single
        foreach ($model->getMediaSingleCollections() as $collectionName) {
            if (isset($data[$collectionName])) {
                $this->manageRefreshItem($model, $data[$collectionName], $collectionName);
            }
        }

        // Process deleted
        if ($arr = Arr::get($data, config('media-library-extension.deleted_request_input'))) {
            $needDeleted = is_array($arr) ? $arr : [$arr];
            foreach ($needDeleted as $id) {
                Media::find($id)?->delete();
            }
        }
    }

    /**
     *
     * @param Model $model
     * @param array $attrs
     *  id string ID Media
     *  is_active=true boolean sometimes
     *  is_main=false boolean sometimes
     *  weight int sometimes
     *  title string sometimes
     *  alt int sometimes
     *  delete boolean sometimes If true - delete the file
     * @param string $collectionName
     * @return |null
     */
    public function manageRefreshItem(Model $model, array $attrs, string $collectionName)
    {
        $media = null;

        // Delete media
        if (isset($attrs['delete']) && $this->comparisonBooleanValue($attrs['delete'])) {
            if (!empty($attrs['id']) && ($media = Media::find($attrs['id']))) {
                $media->delete();
            }

            return $media;

        // Upd params, Model & Regenerate conversions
        } elseif (isset($attrs['id']) && ($media = Media::find($attrs['id']))) {
            
            // Sync new Media with Model
            if ($media->model_type !== $model->getMorphClass()) {

                // If model collection must by single
                if (in_array($collectionName, $model->getMediaSingleCollections())) {
                    $model->getMedia($collectionName)->each(function ($e) {
                        $e->delete();
                    });
                }

                $media->setAttribute('model_id', $model->getKey());
                $media->setAttribute('model_type', $model->getMorphClass());
                $media->setAttribute('collection_name', $collectionName);
                $media->save();

                Artisan::call('media-library:regenerate', ['--ids' => $media->id]);
            }
            $this->setExpandParams($media, $attrs, $collectionName);
        }

        return $media;
    }

    /**
     * @param Model $entity
     * @param UploadedFile $uploadedFile
     * @param string $collectionName
     * @return Media
     */
    public function saveSimple(Model $entity, UploadedFile $uploadedFile, string $collectionName): Media
    {
        $originalName = $uploadedFile->getClientOriginalName();

        $filenameGenerator = config('media-library-extension.filename_generator');
        $filename = $filenameGenerator::get($originalName);

        $media = $entity->addMedia($uploadedFile)
            ->usingFileName($filename)
            ->toMediaCollection($collectionName);

        if ($this->userId) {
            $media->setAttribute('user_id', $this->userId);
            $media->save();
        }
        return $media;
    }

    /**
     * @param Model $entity
     * @param string $base64
     * @param string $collectionName
     * @return Media
     */
    public function saveSimpleBase64(Model $entity, string $base64, string $collectionName): Media
    {
        if ($filenameGenerator = config('media-library-extension.filename_generator_base64')) {
            $filename = $filenameGenerator::get();
        } else {
            $filename = Str::random() . '.jpg';
        }

        $media = $entity->addMediaFromBase64($base64)
            ->usingFileName($filename)
            ->toMediaCollection($collectionName);

        if ($this->userId) {
            $media->setAttribute('user_id', $this->userId);
            $media->save();
        }

        return $media;
    }

    /**
     * Save expand item.
     *
     * @param Model $model
     * @param array $attrs
     *  id int sometimes
     *  url string URL to download media
     *  file file sometimes File for upload. If empty - update Media
     *  base64 base64 string for upload
     *  is_active=true boolean sometimes
     *  is_main=false boolean sometimes
     *  weight int sometimes
     *  title string sometimes
     *  alt int sometimes
     *  delete boolean sometimes If true - delete the file
     * @param string $collectionName
     * @return null|\Spatie\MediaLibrary\Models\Media
     */
    public function saveExpand(Model $model, array $attrs, string $collectionName)
    {
        $media = null;

        // Delete Media
        if (isset($attrs['delete']) && $this->comparisonBooleanValue($attrs['delete'])) {
            if (!empty($attrs['id']) && ($media = $model->media->firstWhere('id', $attrs['id']))) {
                $media->delete();
            }

            return $media;

        // Upload file by URL
        } elseif (empty($attrs['id']) && isset($attrs['url'])) {
            $filenameGenerator = config('media-library-extension.filename_generator');
            $filename = $filenameGenerator::get($attrs['url']);

            $media = $model->addMediaFromUrl($attrs['url'])
                ->usingFileName($filename)
                ->toMediaCollection($collectionName);

        // Upload from base64
        } elseif (empty($attrs['id'])
            && isset($attrs['base64'])
            && is_string($attrs['base64'])
            && strpos($attrs['base64'], ';base64') !== false) {

            $media = $this->saveSimpleBase64($model, $attrs['base64'], $collectionName);

        // Upload new Media
        } elseif (empty($attrs['id']) && isset($attrs['file']) && $attrs['file'] instanceof UploadedFile) {

            $uploadedFile = $attrs['file'];
            $originalName = $uploadedFile->getClientOriginalName();

            $filenameGenerator = config('media-library-extension.filename_generator');
            $filename = $filenameGenerator::get($originalName);

            $media = $model->addMedia($attrs['file'])
                ->usingFileName($filename)
                ->toMediaCollection($collectionName);

        // Update columns for existing Media
        } elseif (!empty($attrs['id'])) {
            $media = $model->media->firstWhere('id', $attrs['id']);
        }

        // Save additions params
        if ($media) {
            $this->setExpandParams($media, $attrs, $collectionName);
        }

        return $media;
    }

    /**
     * @param $media
     * @param array $attrs
     * @param $collectionName
     */
    protected function setExpandParams($media, array $attrs, $collectionName)
    {
        $isActive = isset($attrs['is_active'])
            ? $this->comparisonBooleanValue($attrs['is_active'])
            : true;
        $isMain = isset($attrs['is_main'])
            ? $this->comparisonBooleanValue($attrs['is_main'])
            : false;
        $userId = isset($attrs['user_id'])
            ? intval($attrs['user_id'])
            : $this->userId;

        if ($isMain && ($model = $media->model)) {
            // Unset is_main other media collecion for this model
            $model->media()
                ->where('collection_name', $collectionName)
                ->update(['is_main' => false]);
        }

        $media->setAttribute('is_active', $isActive);
        $media->setAttribute('is_main', $isMain);

        // Set custom properties
        foreach (config('media-library-extension.expand.allowed_custom_properties', []) as $property) {
            if (isset($attrs[$property])) {
                $media->setCustomProperty($property, $attrs[$property]);
            }
        }

        // Set media weight
        if (isset($attrs['weight'])) {
            $media->setAttribute('order_column', (int)$attrs['weight']);
        }
        // User (owner) media
        if ($userId) {
            $media->setAttribute('user_id', $userId);
        }

        $media->save();
    }

    /**
     * @param Model $model
     * @param Request $request
     * @param $collectionName
     */
    protected function processMultiple(Model $model, Request $request, $collectionName)
    {
        if ($request->hasFile($collectionName)) {
            foreach ($request->file($collectionName) as $file) {
                $this->saveSimple($model, $file, $collectionName);
            }
        } elseif (is_array($request->{$collectionName})) {
            foreach ($request->{$collectionName} ?? [] as $attrs) {
                $this->saveExpand($model, $attrs, $collectionName);
            }
        }

        $weightSuffix = config('media-library-extension.field_suffixes.weight', '_weight');
        if (($weight = $request->get($collectionName . $weightSuffix)) && is_array($weight)) {
            foreach ($weight as $mediaId => $value) {
                $model->media()
                    ->where('collection_name', $collectionName)
                    ->where('id', $mediaId)->update(['order_column' => $value]);
            }
        }

        $deletedSuffix = config('media-library-extension.field_suffixes.deleted', '_deleted');
        if (($ids = $request->get($collectionName . $deletedSuffix)) && is_array($ids)) {
            foreach ($ids as $mediaId) {
                if ($mediaId && ($media = $model->media->firstWhere('id', $mediaId))) {
                    $media->delete();
                }
            }
        }
    }

    /**
     * @param Model $entity
     * @param Request $request
     * @param $collectionName
     */
    protected function processSingle(Model $model, Request $request, $collectionName)
    {
        if (is_array($request->{$collectionName})) {
            if (isset($request->{$collectionName}['file']) && $request->{$collectionName}['file'] instanceof UploadedFile) {
                $model->getMedia($collectionName)->each(function ($e) {
                    $e->delete();
                });
            }

            if ($request->{$collectionName}) {
                $this->saveExpand($model, $request->{$collectionName}, $collectionName);
            }
        } elseif ($request->{$collectionName}) {

            if ($request->{$collectionName} instanceof UploadedFile) {
                $model->getMedia($collectionName)->each(function ($e) {
                    $e->delete();
                });
                $this->saveSimple($model, $request->file($collectionName), $collectionName);
            } elseif (strpos($request->{$collectionName}, ';base64') !== false) {
                $model->getMedia($collectionName)->each(function ($e) {
                    $e->delete();
                });
                $this->saveSimpleBase64($model, $request->{$collectionName}, $collectionName);
            }
        }

        $deletedSuffix = config('media-library-extension.field_suffixes.deleted', '_deleted');
        if ($mediaId = $request->get($collectionName . $deletedSuffix)) {
            if ($media = $model->media->firstWhere('id', $mediaId)) {
                $media->delete();
            }
        }
    }

    /**
     * @param Model $model
     * @param Request $request
     */
    public function processDeleted(Model $model, Request $request)
    {
        $deletedRequestInput = config('media-library-extension.deleted_request_input');

        if ($request->{$deletedRequestInput}) {

            $deletedIds = is_array($request->{$deletedRequestInput})
                ? $request->{$deletedRequestInput}
                : [$request->{$deletedRequestInput}];

            $issetIds = $model->media->pluck('id')->toArray();
            $deletedIds = array_intersect($deletedIds, $issetIds);

            foreach ($deletedIds as $mediaId) {
                if ($media = $model->media->firstWhere('id', $mediaId)) {
                    $media->delete();
                }
            }
        }
    }

    /**
     * @param $value
     * @return bool
     */
    protected function comparisonBooleanValue($value): bool
    {
        return ($value === "true" || $value === "1" || $value === true || $value === 1);
    }
}
