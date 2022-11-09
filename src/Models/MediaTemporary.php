<?php

namespace Fomvasss\MediaLibraryExtension\Models;

use Fomvasss\MediaLibraryExtension\HasMedia\HasMedia;
use Fomvasss\MediaLibraryExtension\HasMedia\InteractsWithMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MediaTemporary extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = ['id'];
}
