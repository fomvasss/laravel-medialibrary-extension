# Laravel Medialibrary Extension

[![License](https://img.shields.io/packagist/l/fomvasss/laravel-medialibrary-extension.svg?style=for-the-badge)](https://packagist.org/packages/fomvasss/laravel-medialibrary-extension)
[![Build Status](https://img.shields.io/github/stars/fomvasss/laravel-medialibrary-extension.svg?style=for-the-badge)](https://github.com/fomvasss/laravel-medialibrary-extension)
[![Latest Stable Version](https://img.shields.io/packagist/v/fomvasss/laravel-medialibrary-extension.svg?style=for-the-badge)](https://packagist.org/packages/fomvasss/laravel-medialibrary-extension)
[![Total Downloads](https://img.shields.io/packagist/dt/fomvasss/laravel-medialibrary-extension.svg?style=for-the-badge)](https://packagist.org/packages/fomvasss/laravel-medialibrary-extension)
[![Quality Score](https://img.shields.io/scrutinizer/g/fomvasss/laravel-medialibrary-extension.svg?style=for-the-badge)](https://scrutinizer-ci.com/g/fomvasss/laravel-medialibrary-extension)

Extensions to the file management functionality of package [spatie/laravel-medialibrary](https://github.com/spatie/laravel-medialibrary)

----------

## Installation

Run from the command line:

```bash
composer require fomvasss/laravel-medialibrary-extension
```

## Publishing

```bash
php artisan vendor:publish --provider="Fomvasss\MediaLibraryExtension\ServiceProvider"
```

#### Publish `spatie/laravel-medialibrary` if needed
```bash
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="migrations"
php artisan migrate
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="config"
```

## Integration

Usage in Eloquent models trait

```Fomvasss\MediaLibraryExtension\Models\Traits\HasMediaTrait```

and implements interface 

 ```Fomvasss\MediaLibraryExtension\Models\Traits\HasMedia```

Usage in controller (or managers, etc.) next class

```MediaLibraryManager``` 

or facade

```MediaLibraryManager```

## Usage

`app/Models/Article.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\Models\Media;
use Fomvasss\MediaLibraryExtension\Models\Traits\HasMedia\HasMediaTrait;
use Fomvasss\MediaLibraryExtension\Models\Traits\HasMedia\HasMedia;

class Article extends Model implements HasMedia
{
    use HasMediaTrait;
    
    // html-input name == media collection name, example:
    protected $mediaFieldsSingle = ['image']; 
    protected $mediaFieldsMultiple = ['images', 'files'];

    /**
     * Optional method.
     */
    public function customMediaConversions(Media $media = null)
    {
        $this->addMediaCollection('main')
            ->singleFile();

        $this->addMediaConversion('table')
            ->format('jpg')->quality(93)
            ->fit('crop', 360, 257);
    }
}
```

`app/Http/Controllers/Article.php`

```php
<?php 

namespace App\Http\Controllers;

use Fomvasss\MediaLibraryExtension\MediaLibraryManager;

class HomeController extends Controller 
{
    public function store(Request $request, MediaLibraryManager $mediaMng)
    {
    	// create entity
        $article = \App\Model\Article::create($request->all());

		$mediaMng->manage($article, $request);
        // it's all, your files saved :)
    }
    
    public function show($id)
    {
        $article = \App\Model\Article::findOrFail($id);
        
        dd($article->getFirstMediaUrl('image'));
        // also available all methods medialibrary!
    }   
}
```

## Examples html-form

For upload files from collection name `images` you need send form with next data:
```html
<input type="file" multiple="" name="images[]">
```

For set sort order file from collection name `images` and `id = 13, 15` you need send form with next data (`weight = 21, 22`):
```html
<input type="hidden" name="images_weight[15]" value="21">
<input type="hidden" name="images_weight[13]" value="22">
```

For deleted file from collection name `images` and `id = 13, 15` you need send form with next data:
```html
<input type="hidden" name="images_deleted[]" value="13">
<input type="hidden" name="images_deleted[]" value="15">
```

## Links

Cool package [spatie/laravel-medialibrary](https://github.com/spatie/laravel-medialibrary)