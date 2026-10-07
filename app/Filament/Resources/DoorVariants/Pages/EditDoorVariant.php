<?php

namespace App\Filament\Resources\DoorVariants\Pages;

use App\Filament\Resources\DoorVariants\DoorVariantResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditDoorVariant extends EditRecord
{
    protected static string $resource = DoorVariantResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('door_variants.layout.edit_door_variant');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
