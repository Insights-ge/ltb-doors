<?php

namespace App\Filament\Resources\DoorModels\Pages;

use App\Filament\Resources\DoorModels\DoorModelResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditDoorModel extends EditRecord
{
    protected static string $resource = DoorModelResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('panel.catalog.door_models.layout.edit_door_model');
    }
}
