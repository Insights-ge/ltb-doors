<?php

namespace App\Actions;

use App\Jobs\ImportDoorVariantsJob;
use App\Models\DoorVariant;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class DispatchDoorVariantImportAction
{
    /**
     * Queues the import of a stored upload. The job deletes the upload and notifies the user when it's done.
     *
     * @param  string  $path  The upload's path on the job's disk.
     * @param  string  $locale  The language of the notification the user will get.
     */
    public function execute(User $user, string $path, string $locale): void
    {
        Gate::forUser($user)->authorize('import', DoorVariant::class);

        if (! Storage::disk(ImportDoorVariantsJob::disk())->exists($path)) {
            throw new InvalidArgumentException("The uploaded file [{$path}] does not exist.");
        }

        ImportDoorVariantsJob::dispatch($path, $user, $locale);
    }
}
