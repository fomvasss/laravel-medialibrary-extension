<?php

return [

    /* -----------------------------------------------------------------
     |  Medialibrary extension settings
     | -----------------------------------------------------------------
     */
    'filename_generator' => \Fomvasss\MediaLibraryExtension\FilenameGenerators\DefaultFileNameGenerator::class,

    'default_img_quantity' => 85,

    /* ----------------------------------------------------------------
     |  Make conversions for all medias
     |  https://spatie.be/docs/image/v3/image-manipulations/resizing-images
     | ----------------------------------------------------------------
     */
    'default_conversions' => [
        'thumb' => [
            'quantity' => 75,
            //'crop' => \Spatie\Image\Enums\CropPosition::Center,
            'fit' => \Spatie\Image\Enums\Fit::Crop,
            'width' => 100,
            'height' => 100,
            'format' => 'webp',
            'blur' => 0,
            'regex_perform_to_collections' => '/img|image|photo|gallery|scr|logo|avatar/i',
            'non_queued' => true,
        ],
    ],

    'field_suffixes' => [
        'weight' => '_weight',   // request('YOUR_COLLECTION_NAME_weight')
        'deleted' => '_deleted', // request('YOUR_COLLECTION_NAME_deleted')
        'custom' => '_custom', // request('YOUR_COLLECTION_NAME_custom')
    ],

    'deleted_request_input' => 'media_deleted', // request('media_deleted')

    // Якщо true — при збереженні медіа автоматично заповнює поле user_id з авторизованого юзера
    'use_auth_user' => false,

    /*
     * Restricts manageRefresh() / mediaManageRefresh() to data that belongs to the model.
     * Enable it when the data comes from a client (API) rather than a trusted admin panel.
     *  - attach by id: only media of this model or a temporary upload
     *  - delete (`delete` flag, `media_deleted`): only media of this model
     *  - `user_id` from the data is ignored
     * Other ids are silently skipped. Who may attach a given temporary upload is up to the project.
     */
    'strict_refresh' => false,

    /*
     *  id int|null
     *  file|null File for upload. If empty - update Media fields
     *  is_active=true boolean sometimes
     *  is_main=false boolean sometimes
     *  weight int sometimes
     *  title string sometimes (custom_propertie)
     *  alt string sometimes (custom_propertie)
     *  delete boolean sometimes If true - delete the media
     */
    'expand' => [
        'allowed_custom_properties' => [
            'alt', 'title',
        ],
    ],

    'temporary' => [
        'model' => \Fomvasss\MediaLibraryExtension\Models\MediaTemporary::class,
        'cleartime' => 60 * 24, // in minutes
    ],
];
