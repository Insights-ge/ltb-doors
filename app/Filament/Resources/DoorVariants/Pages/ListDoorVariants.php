<?php

namespace App\Filament\Resources\DoorVariants\Pages;

use App\Filament\Resources\DoorVariants\DoorVariantResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDoorVariants extends ListRecords
{
    protected static string $resource = DoorVariantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
