<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HiddenFromNavigation;
use App\Settings\GeneralSettings;
use App\Support\Locales;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageGeneralSettings extends SettingsPage
{
    use HasPageShield;
    use HiddenFromNavigation;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 10;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $settings = GeneralSettings::class;

    public static function getNavigationLabel(): string
    {
        return __('panel.settings.general.layout.general_settings');
    }

    public static function getLabel(): ?string
    {
        return __('panel.settings.general.layout.general_settings');
    }

    public function getTitle(): string
    {
        return __('panel.settings.general.layout.general_settings');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // ── 1. General ────────────────────────────────────────────────
                Section::make(__('panel.settings.general.sections.general'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('site_name')
                            ->label(__('panel.settings.general.fields.site_name'))
                            ->required()
                            ->columnSpan(1),
                        TextInput::make('site_tagline')
                            ->label(__('panel.settings.general.fields.site_tagline'))
                            ->columnSpan(1),
                        TextInput::make('contact_email')
                            ->label(__('panel.settings.general.fields.contact_email'))
                            ->email()
                            ->required()
                            ->columnSpanFull(),
                    ]),

                // ── 2. Localization ───────────────────────────────────────────
                Section::make(__('panel.settings.general.sections.locales'))
                    ->columns(2)
                    ->schema([
                        Select::make('enabled_locales')
                            ->label(__('panel.settings.general.fields.enabled_locales'))
                            ->options(Locales::options())
                            ->multiple()
                            ->searchable()
                            ->required()
                            ->live()
                            ->noOptionsMessage(__('panel.settings.general.fields.enabled_locales_no_options'))
                            ->columnSpan(1),
                        Select::make('default_locale')
                            ->label(__('panel.settings.general.fields.default_locale'))
                            ->options(fn (Get $get): array => array_intersect_key(
                                Locales::options(),
                                array_flip(array_filter((array) ($get('enabled_locales') ?? []), 'is_string')),
                            ))
                            ->required()
                            ->noOptionsMessage(__('panel.settings.general.fields.default_locale_no_options'))
                            ->columnSpan(1),
                    ]),

                // ── 3. Branding ───────────────────────────────────────────────
                Section::make(__('panel.settings.general.sections.branding'))
                    ->columns(3)
                    ->schema([
                        FileUpload::make('branding_logo_main')
                            ->label(__('panel.settings.general.fields.branding_logo_main'))
                            ->image()
                            ->previewable()
                            ->moveFiles()
                            ->disk('public')
                            ->directory('images/logos')
                            ->visibility('public')
                            ->columnSpan(1),
                        FileUpload::make('branding_logo_header')
                            ->label(__('panel.settings.general.fields.branding_logo_header'))
                            ->image()
                            ->previewable()
                            ->moveFiles()
                            ->disk('public')
                            ->directory('images/logos')
                            ->visibility('public')
                            ->columnSpan(1),
                        FileUpload::make('branding_logo_footer')
                            ->label(__('panel.settings.general.fields.branding_logo_footer'))
                            ->image()
                            ->previewable()
                            ->moveFiles()
                            ->disk('public')
                            ->directory('images/logos')
                            ->visibility('public')
                            ->columnSpan(1),
                        FileUpload::make('filament_brand_logo')
                            ->label(__('panel.settings.general.fields.filament_brand_logo'))
                            ->image()
                            ->previewable()
                            ->moveFiles()
                            ->disk('public')
                            ->directory('images/branding')
                            ->visibility('public')
                            ->columnSpan(1),
                        FileUpload::make('filament_dark_mode_brand_logo')
                            ->label(__('panel.settings.general.fields.filament_dark_mode_brand_logo'))
                            ->image()
                            ->previewable()
                            ->moveFiles()
                            ->disk('public')
                            ->directory('images/branding')
                            ->visibility('public')
                            ->columnSpan(1),
                        FileUpload::make('filament_favicon')
                            ->label(__('panel.settings.general.fields.filament_favicon'))
                            ->acceptedFileTypes(['image/x-icon', 'image/vnd.microsoft.icon', 'image/png', 'image/svg+xml'])
                            ->disk('public')
                            ->previewable()
                            ->moveFiles()
                            ->directory('images/branding')
                            ->visibility('public')
                            ->columnSpan(1),
                        FileUpload::make('filament_auth_page_bg_image')
                            ->label(__('panel.settings.general.fields.filament_auth_page_bg_image'))
                            ->image()
                            ->previewable()
                            ->moveFiles()
                            ->disk('public')
                            ->directory('images/branding')
                            ->visibility('public')
                            ->columnSpan(1),
                    ]),

                // ── 4. SEO ────────────────────────────────────────────────────
                Section::make(__('panel.settings.general.sections.seo'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('gtm_id')
                            ->label(__('panel.settings.general.fields.gtm_id'))
                            ->columnSpan(1),
                        TextInput::make('social_facebook')
                            ->label(__('panel.settings.general.fields.social_facebook'))
                            ->url()
                            ->columnSpan(1),
                        TextInput::make('social_instagram')
                            ->label(__('panel.settings.general.fields.social_instagram'))
                            ->url()
                            ->columnSpan(1),
                        TextInput::make('social_whatsapp')
                            ->label(__('panel.settings.general.fields.social_whatsapp'))
                            ->tel()
                            ->prefix('+')
                            ->columnSpan(1),
                        TextInput::make('default_meta_description')
                            ->label(__('panel.settings.general.fields.default_meta_description'))
                            ->columnSpanFull(),
                        TextInput::make('default_meta_keywords')
                            ->label(__('panel.settings.general.fields.default_meta_keywords'))
                            ->columnSpanFull(),
                        FileUpload::make('og_image')
                            ->label(__('panel.settings.general.fields.og_image'))
                            ->image()
                            ->previewable()
                            ->moveFiles()
                            ->disk('public')
                            ->directory('images/og')
                            ->visibility('public')
                            ->columnSpanFull(),
                        TextInput::make('og_image_width')
                            ->label(__('panel.settings.general.fields.og_image_width'))
                            ->numeric()
                            ->integer()
                            ->required()
                            ->columnSpan(1),
                        TextInput::make('og_image_height')
                            ->label(__('panel.settings.general.fields.og_image_height'))
                            ->numeric()
                            ->integer()
                            ->required()
                            ->columnSpan(1),
                        Select::make('og_type')
                            ->label(__('panel.settings.general.fields.og_type'))
                            ->options([
                                'website' => 'Website',
                                'article' => 'Article',
                                'profile' => 'Profile',
                            ])
                            ->required()
                            ->noOptionsMessage(__('panel.settings.general.fields.og_type_no_options'))
                            ->columnSpan(1),
                    ]),
            ]);
    }
}
