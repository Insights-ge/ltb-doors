<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HiddenFromNavigation;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Process;

class ManageCacheTools extends Page
{
    use HasPageShield;
    use HiddenFromNavigation;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCommandLine;

    protected static ?int $navigationSort = 30;

    protected static bool $shouldRegisterNavigation = false;

    public ?string $commandOutput = null;

    public ?string $lastCommand = null;

    public bool $lastCommandFailed = false;

    protected string $view = 'filament.admin.pages.manage-cache-tools';

    public static function getNavigationLabel(): string
    {
        return __('panel.cache_tools.navigation_label');
    }

    public static function getLabel(): ?string
    {
        return __('panel.cache_tools.navigation_label');
    }

    public function getTitle(): string
    {
        return __('panel.cache_tools.title');
    }

    public function runCommand(string $command): void
    {
        $this->lastCommand = 'php artisan ' . $command;
        $this->lastCommandFailed = false;

        $result = Process::path(base_path())->run(
            PHP_BINARY . ' artisan ' . $command . ' --no-ansi',
        );

        $output = trim($result->output() . $result->errorOutput());
        $this->commandOutput = $output ?: '(no output)';
        $this->lastCommandFailed = $result->failed();

        if ($result->successful()) {
            Notification::make()
                ->success()
                ->title(__('panel.cache_tools.notifications.success_title'))
                ->body(__('panel.cache_tools.notifications.success_body', ['command' => $command]))
                ->send();
        } else {
            Notification::make()
                ->danger()
                ->title(__('panel.cache_tools.notifications.error_title'))
                ->body($output)
                ->send();
        }
    }
}
