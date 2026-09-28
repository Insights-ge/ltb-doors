<?php

namespace App\Filament\Resources\TranslationLines\Tables;

use App\Support\Locales;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\TranslationLoader\LanguageLine;

class TranslationLinesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('group')
                    ->label(__('panel.translation_lines.table.group'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('key')
                    ->label(__('panel.translation_lines.table.key'))
                    ->searchable()
                    ->sortable(),

                ...array_map(
                    fn (string $locale) => TextColumn::make("text.{$locale}")
                        ->label(strtoupper($locale))
                        ->limit(60)
                        ->searchable(),
                    self::supportedLocales()
                ),

                TextColumn::make('updated_at')
                    ->label(__('panel.translation_lines.table.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('missing_translations')
                    ->label(__('panel.translation_lines.table.missing_translations'))
                    ->query(function (Builder $query): Builder {
                        $locales = self::supportedLocales();

                        return $query->where(function (Builder $q) use ($locales): void {
                            foreach ($locales as $locale) {
                                $q->orWhereNull("text->{$locale}")
                                    ->orWhere("text->{$locale}", '');
                            }
                        });
                    }),
                SelectFilter::make('group')
                    ->label(__('panel.translation_lines.table.group'))
                    ->options(fn () => LanguageLine::query()
                        ->distinct()
                        ->orderBy('group')
                        ->pluck('group', 'group')
                        ->toArray())
                    ->searchable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->striped()
            ->defaultSort('group')
            ->emptyStateHeading(__('panel.translation_lines.table.empty_heading'))
            ->emptyStateDescription(__('panel.translation_lines.table.empty_description'));
    }

    /** @return list<string> */
    private static function supportedLocales(): array
    {
        return array_map(fn ($l) => $l->value, Locales::enabled());
    }
}
