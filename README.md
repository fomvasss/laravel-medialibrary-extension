# Laravel Medialibrary Extension

[![License](https://img.shields.io/packagist/l/fomvasss/laravel-medialibrary-extension.svg?style=for-the-badge)](https://packagist.org/packages/fomvasss/laravel-medialibrary-extension)
[![Build Status](https://img.shields.io/github/stars/fomvasss/laravel-medialibrary-extension.svg?style=for-the-badge)](https://github.com/fomvasss/laravel-medialibrary-extension)
[![Latest Stable Version](https://img.shields.io/packagist/v/fomvasss/laravel-medialibrary-extension.svg?style=for-the-badge)](https://packagist.org/packages/fomvasss/laravel-medialibrary-extension)
[![Total Downloads](https://img.shields.io/packagist/dt/fomvasss/laravel-medialibrary-extension.svg?style=for-the-badge)](https://packagist.org/packages/fomvasss/laravel-medialibrary-extension)
[![Quality Score](https://img.shields.io/scrutinizer/g/fomvasss/laravel-medialibrary-extension.svg?style=for-the-badge)](https://scrutinizer-ci.com/g/fomvasss/laravel-medialibrary-extension)

**English** | [Українська](README.uk.md)

Form and API layer on top of [spatie/laravel-medialibrary](https://github.com/spatie/laravel-medialibrary): declare the media collections of a model once and save everything a form or an API client sends — new files, deletions, sort order, `alt`/`title` and other custom properties, the main file — with one call.

- `$model->mediaManage($request)` — save media from an HTML form (admin panel)
- `$model->mediaManageRefresh($data)` — save media from API data: files are uploaded beforehand as temporary media and then attached by `id`
- `strict_refresh` — safe attach / delete for data that comes from a client
- default image conversions for all models from config, main / active flags, owner (`user_id`), file name generators, helpers for reading

Admin UI for it: the `Lte3::mediaFile()` / `Lte3::mediaImage()` fields of [fomvasss/laravel-lte3](https://github.com/fomvasss/laravel-lte3).

![lte3 media field](docs/images/lte3-media-field.png)

## Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Model](#model)
- [Saving from a form](#saving-from-a-form)
  - [Simple format](#simple-format)
  - [Expand format](#expand-format)
  - [Single and multiple collections](#single-and-multiple-collections)
  - [Validation](#validation)
- [Admin UI (laravel-lte3)](#admin-ui-laravel-lte3)
- [Saving from an API: temporary uploads](#saving-from-an-api-temporary-uploads)
  - [Strict mode](#strict-mode)
  - [Clearing temporary uploads](#clearing-temporary-uploads)
- [Reading media](#reading-media)
- [Conversions](#conversions)
- [File names](#file-names)
- [Configuration](#configuration)
- [Upgrading](#upgrading)
- [Support](#support)

## Requirements

- PHP 8.1+
- Laravel 10 – 13
- spatie/laravel-medialibrary 11

## Installation

```bash
composer require fomvasss/laravel-medialibrary-extension
```

Publish spatie/laravel-medialibrary migrations and config:

```bash
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-config"
```

Publish the package config and migrations:

```bash
php artisan vendor:publish --provider="Fomvasss\MediaLibraryExtension\ServiceProvider"
```

The package migrations add columns `is_main`, `is_active`, `user_id` to the `media` table and create the `media_temporaries` table (owner model of temporary uploads). If your users have uuid keys, change `user_id` in the published migration to `uuid()` before migrating.

```bash
php artisan migrate
```

## Model

Implement `Fomvasss\MediaLibraryExtension\HasMedia\HasMedia` and use the `InteractsWithMedia` trait (instead of the spatie ones). Declare the collections: the collection name is also the form field name.

```php
<?php

namespace App\Models;

use Fomvasss\MediaLibraryExtension\HasMedia\HasMedia;
use Fomvasss\MediaLibraryExtension\HasMedia\InteractsWithMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Article extends Model implements HasMedia
{
    use InteractsWithMedia;

    // one file per collection: a new upload replaces the old one
    protected $mediaSingleCollections = ['image'];

    // many files per collection
    protected $mediaMultipleCollections = ['images', 'files'];

    // optional: your conversions in addition to `default_conversions` from config
    public function customMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('preview')
            ->performOnCollections('image', 'images')
            ->fit(Fit::Contain, 600, 600)
            ->format('webp');
    }
}
```

All spatie/laravel-medialibrary methods (`addMedia()`, `getMedia()`, `getFirstMediaUrl()`, …) keep working.

## Saving from a form

```php
public function update(ArticleRequest $request, Article $article)
{
    $article->update($request->validated());

    $article->mediaManage($request);
    // or via the facade: \MediaManager::manage($article, $request);

    return back();
}
```

`mediaManage()` goes through every collection of the model and reads its fields from the request. Two formats are supported; each collection can use either of them.

### Simple format

The file input has the collection name; service fields have suffixes (`field_suffixes` in config).

```html
<!-- multiple collection `images` -->
<input type="file" name="images[]" multiple>

<!-- sort order of saved files: images_weight[<media id>] -->
<input type="hidden" name="images_weight[13]" value="0">
<input type="hidden" name="images_weight[15]" value="1">

<!-- delete saved files -->
<input type="hidden" name="images_deleted[]" value="15">

<!-- custom properties of saved files: images_custom[<media id>][<property>] -->
<input type="hidden" name="images_custom[13][alt]" value="Sunset over the sea">

<!-- single collection `image`: a new file replaces the current one -->
<input type="file" name="image">
<input type="hidden" name="image_custom[new][alt]" value="Cover"> <!-- properties of the new file -->
<input type="hidden" name="image_deleted" value="7">               <!-- delete the current one -->
```

Besides, `media_deleted[]` (`deleted_request_input` in config) deletes media of the model by id from any collection.

Custom properties of a new file in a multiple collection can't be sent in this format (it has no id yet) — use the expand format.

### Expand format

A row per file — saved (`id`) or new (`file`) — with all its data. Lets you set properties and order of new files and the main file in one request.

```html
<!-- saved file -->
<input type="hidden" name="files[0][id]" value="13">
<input type="hidden" name="files[0][weight]" value="1">
<input type="hidden" name="files[0][delete]" value="0">
<input type="hidden" name="files[0][title]" value="Price list 2026">

<!-- new file -->
<input type="file" name="files[1][file]">
<input type="hidden" name="files[1][weight]" value="0">
<input type="hidden" name="files[1][is_main]" value="1">
<input type="hidden" name="files[1][alt]" value="Scheme">

<!-- single collection: one row without an index -->
<input type="file" name="image[file]">
<input type="hidden" name="image[alt]" value="Cover">
```

Row keys:

| Key | |
|---|---|
| `id` | a saved media of the model |
| `file` | a new file (`UploadedFile`); in a single collection it replaces the current file |
| `url` / `path` / `base64` | a new file from a URL, a local path or a base64 string |
| `weight` | order (`order_column`) |
| `delete` | `1` — delete the media of `id` |
| `is_main` | `1` — the main file of the collection (only one: the others are reset) |
| `is_active` | `0` — hide without deleting (default `1`) |
| `alt`, `title`, … | custom properties — only those listed in `expand.allowed_custom_properties` |

> A collection named like a Symfony `Request` property (`files`, `query`, `request`, `headers`, …) works in the expand format since 6.4.1.

### Single and multiple collections

- **multiple** (`$mediaMultipleCollections`) — files are added; they are deleted only explicitly (`*_deleted` / `delete`).
- **single** (`$mediaSingleCollections`) — one file: a new upload deletes the previous one.

### Validation

Simple format:

```php
'images' => 'nullable|array',
'images.*' => 'file|image|max:10240',
'image' => 'nullable|image|max:10240',
```

Expand format: an element of `files.*` is an array, the file is in `files.*.file`. A rule that accepts both formats:

```php
use Illuminate\Validation\Rule;

'files' => 'nullable|array',
'files.*' => Rule::forEach(fn ($value) => is_array($value) ? ['array'] : ['file', 'max:51200', 'mimes:jpg,png,pdf']),
'files.*.file' => 'nullable|file|max:51200|mimes:jpg,png,pdf',
```

## Admin UI (laravel-lte3)

[fomvasss/laravel-lte3](https://github.com/fomvasss/laravel-lte3) has ready fields that send exactly these formats: drop zone, previews before saving, image thumbnails, delete with restore, drag-and-drop sorting, a modal for custom properties.

```blade
{!! Lte3::formOpen(['action' => route('admin.articles.update', $article), 'model' => $article, 'files' => true]) !!}

{{-- simple format (default) --}}
{!! Lte3::mediaImage('images', $article, ['multiple' => true, 'custom_properties' => ['alt', 'title']]) !!}
{!! Lte3::mediaImage('image', $article, ['custom_properties' => ['alt']]) !!}

{{-- expand format: properties of new files, main file --}}
{!! Lte3::mediaFile('files', $article, [
    'multiple' => true,
    'format' => 'expand',
    'main' => true,
    'accept' => 'image/*,.pdf,.doc,.docx',
    'custom_properties' => ['title', 'alt'],
]) !!}

{!! Lte3::formClose() !!}
```

![properties modal](docs/images/lte3-media-properties.png)

Details: [lte3 docs — mediaFile field](https://github.com/fomvasss/laravel-lte3-docs/blob/master/fields/mediaFile.md).

## Saving from an API: temporary uploads

For SPA / mobile clients the file is uploaded first, separately from the entity form:

1. The client sends the file to your upload endpoint; the file is stored as a media of the temporary model (`MediaTemporary`), and the response returns its `id`.
2. The client sends the entity with this `id`; `mediaManageRefresh()` moves the media to the model.

Upload endpoint:

```php
use Fomvasss\MediaLibraryExtension\Actions\UploadMediaTemporaryFile;

public function upload(Request $request)
{
    $request->validate(['file' => 'required|file|max:51200|mimes:jpg,png,webp,pdf']);

    $media = (new UploadMediaTemporaryFile)->handle([
        'file' => $request->file('file'),
        'user_id' => $request->user()?->id, // owner — checked in strict mode
    ]);

    return response()->json(['id' => $media->id, 'url' => $media->getUrl()]);
}
```

Saving the entity:

```php
public function update(Request $request, Article $article)
{
    $article->update($request->only('title', 'body'));

    $article->mediaManageRefresh($request->only('image', 'images', 'media_deleted'));
}
```

```json
{
    "image": {"id": "<temporary media id>", "alt": "Cover"},
    "images": [
        {"id": "<temporary media id>", "weight": 0},
        {"id": "<saved media id>", "weight": 1, "title": "Updated title"},
        {"id": "<saved media id>", "delete": true}
    ],
    "media_deleted": ["<media id>"]
}
```

Row keys are the same as in the [expand format](#expand-format) (`id`, `weight`, `delete`, `is_main`, `is_active`, custom properties); `media_deleted` (`deleted_request_input` in config) — ids to delete from any collection.

### Strict mode

By default `mediaManageRefresh()` trusts every `id` in the data: any media can be attached to the model or deleted. That is fine for a trusted admin panel, but not for data from a client. Enable it in `config/media-library-extension.php`:

```php
'strict_refresh' => true,
```

With it:
- attach by `id` works only for media of this model or an own temporary upload:
  - uploaded with `user_id` — only by the same user (the `$user` argument of `mediaManageRefresh()` or the authenticated one)
  - uploaded without `user_id` in a request that came with the session cookie (public web form) — only from the same session. A session started for a request without cookies (e.g. Sanctum stateful API called with a Bearer token) is ignored: it does not survive to the next request
  - uploaded without both (stateless API) — by anyone who knows the `id`, so keep media keys unguessable (uuid)
- `delete` and `media_deleted` remove only media of this model
- `user_id` from the data is ignored

Other ids are silently skipped.

### Clearing temporary uploads

Unattached temporary uploads older than `temporary.cleartime` minutes (a day by default) are deleted by:

```php
\Fomvasss\MediaLibraryExtension\Actions\ClearMediaTemporary::doHandle();
```

Schedule it — in `routes/console.php` (Laravel 11+) or `app/Console/Kernel.php` (Laravel 10):

```php
Schedule::call(fn () => \Fomvasss\MediaLibraryExtension\Actions\ClearMediaTemporary::doHandle())->daily();
```

## Reading media

```php
$article->getFirstMediaUrl('image');                                   // spatie
$article->getMyFirstMediaUrl('image', 'preview', '/img/no-image.png'); // with a default URL
$article->getMyFirstMediaFullUrl('image');                             // absolute URL

$article->getMainMedia('images');                                      // is_main + is_active, otherwise the first
$article->getMainMediaUrl('images', 'preview', '/img/no-image.png');

$media = $article->getFirstMedia('image');
$media->getCustomProperty('alt');
$media->is_main;   // bool
$media->user_id;   // owner, if `use_auth_user` is on or `user_id` was passed
```

## Conversions

Conversions from `default_conversions` in config are registered for **all** models with `InteractsWithMedia`; a conversion applies to the collections whose names match `regex_perform_to_collections`. The default is the `thumb` 100×100 webp for collections like `image`, `photo`, `gallery`, `logo`, `avatar`.

```php
'default_conversions' => [
    'thumb' => [
        'quantity' => 75,
        'fit' => \Spatie\Image\Enums\Fit::Crop,
        'width' => 100,
        'height' => 100,
        'format' => 'webp',
        'regex_perform_to_collections' => '/img|image|photo|gallery|scr|logo|avatar/i',
        'non_queued' => true,
    ],
],
```

Model-specific conversions go to `customMediaConversions()` (see [Model](#model)). The default quality is `default_img_quantity`, per model — `setMediaQuality()`.

`Fit` modes on the same image resized to 320×480 (files in `docs/medialibrary`):

| Original | `Contain` | `Max` | `Crop` | `Fill` | `FillMax` | `Stretch` |
|---|---|---|---|---|---|---|
| ![](docs/medialibrary/original.png) | ![](docs/medialibrary/contain.webp) | ![](docs/medialibrary/max.webp) | ![](docs/medialibrary/crop.webp) | ![](docs/medialibrary/fit_fil.webp) | ![](docs/medialibrary/fil_max.webp) | ![](docs/medialibrary/stretch.webp) |

## File names

`filename_generator` makes the name of a stored file:

- `DefaultFileNameGenerator` (default) — slug of the original name: `Price List (2026).PDF` → `price-list-2026.PDF`
- `RandomFileNameGenerator` — 32 random characters, the extension is kept

Your own — a class with `public static function get(string $originalName): string` (`FileNameGeneratorInterface`).

## Configuration

`config/media-library-extension.php`:

| Key | Default | |
|---|---|---|
| `filename_generator` | `DefaultFileNameGenerator` | name of a stored file |
| `default_img_quantity` | `85` | default quality of conversions |
| `default_conversions` | `thumb` | conversions for all models |
| `field_suffixes` | `_weight`, `_deleted`, `_custom` | suffixes of service fields in the simple format |
| `deleted_request_input` | `media_deleted` | field with ids to delete from any collection |
| `use_auth_user` | `false` | write the authenticated user to `media.user_id` |
| `strict_refresh` | `false` | [strict mode](#strict-mode) of `mediaManageRefresh()` |
| `expand.allowed_custom_properties` | `alt`, `title` | custom properties written from the expand format and API data |
| `temporary.model` | `MediaTemporary` | owner model of temporary uploads |
| `temporary.cleartime` | `1440` | minutes after which an unattached temporary upload is deleted |

## Upgrading

See [UPGRADING.md](UPGRADING.md) and [CHANGELOG.md](CHANGELOG.md).

## Links

- [spatie/laravel-medialibrary](https://github.com/spatie/laravel-medialibrary)
- [fomvasss/laravel-lte3](https://github.com/fomvasss/laravel-lte3) — admin fields for this package

## Support

If this package is useful to you, consider supporting its development:

[![Monobank](https://img.shields.io/badge/Donate-Monobank-black)](https://send.monobank.ua/jar/5xsqtHvVrY)
[![Ko-Fi](https://img.shields.io/badge/Donate-Ko--fi-FF5E5B?logo=ko-fi&logoColor=white)](https://ko-fi.com/fomvasss)
[![USDT TRC20](https://img.shields.io/badge/Donate-USDT%20TRC20-26A17B?logo=tether&logoColor=white)](https://link.trustwallet.com/send?coin=195&address=THLgp6DxiAtbNHvgnKV56vk1L38UuUagKf&token_id=TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t)

> USDT TRC20 address: `THLgp6DxiAtbNHvgnKV56vk1L38UuUagKf`
