<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Resources\Users\UserResource;
use App\Http\Middleware\SyncLocaleFromSession;
use App\Settings\GeneralSettings;
use App\Support\AdminTheme;
use DiogoGPinto\AuthUIEnhancer\AuthUIEnhancerPlugin;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;
use Spatie\LaravelSettings\Exceptions\MissingSettings;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->profile()
            ->spa(hasPrefetching: true)
            ->emailVerification()
            ->databaseNotifications()

            ->font('Albert Sans')
            ->serifFont('Lora')

            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                ...AdminTheme::COLORS,
                'pink' => Color::hex('#ff006e'),
                'blue' => Color::hex('#00b4d8'),
                'green' => Color::hex('#38b000'),
                'yellow' => Color::hex('#ffc300'),
                'red' => Color::hex('#ff5e5b'),
                'purple' => Color::hex('#9d4edd'),
            ])
            // ->sidebarCollapsibleOnDesktop()
            ->topbar(false)
            ->maxContentWidth(Width::Full)
            ->unsavedChangesAlerts()
            ->sidebarWidth('20rem')
            ->brandLogo(fn () => $this->brandLogoUrl())
            ->darkModeBrandLogo(fn () => $this->darkModeBrandLogoUrl())
            ->brandLogoHeight(fn () => auth()->check() ? '4rem' : '2.5rem')
            ->favicon(fn () => $this->faviconUrl())
            ->defaultThemeMode(ThemeMode::System)
            ->homeUrl(fn () => UserResource::getUrl())
            ->renderHook(
                PanelsRenderHook::SIDEBAR_LOGO_AFTER,
                fn () => view('filament.admin.components.brand-text'),
            )

            ->plugins([
                BreezyCore::make()
                    ->enableTwoFactorAuthentication()
                    ->myProfile(
                        hasAvatars: true,
                    )
                    ->enableBrowserSessions(condition: true),
                AuthUIEnhancerPlugin::make()
                    ->showEmptyPanelOnMobile(false)
                    ->formPanelPosition('right')
                    ->formPanelWidth('40%')
                    ->emptyPanelBackgroundImageUrl($this->authPageBgImageUrl()),
                FilamentApexChartsPlugin::make(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                // AccountWidget::class,
                // FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                SyncLocaleFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    private function settings(): GeneralSettings
    {
        return app(GeneralSettings::class);
    }

    private function brandLogoUrl(): string
    {
        $path = $this->settingValue('filament_brand_logo');

        return $path ? Storage::disk('public')->url($path) : asset('images/logo-dark.avif');
    }

    private function darkModeBrandLogoUrl(): string
    {
        $path = $this->settingValue('filament_dark_mode_brand_logo');

        return $path ? Storage::disk('public')->url($path) : asset('images/logo-light.avif');
    }

    private function faviconUrl(): string
    {
        $path = $this->settingValue('filament_favicon');

        return $path ? Storage::disk('public')->url($path) : asset('favicon.ico');
    }

    private function authPageBgImageUrl(): string
    {
        $path = $this->settingValue('filament_auth_page_bg_image');

        return $path ? Storage::disk('public')->url($path) : asset('images/cover.avif');
    }

    private function settingValue(string $property): ?string
    {
        try {
            $value = $this->settings()->{$property};

            return is_string($value) ? $value : null;
        } catch (MissingSettings|QueryException) {
            return null;
        }
    }
}
