<?php

namespace App\Actions;

use App\Models\DoorVariant;
use App\Models\User;
use App\Services\DoorVariantImportSampleWriter;
use Illuminate\Support\Facades\Gate;

class CreateDoorVariantImportSampleAction
{
    public function __construct(private readonly DoorVariantImportSampleWriter $sampleWriter) {}

    /**
     * @param  string  $path  A file path, or "php://output" to stream it.
     */
    public function execute(User $user, string $path): void
    {
        Gate::forUser($user)->authorize('import', DoorVariant::class);

        $this->sampleWriter->write($path);
    }
}
