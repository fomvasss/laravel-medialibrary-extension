# Upgrading

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