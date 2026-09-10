<?php

namespace App\Filament\Resources\TranslationLines\Pages;

use App\Enums\Locale;
use App\Filament\Concerns\HiddenFromNavigation;
use App\Filament\Resources\TranslationLines\TranslationLineResource;
use App\Support\Locales;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Lang;
use Spatie\TranslationLoader\LanguageLine;

class ListTranslationLines extends ListRecords
{
    use HiddenFromNavigation;

    protected static string $resource = TranslationLineResource::class;

    protected static bool $shouldRegisterNavigation = false;

    public function getTitle(): string
    {
        return __('panel.translation_lines.layout.translation_lines');
    }

    public function getTabs(): array
    {
        $groups = LanguageLine::query()
            ->select('group')
            ->distinct()
            ->orderBy('group')
            ->pluck('group');

        if ($groups->isEmpty()) {
            return [];
        }

        $tabs = [
            'all' => Tab::make(__('panel.translation_lines.tabs.all')),
        ];

        foreach ($groups as $group) {
            if (! is_string($group)) {
                continue;
            }

            $tabs[$group] = Tab::make(
                Lang::has("panel.translation_lines.tabs.{$group}")
                    ? __("panel.translation_lines.tabs.{$group}")
                    : ucfirst($group)
            )->modifyQueryUsing(fn (Builder $query) => $query->where('group', $group));
        }

        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync_from_files')
                ->label(__('panel.translation_lines.actions.sync_from_files'))
                ->icon(Heroicon::ArrowDownTray)
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading(__('panel.translation_lines.actions.sync_modal_heading'))
                ->modalDescription(__('panel.translation_lines.actions.sync_modal_description'))
                ->action(fn () => $this->syncFromFiles()),
        ];
    }

    private function syncFromFiles(): void
    {
        $locales = array_map(
            fn (Locale $locale): string => $locale->value,
            Locales::enabled(),
        );
        $synced = 0;

        foreach ($locales as $locale) {
            $langPath = lang_path($locale);

            if (! is_dir($langPath)) {
                continue;
            }

            foreach (glob($langPath . '/*.php') ?: [] as $file) {
                $group = basename($file, '.php');
                $translations = require $file;

                if (! is_array($translations)) {
                    continue;
                }

                foreach ($this->flattenTranslations($translations) as $key => $value) {
                    $line = LanguageLine::firstOrNew(['group' => $group, 'key' => $key]);
                    $text = $line->text ?? [];
                    $text[$locale] = $value;
                    $line->text = $text;
                    $line->save();
                    $synced++;
                }
            }
        }

        Notification::make()
            ->title(__('panel.translation_lines.actions.sync_success', ['count' => $synced]))
            ->success()
            ->send();
    }

    /**
     * Flatten a nested translation array into dot-notated key => value pairs.
     *
     * @param  array<int|string, mixed>  $array
     * @return array<string, string>
     */
    private function flattenTranslations(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $fullKey = $prefix !== '' ? "{$prefix}.{$key}" : (string) $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenTranslations($value, $fullKey));
            } else {
                $result[$fullKey] = is_scalar($value) ? (string) $value : '';
            }
        }

        return $result;
    }
}
