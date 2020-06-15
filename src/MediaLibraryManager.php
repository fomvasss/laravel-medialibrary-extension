<?php

namespace Fomvasss\MediaLibraryExtension;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaLibraryManager
{
    /**
     * @param Model $entity
     * @param Request $request
     * @throws \Exception
     */
    public function manage(Model $entity, Request $request)
    {
        if (method_exists($entity, 'getMediaFieldsMultiple') === false) {
            throw new \Exception("Method 'getMediaFieldsMultiple' not found in model class " . class_basename($entity));
        }

        if (method_exists($entity, 'getMediaFieldsSingle') === false) {
            throw new \Exception("Method 'getMediaFieldsSingle' not found in model class " . class_basename($entity));
        }

        // Multiple fields
        foreach ($entity->getMediaFieldsMultiple() as $field) {
            $this->processMultiple($entity, $request, $field);
        }

        // Single field
        foreach ($entity->getMediaFieldsSingle() as $field) {
            $this->processSingle($entity, $request, $field);
        }
    }

    /**
     * @param Model $entity
     * @param UploadedFile $uploadedFile
     * @param string $collectionName
     * @return Media
     */
    public function add(Model $entity, UploadedFile $uploadedFile, string $collectionName): Media
    {
        $originalName = $uploadedFile->getClientOriginalName();

        $filenameGenerator = config('media-library-extension.filename_generator');
        $filename = $filenameGenerator::get($originalName);

        return $entity->addMedia($uploadedFile)
            ->usingFileName($filename)
            ->toMediaCollection($collectionName);
    }

    /**
     * @param Model $entity
     * @param Request $request
     * @param $field
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function processMultiple(Model $entity, Request $request, $field)
    {
        $validator = \Validator::make($request->only($field), $entity->getMediaFieldsValidation($field));

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        if ($request->hasFile($field)) {
            foreach ($request->file($field) as $file) {
                $this->add($entity, $file, $field);
            }
        }

        $weightSuffix = config('media-library-extension.field_suffixes.weight', '_weight');
        if (($weight = $request->get($field . $weightSuffix)) && is_array($weight)) {
            foreach ($weight as $key => $value) {
                $entity->media()->where('id', $key)->update(['order_column' => $value]);
            }
        }

        $deletedSuffix = config('media-library-extension.field_suffixes.deleted', '_deleted');
        if (($ids = $request->get($field . $deletedSuffix)) && is_array($ids)) {
            array_map(function($id) use ($entity) {
                $id ? $entity->deleteMedia($id) : null;
            }, $ids);
        }
    }

    /**
     * @param Model $entity
     * @param Request $request
     * @param $field
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function processSingle(Model $entity, Request $request, $field)
    {
        $validator = \Validator::make($request->only($field), $entity->getMediaFieldsValidation($field));

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        if ($request->hasFile($field)) {
            $entity->getMedia($field)->each(function ($e) {
                $e->delete();
            });

            $this->add($entity, $request->file($field), $field);
        }

        $deletedSuffix = config('media-library-extension.field_suffixes.deleted', '_deleted');
        if ($id = $request->get($field . $deletedSuffix)) {
            $entity->deleteMedia($id);
        }
    }
}