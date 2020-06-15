<?php

return [

    /* -----------------------------------------------------------------
     |  Medialibrary extension settings
     | -----------------------------------------------------------------
     */
    'filename_generator' => \Fomvasss\MediaLibraryExtension\FilenameGenerators\DefaultFileNameGenerator::class,

    'default_img_quantity' => 75,

    'default_conversions' => [
        'thumb' => [
            'quantity' => 75,
            'crop-method' => 'crop-center',
            'width' => 100,
            'height' => 100,
            'regex_perform_to_collections' => '/img|image|photo|gallery|scr/i',
        ],
    ],

    'field_suffixes' => [
        'weight' => '_weight',
        'deleted' => '_deleted',
    ]
];
