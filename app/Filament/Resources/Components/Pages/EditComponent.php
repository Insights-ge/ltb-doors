<?php

namespace App\Filament\Resources\Components\Pages;

use App\Filament\Resources\Components\ComponentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditComponent extends EditRecord
{
    protected static string $resource = ComponentResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('panel.catalog.components.layout.edit_component');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(ComponentResource::preventDeletingComponentInUse(...)),
        ];
    }
}
