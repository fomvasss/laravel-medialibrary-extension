<?php

declare(strict_types=1);

namespace Fomvasss\MediaLibraryExtension\Tests\Feature;

use Fomvasss\MediaLibraryExtension\Tests\Article;
use Fomvasss\MediaLibraryExtension\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * mediaManage() з даних форми. Колекція `files` збігається з властивістю Symfony Request::$files
 * (FileBag) — дані колекції мають читатися з полів запиту, а не з цієї властивості
 */
class ManageTest extends TestCase
{
    public function testExpandFormatForFilesCollection()
    {
        $article = Article::create();
        $keep = $this->mediaOf($article, 'keep.txt');
        $drop = $this->mediaOf($article, 'drop.txt');

        $article->mediaManage($this->request([
            'files' => [
                ['id' => $keep->id, 'weight' => 1, 'alt' => 'Kept'],
                ['id' => $drop->id, 'delete' => 1],
                ['file' => UploadedFile::fake()->create('new.txt', 1), 'weight' => 0, 'alt' => 'New'],
            ],
        ]));

        $media = $article->refresh()->getMedia('files');

        $this->assertSame(['new.txt', 'keep.txt'], $media->pluck('file_name')->all());
        $this->assertSame('New', $media[0]->getCustomProperty('alt'));
        $this->assertSame('Kept', $media[1]->getCustomProperty('alt'));
        $this->assertFalse(Media::whereKey($drop->id)->exists());
    }

    public function testExpandFormatForSingleCollection()
    {
        $article = Article::create();
        $old = $this->mediaOf($article, 'old.txt', 'cover');

        $article->mediaManage($this->request([
            'cover' => ['file' => UploadedFile::fake()->create('new.txt', 1), 'alt' => 'Cover'],
        ]));

        $cover = $article->refresh()->getMedia('cover');
        $this->assertSame(['new.txt'], $cover->pluck('file_name')->all());
        $this->assertSame('Cover', $cover[0]->getCustomProperty('alt'));
        $this->assertFalse(Media::whereKey($old->id)->exists());
    }

    public function testSimpleFormatForFilesCollection()
    {
        $article = Article::create();
        $keep = $this->mediaOf($article, 'keep.txt');
        $drop = $this->mediaOf($article, 'drop.txt');

        $article->mediaManage($this->request([
            'files' => [UploadedFile::fake()->create('new.txt', 1)],
            'files_weight' => [$keep->id => 5],
            'files_deleted' => [$drop->id],
            'files_custom' => [$keep->id => ['alt' => 'Kept']],
        ]));

        $media = $article->refresh()->getMedia('files');

        $this->assertSame(['new.txt', 'keep.txt'], $media->pluck('file_name')->all());
        $this->assertSame('Kept', $media[1]->getCustomProperty('alt'));
        $this->assertFalse(Media::whereKey($drop->id)->exists());
    }

    /**
     * Як справжній запит форми: файли — у $request->files, решта — у полях; вкладені масиви зливаються в all()
     */
    private function request(array $data): Request
    {
        return Request::create('/', 'POST', $this->withoutFiles($data), [], $this->onlyFiles($data));
    }

    private function onlyFiles(array $data): array
    {
        $files = [];
        foreach ($data as $key => $item) {
            if ($item instanceof UploadedFile) {
                $files[$key] = $item;
            } elseif (is_array($item) && ($nested = $this->onlyFiles($item))) {
                $files[$key] = $nested;
            }
        }

        return $files;
    }

    private function withoutFiles(array $data): array
    {
        foreach ($data as $key => $item) {
            if ($item instanceof UploadedFile) {
                unset($data[$key]);
            } elseif (is_array($item)) {
                $data[$key] = $this->withoutFiles($item);
            }
        }

        return $data;
    }

    private function mediaOf(Article $article, string $name, string $collection = 'files'): Media
    {
        return $article->addMedia(UploadedFile::fake()->create($name, 1))->toMediaCollection($collection);
    }
}
