<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\CreateRole as ShieldCreateRole;
use Illuminate\Contracts\Support\Htmlable;
use Override;

class CreateRole extends ShieldCreateRole
{
    protected static string $resource = RoleResource::class;

    #[Override]
    public function getTitle(): string|Htmlable
    {
        return __('panel.roles.layout.create_role');
    }
}
