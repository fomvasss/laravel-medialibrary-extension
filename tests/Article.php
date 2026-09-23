<?php

declare(strict_types=1);

namespace Fomvasss\MediaLibraryExtension\Tests;

use Fomvasss\MediaLibraryExtension\HasMedia\HasMedia;
use Fomvasss\MediaLibraryExtension\HasMedia\InteractsWithMedia;
use Illuminate\Database\Eloquent\Model;

class Article extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = ['id'];

    protected array $mediaMultipleCollections = ['files'];

    protected array $mediaSingleCollections = ['cover'];
}
