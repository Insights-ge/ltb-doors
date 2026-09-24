<?php

namespace App\Filament\Resources\DoorVariants\Pages;

use App\Filament\Resources\DoorVariants\DoorVariantResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateDoorVariant extends CreateRecord
{
    protected static string $resource = DoorVariantResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('panel.catalog.door_variants.layout.create_door_variant');
    }
}
