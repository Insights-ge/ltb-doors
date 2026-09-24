<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\ViewRole as ShieldViewRole;
use Illuminate\Contracts\Support\Htmlable;
use Override;

class ViewRole extends ShieldViewRole
{
    protected static string $resource = RoleResource::class;

    #[Override]
    public function getTitle(): string|Htmlable
    {
        return __('panel.roles.layout.view_role');
    }
}
