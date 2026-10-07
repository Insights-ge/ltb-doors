<?php

namespace App\Filament\Resources\Components\Pages;

use App\Filament\Resources\Components\ComponentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateComponent extends CreateRecord
{
    protected static string $resource = ComponentResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('components.layout.create_component');
    }
}
