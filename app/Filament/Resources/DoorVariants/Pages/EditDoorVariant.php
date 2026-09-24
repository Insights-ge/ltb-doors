<?php

namespace App\Filament\Resources\DoorVariants\Pages;

use App\Filament\Resources\DoorVariants\DoorVariantResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDoorVariant extends EditRecord
{
    protected static string $resource = DoorVariantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
