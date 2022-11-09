<?php

namespace Fomvasss\MediaLibraryExtension\Actions;

use Illuminate\Support\Collection;

class ClearMediaTemporaryFiles
{
    //public string $commandSignature = 'media:clear-temporary';

    public function handle()
    {
        $mediaTemporaryClass = config('media-library-extension.temporary_upload_model');
        $mediaTemporaryInstance = new $mediaTemporaryClass();
        
        $mediaTemporaryInstance::where('created_at', '<', now()->subDay())
            ->chunk(100, function (Collection $items) {
                $items->each(fn ($item) => $temp->delete());
            });
    }

}
