# Upgrading

## From v5 to v6

1. Change config `default_conversions.thumb.crop-method` set `\Spatie\Image\Enums\CropPosition::Center`
2. Change config `temporary_upload_model` to `temporary.model`
3. Add `temporary.cleartime`, example 60 minutes
4. Rename used class `ClearMediaTemporaryFiles` to `ClearMediaTemporary`

## From v4 to v5

1. Nothing to change :)

## From v3 to v4

1. Add to database table `media` columns:
    
    ```
    $table->boolean('is_main')->default(false)->after('size');
    $table->boolean('is_active')->default(true)->after('is_main');
    ```

2. Rename in your Eloquent models fields:
    ```
    protected $mediaFieldsSingle => $mediaSingleCollections;
    protected $mediaFieldsMultiple => $mediaMultipleCollections;
    ``` 

3. Update return type `customMediaConversions()` method - add return `void` type:
    ```php
       public function customMediaConversions(Media $media = null): void
    ```

4. Change:
    `Fomvasss\MediaLibraryExtension\MediaLibraryManager` => `Fomvasss\MediaLibraryExtension\MediaManager`

5. Change Facade `MediaLibrary` => `MediaManager` (Add alias `'MediaManager' => \Fomvasss\MediaLibraryExtension\Facade::class,`)

6. Add to config file `media-library-extension.php` new values: `deleted_request_input`, `expand`. Or replace old config file.
