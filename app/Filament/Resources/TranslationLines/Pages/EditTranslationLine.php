<?php

namespace App\Filament\Resources\TranslationLines\Pages;

use App\Filament\Concerns\HiddenFromNavigation;
use App\Filament\Resources\TranslationLines\TranslationLineResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditTranslationLine extends EditRecord
{
    use HiddenFromNavigation;

    protected static string $resource = TranslationLineResource::class;

    protected static bool $shouldRegisterNavigation = false;

    public function getTitle(): string|Htmlable
    {
        return __('panel.translation_lines.layout.edit_translation_line');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
