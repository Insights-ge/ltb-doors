<?php

namespace App\Filament\Resources\DoorModels\Pages;

use App\Filament\Resources\DoorModels\DoorModelResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateDoorModel extends CreateRecord
{
    protected static string $resource = DoorModelResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('panel.catalog.door_models.layout.create_door_model');
    }
}
