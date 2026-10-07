<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\EditRole as ShieldEditRole;
use Illuminate\Contracts\Support\Htmlable;
use Override;

class EditRole extends ShieldEditRole
{
    protected static string $resource = RoleResource::class;

    #[Override]
    public function getTitle(): string|Htmlable
    {
        return __('roles.layout.edit_role');
    }
}
