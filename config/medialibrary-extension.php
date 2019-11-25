<?php

return [

    /* -----------------------------------------------------------------
     |  The default Model meta-tag
     | -----------------------------------------------------------------
     */
//    'filename_generator' => \Fomvasss\MediaLibraryExtension\FilenameGenerators\DefaultFileNameGenerator::class,
    'filename_generator' => \App\MyMedianameGenerator::class,

    'default_img_quantity' => 75,

    'default_conversions' => [
        'thumb' => [
            'quantity' => 75,
            'crop-method' => 'crop-center',
            'width' => 100,
            'height' => 100,
            'regex_perform_to_collections' => '/img|image|photo|gallery|scr/i',
        ],
        'my' => [
            'quantity' => 100,
            'crop-method' => 'crop-center',
            'width' => 200,
            'height' => 200,
            'regex_perform_to_collections' => '/img|image|photo|gallery|scr/i',
        ],
    ],

    'field_suffixes' => [
        'weight' => '_weight',
        'deleted' => '_deleted',
    ]
];
