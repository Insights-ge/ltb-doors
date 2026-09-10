<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\FilamentIconServiceProvider;
use Spatie\TranslationLoader\TranslationServiceProvider;

return [
    AppServiceProvider::class,
    FilamentIconServiceProvider::class,
    AdminPanelProvider::class,
    TranslationServiceProvider::class,
];
