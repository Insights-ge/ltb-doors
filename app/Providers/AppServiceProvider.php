<?php

namespace App\Providers;

use App\Enums\Locale;
use App\Filament\Auth\LoginResponse;
use App\Policies\LanguageLinePolicy;
use App\Support\Locales;
use BezhanSalleh\LanguageSwitch\Enums\ItemStyle;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\TranslationLoader\LanguageLine;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LoginResponseContract::class, LoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(LanguageLine::class, LanguageLinePolicy::class);

        Model::shouldBeStrict(! app()->isProduction());

        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $enabled = Locales::enabled();

            $locales = array_map(fn (Locale $l) => $l->value, $enabled);
            $labels = array_combine($locales, array_map(fn (Locale $l) => $l->native(), $enabled));
            $flags = array_combine($locales, array_map(fn (Locale $l) => $l->flag(), $enabled));

            $switch
                ->locales($locales)
                ->labels($labels)
                ->flags($flags)
                ->circular()
                ->itemStyle(ItemStyle::FlagWithLabel)
                ->visible(outsidePanels: true)
                ->renderHook('panels::user-menu.before');
        });

        // Very USEFULFRIENDLY
        ViewAction::configureUsing(fn (ViewAction $action) => $action->iconButton());
        EditAction::configureUsing(fn (EditAction $action) => $action->iconButton());
        DeleteAction::configureUsing(fn (DeleteAction $action) => $action->iconButton());
        TextColumn::configureUsing(fn (TextColumn $column) => $column->toggleable());
        ImageColumn::configureUsing(fn (ImageColumn $column) => $column->toggleable());
        IconColumn::configureUsing(fn (IconColumn $column) => $column->toggleable());
    }
}
