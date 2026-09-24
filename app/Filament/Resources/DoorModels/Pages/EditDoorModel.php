<?php

namespace App\Filament\Resources\DoorModels\Pages;

use App\Filament\Resources\DoorModels\DoorModelResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDoorModel extends EditRecord
{
    protected static string $resource = DoorModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
