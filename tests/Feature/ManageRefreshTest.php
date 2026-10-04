<?php

declare(strict_types=1);

namespace Fomvasss\MediaLibraryExtension\Tests\Feature;

use Fomvasss\MediaLibraryExtension\Actions\UploadMediaTemporaryFile;
use Fomvasss\MediaLibraryExtension\Tests\Article;
use Fomvasss\MediaLibraryExtension\Tests\Page;
use Fomvasss\MediaLibraryExtension\Tests\TestCase;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ManageRefreshTest extends TestCase
{
    public function testDefaultModeKeepsLegacyBehaviour()
    {
        $article = Article::create();
        $foreign = $this->mediaOf(Page::create());
        $foreignToDelete = $this->mediaOf(Page::create());

        $article->mediaManageRefresh([
            'files' => [['id' => $foreign->id]],
            'media_deleted' => [$foreignToDelete->id],
        ]);

        $this->assertTrue($this->belongsTo($foreign, $article));
        $this->assertFalse(Media::whereKey($foreignToDelete->id)->exists());
    }

    public function testStrictAttachesTemporaryUpload()
    {
        $this->strict();
        $article = Article::create();
        $temporary = $this->temporaryUpload();

        $article->mediaManageRefresh(['files' => [['id' => $temporary->id]]]);

        $this->assertTrue($this->belongsTo($temporary, $article));
        $this->assertSame('files', $temporary->refresh()->collection_name);
    }

    public function testStrictSkipsForeignMedia()
    {
        $this->strict();
        $owner = Page::create();
        $article = Article::create();
        $sibling = Article::create();
        $foreign = $this->mediaOf($owner);
        $foreignCover = $this->mediaOf($owner, 'cover');
        $siblingMedia = $this->mediaOf($sibling);

        $article->mediaManageRefresh([
            'files' => [['id' => $foreign->id], ['id' => $siblingMedia->id, 'title' => 'Hijacked']],
            'cover' => ['id' => $foreignCover->id],
        ]);

        $this->assertTrue($this->belongsTo($foreign, $owner));
        $this->assertTrue($this->belongsTo($foreignCover, $owner));
        // та сама модель за типом, але інший запис — теж чуже
        $this->assertTrue($this->belongsTo($siblingMedia, $sibling));
        $this->assertNull($siblingMedia->getCustomProperty('title'));
    }

    public function testStrictDeletesOnlyOwnMedia()
    {
        $this->strict();
        $article = Article::create();
        $own = $this->mediaOf($article);
        $ownDeleted = $this->mediaOf($article);
        $foreign = $this->mediaOf(Article::create());
        $foreignDeleted = $this->mediaOf(Article::create());

        $article->mediaManageRefresh([
            'files' => [
                ['id' => $own->id, 'delete' => 1],
                ['id' => $foreign->id, 'delete' => 1],
            ],
            'media_deleted' => [$ownDeleted->id, $foreignDeleted->id],
        ]);

        $this->assertFalse(Media::whereKey($own->id)->exists());
        $this->assertFalse(Media::whereKey($ownDeleted->id)->exists());
        $this->assertTrue(Media::whereKey($foreign->id)->exists());
        $this->assertTrue(Media::whereKey($foreignDeleted->id)->exists());
    }

    public function testStrictUpdatesOwnMedia()
    {
        $this->strict();
        $article = Article::create();
        $own = $this->mediaOf($article);

        $article->mediaManageRefresh(['files' => [['id' => $own->id, 'title' => 'New title', 'weight' => 5]]]);

        $own->refresh();
        $this->assertSame('New title', $own->getCustomProperty('title'));
        $this->assertSame(5, $own->order_column);
    }

    public function testStrictIgnoresUserIdFromData()
    {
        $this->strict();
        $article = Article::create();
        $temporary = $this->temporaryUpload(userId: 10);

        $article->mediaManageRefresh(['files' => [['id' => $temporary->id, 'user_id' => 99]]], $this->user(10));

        $this->assertTrue($this->belongsTo($temporary, $article));
        $this->assertSame(10, (int) $temporary->refresh()->user_id);
    }

    public function testStrictSkipsTemporaryUploadOfAnotherUser()
    {
        $this->strict();
        $article = Article::create();
        $temporary = $this->temporaryUpload(userId: 10);

        $article->mediaManageRefresh(['files' => [['id' => $temporary->id]]], $this->user(11));
        $article->mediaManageRefresh(['files' => [['id' => $temporary->id]]]);

        $this->assertFalse($this->belongsTo($temporary, $article));
    }

    public function testStrictAttachesAnonymousUploadOnlyFromItsSession()
    {
        $this->strict();
        $article = Article::create();
        $this->useSession('first');
        $temporary = $this->temporaryUpload();

        $this->useSession('second');
        $article->mediaManageRefresh(['files' => [['id' => $temporary->id]]]);
        $this->assertFalse($this->belongsTo($temporary, $article));

        $this->useSession('first');
        $article->mediaManageRefresh(['files' => [['id' => $temporary->id]]]);
        $this->assertTrue($this->belongsTo($temporary, $article));
        $this->assertNull($temporary->getCustomProperty(UploadMediaTemporaryFile::SESSION_PROPERTY));
    }

    public function testStrictIgnoresSessionWithoutCookie()
    {
        $this->strict();
        $article = Article::create();
        $this->useSession('first', withCookie: false);
        $temporary = $this->temporaryUpload();

        $this->useSession('second', withCookie: false);
        $article->mediaManageRefresh(['files' => [['id' => $temporary->id]]]);

        $this->assertTrue($this->belongsTo($temporary, $article));
    }

    public function testDefaultModeIgnoresUploadOwner()
    {
        $article = Article::create();
        $temporary = $this->temporaryUpload(userId: 10);

        $article->mediaManageRefresh(['files' => [['id' => $temporary->id]]], $this->user(11));

        $this->assertTrue($this->belongsTo($temporary, $article));
    }

    public function testStrictSkipsInvalidIds()
    {
        $this->strict();
        $article = Article::create();

        $article->mediaManageRefresh([
            'files' => [['id' => 'not-an-id'], ['id' => ['nested']], ['id' => 'x', 'delete' => 1]],
            'media_deleted' => ['not-an-id', ['nested']],
        ]);

        $this->assertSame(0, $article->media()->count());
    }

    private function strict(): void
    {
        config(['media-library-extension.strict_refresh' => true]);
    }

    private function mediaOf(Article $article, string $collection = 'files'): Media
    {
        return $article->addMedia(UploadedFile::fake()->create('doc.txt', 1))->toMediaCollection($collection);
    }

    private function temporaryUpload(?int $userId = null): Media
    {
        return (new UploadMediaTemporaryFile)->handle(array_filter([
            'file' => UploadedFile::fake()->create('upload.txt', 1),
            'user_id' => $userId,
        ]));
    }

    private function user(int $id): User
    {
        return (new User)->forceFill(['id' => $id]);
    }

    /**
     * $withCookie — запит прийшов з cookie сесії; без нього сесію стартували лише для цього запиту
     */
    private function useSession(string $id, bool $withCookie = true): void
    {
        $session = $this->app['session']->driver('array');
        $session->setId(str_pad($id, 40, 'x'));
        $request = $this->app['request'];
        $request->setLaravelSession($session);
        $withCookie
            ? $request->cookies->set($session->getName(), $session->getId())
            : $request->cookies->remove($session->getName());
    }

    private function belongsTo(Media $media, Article $article): bool
    {
        $media->refresh();

        return $media->model_type === $article->getMorphClass() && (int) $media->model_id === $article->id;
    }
}
