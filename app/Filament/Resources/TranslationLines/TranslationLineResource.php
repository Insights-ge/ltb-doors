<?php

namespace App\Filament\Resources\TranslationLines;

use App\Filament\Resources\TranslationLines\Pages\EditTranslationLine;
use App\Filament\Resources\TranslationLines\Pages\ListTranslationLines;
use App\Filament\Resources\TranslationLines\Schemas\TranslationLineForm;
use App\Filament\Resources\TranslationLines\Tables\TranslationLinesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Spatie\TranslationLoader\LanguageLine;

class TranslationLineResource extends Resource
{
    protected static ?string $model = LanguageLine::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Language;

    protected static ?int $navigationSort = 20;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return TranslationLineForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TranslationLinesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTranslationLines::route('/'),
            'edit' => EditTranslationLine::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('panel.translation_lines.layout.translation_lines');
    }

    public static function getLabel(): ?string
    {
        return __('panel.translation_lines.layout.translation_line');
    }
}
