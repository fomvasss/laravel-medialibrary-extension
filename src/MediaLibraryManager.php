<?php
/**
 * Created by PhpStorm.
 * User: fomvasss
 * Date: 25.11.2019
 * Time: 1:03
 */

namespace Fomvasss\MediaLibraryExtension;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\Models\Media;

class MediaLibraryManager
{
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

    public function add(Model $entity, UploadedFile $uploadedFile, string $collectionName): Media
    {
        $originalName = $uploadedFile->getClientOriginalName();

        $filenameGenerator = config('medialibrary-extension.filename_generator');
        $filename = $filenameGenerator::get($originalName);

//        $fileName = pathinfo($originalName, PATHINFO_FILENAME);
//        $fileName = \Illuminate\Support\Str::slug($fileName);
//        $fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);

        return $entity->addMedia($uploadedFile)
            ->usingFileName($filename)
            ->toMediaCollection($collectionName);
    }

    protected function processMultiple(Model $entity, Request $request, $field)
    {
        //$request->validate($entity->getMediaFieldsValidation($field));
        $validator = \Validator::make($request->only($field), $entity->getMediaFieldsValidation($field));

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        if ($request->hasFile($field)) {
            //$entity->addMultipleMediaFromRequest([$field])->each(function ($fileAdder) use ($field) {
            //    $fileAdder->toMediaCollection($field);
            //});
            foreach ($request->file($field) as $file) {
                $this->add($entity, $file, $field);
            }
        }

        $weightSuffix = config('medialibrary-extension.field_suffixes.weight', '_weight');
        if (($weight = $request->get($field . $weightSuffix)) && is_array($weight)) {
            foreach ($weight as $key => $value) {
                $entity->media()->where('id', $key)->update(['order_column' => $value]);
            }
        }

        $deletedSuffix = config('medialibrary-extension.field_suffixes.weight', '_deleted');
        if (($ids = $request->get($field . $deletedSuffix)) && is_array($ids)) {
            array_map(function($id) use ($entity) {
                $id ? $entity->deleteMedia($id) : null;
            }, $ids);
        }
        //$entity->media()->whereIn('id', $ids)->delete();
    }

    protected function processSingle(Model $entity, Request $request, $field)
    {
        //$request->validate($entity->getMediaFieldsValidation($field));
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

        $deletedSuffix = config('medialibrary-extension.field_suffixes.weight', '_deleted');
        if ($id = $request->get($field . $deletedSuffix)) {
            $entity->deleteMedia($id);
        }
    }
}