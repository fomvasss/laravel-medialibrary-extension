# Laravel Medialibrary Extension

[![License](https://img.shields.io/packagist/l/fomvasss/laravel-medialibrary-extension.svg?style=for-the-badge)](https://packagist.org/packages/fomvasss/laravel-medialibrary-extension)
[![Build Status](https://img.shields.io/github/stars/fomvasss/laravel-medialibrary-extension.svg?style=for-the-badge)](https://github.com/fomvasss/laravel-medialibrary-extension)
[![Latest Stable Version](https://img.shields.io/packagist/v/fomvasss/laravel-medialibrary-extension.svg?style=for-the-badge)](https://packagist.org/packages/fomvasss/laravel-medialibrary-extension)
[![Total Downloads](https://img.shields.io/packagist/dt/fomvasss/laravel-medialibrary-extension.svg?style=for-the-badge)](https://packagist.org/packages/fomvasss/laravel-medialibrary-extension)
[![Quality Score](https://img.shields.io/scrutinizer/g/fomvasss/laravel-medialibrary-extension.svg?style=for-the-badge)](https://scrutinizer-ci.com/g/fomvasss/laravel-medialibrary-extension)

Extensions to the file management functionality of package [spatie/laravel-medialibrary](https://github.com/spatie/laravel-medialibrary)

----------

## Installation

```bash
composer require fomvasss/laravel-medialibrary-extension
```

Publish `spatie/laravel-medialibrary` (if this has not been done before)
```bash
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-config"
```

Publish `fomvasss/laravel-medialibrary-extension`
```bash
php artisan vendor:publish --provider="Fomvasss\MediaLibraryExtension\ServiceProvider"
```

Run migration:
```bash
php artisan migrate
```

Add facade to `config/app.php` to `aliases` (optional):

```php
//...
'MediaManager' => \Fomvasss\MediaLibraryExtension\Facade::class,
//...
```

## Integration in Eloquent models

Implements interface:
 ```Fomvasss\MediaLibraryExtension\HasMedia\HasMedia```

Usage in Eloquent models trait:
```Fomvasss\MediaLibraryExtension\HasMedia\InteractsWithMedia```

## Usage

In model `app/Models/Article.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\Models\Media;
use Fomvasss\MediaLibraryExtension\HasMedia\HasMedia;
use Fomvasss\MediaLibraryExtension\HasMedia\InteractsWithMedia;

class Article extends Model implements HasMedia
{
    use InteractsWithMedia;
    
    // html-input name == media collection name, example:
    protected $mediaSingleCollections = ['image']; 
    protected $mediaMultipleCollections = ['images', 'files'];

    /**
     * Optional method.
     */
    public function customMediaConversions(Media $media = null): void
    {
        $this->addMediaCollection('main')
            ->singleFile();

        $this->addMediaConversion('table')
            ->format('jpg')->quality(93)
            ->fit('crop', 360, 257);
    }
}
```

In controller `app/Http/Controllers/Article.php`:

```php
<?php 

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller 
{
    public function store(PostRequest $request)
    {
        $article = Article::create($request->validated());
    
        $article->mediaManage($request);
        // Or usage Facade:
        \MediaLibrary::manage($article, $request);
        
        //...
    }
    
    public function show($id)
    {
        $article = \App\Model\Article::findOrFail($id);
        
        $url = $article->getFirstMediaUrl('image');
        $image = $article->getFirstMedia('image');
        $alt = $image->getCustomProperty('alt');
        $images = $article->getMedia('images'); // collection
        // ...
        // Also available all methods spatie/laravel-medialibrary!
    }   
}
```

## Examples HTML form

For upload files from collection name `images` you need send form with next data:
```html
<input type="file" name="image">
<input type="hidden" name="image_custom[15][alt]" value="This Alt image text">

<input type="file" multiple="" name="images[]">
```

For set sort order file from collection name `images` and `id = 13, 15` you need send form with next data (`weight = 21, 22`):
```html
<input type="hidden" name="images_weight[15]" value="21">
<input type="hidden" name="images_weight[13]" value="22">
<input type="hidden" name="images_custom[13][alt]" value="Some text">
```

For deleted file from collection name `images` and `id = 13, 15` you need send form with next data:
```html
<input type="hidden" name="images_deleted[]" value="13">
<input type="hidden" name="images_deleted[]" value="15">
```

## Links

[spatie/laravel-medialibrary](https://github.com/spatie/laravel-medialibrary)
