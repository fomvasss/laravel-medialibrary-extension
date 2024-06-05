<?php

namespace Fomvasss\MediaLibraryExtension\Actions;

use Illuminate\Support\Collection;

class ClearMediaTemporary
{
    public function handle()
    {
        $mediaTemporaryClass = config('media-library-extension.temporary.model');
        $mediaTemporaryInstance = new $mediaTemporaryClass();

        $mediaTemporaryCleartime = config('media-library-extension.temporary.cleartime', 60 * 24);
        
        $mediaTemporaryInstance::where('created_at', '<', now()->subMinutes($mediaTemporaryCleartime))
            ->chunk(100, function (Collection $items) {
                $items->each(fn ($item) => $item->delete());
            });
    }

    public static function doHandle()
    {
        return (new self())->handle();   
    }
}
