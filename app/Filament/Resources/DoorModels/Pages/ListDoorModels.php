<?php

namespace App\Filament\Resources\DoorModels\Pages;

use App\Filament\Resources\DoorModels\DoorModelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDoorModels extends ListRecords
{
    protected static string $resource = DoorModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
