<?php

namespace Fomvasss\MediaLibraryExtension;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaManager
{
    protected $userId = null;

    /**
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
     * Сохранить данные записи Media и файл.
     *
     * @param Model $model
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
    public function saveExpand(Model $model, array $attrs, string $collectionName)
    {
        $media = null;

        // Delete media
        if (isset($attrs['delete']) && $this->comparisonBooleanValue($attrs['delete'])) {
            if (!empty($attrs['id']) && ($media = $model->media()->find($attrs['id']))) {
                $media->delete();
            }

            return $media;

        // Upload new media
        } elseif (empty($attrs['id']) && isset($attrs['file']) && $attrs['file'] instanceof UploadedFile) {

            $uploadedFile = $attrs['file'];
            $originalName = $uploadedFile->getClientOriginalName();

            $filenameGenerator = config('media-library-extension.filename_generator');
            $filename = $filenameGenerator::get($originalName);

            $media = $model->addMedia($attrs['file'])
                ->usingFileName($filename)
                ->toMediaCollection($collectionName);

        // Update fields for existing media
        } elseif (!empty($attrs['id'])) {
            $media = $model->media()->find($attrs['id']);
        }

        if ($media) {
            $isActive = isset($attrs['is_active'])
                ? $this->comparisonBooleanValue($attrs['is_active'])
                : true;
            $isMain = isset($attrs['is_main'])
                ? $this->comparisonBooleanValue($attrs['is_main'])
                : false;
            $userId = isset($attrs['user_id'])
                ? intval($attrs['user_id'])
                : $this->userId;
            
            if ($isMain) {
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

        return $media;
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
            foreach ($weight as $key => $value) {
                $model->media()
                    ->where('collection_name', $collectionName)
                    ->where('id', $key)->update(['order_column' => $value]);
            }
        }

        $deletedSuffix = config('media-library-extension.field_suffixes.deleted', '_deleted');
        if (($ids = $request->get($collectionName . $deletedSuffix)) && is_array($ids)) {
            foreach ($ids as $id) {
                if ($id && $model->media->where('id', $id)->first()) {
                    $model->deleteMedia($id);
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
        if ($id = $request->get($collectionName . $deletedSuffix)) {
            $model->deleteMedia($id);
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

            foreach ($deletedIds as $id) {
                $model->deleteMedia($id);
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