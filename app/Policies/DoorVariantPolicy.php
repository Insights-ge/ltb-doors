<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DoorVariant;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DoorVariantPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DoorVariant');
    }

    public function view(AuthUser $authUser, DoorVariant $doorVariant): bool
    {
        return $authUser->can('View:DoorVariant');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DoorVariant');
    }

    public function update(AuthUser $authUser, DoorVariant $doorVariant): bool
    {
        return $authUser->can('Update:DoorVariant');
    }

    public function delete(AuthUser $authUser, DoorVariant $doorVariant): bool
    {
        return $authUser->can('Delete:DoorVariant');
    }

    public function restore(AuthUser $authUser, DoorVariant $doorVariant): bool
    {
        return $authUser->can('Restore:DoorVariant');
    }

    public function forceDelete(AuthUser $authUser, DoorVariant $doorVariant): bool
    {
        return $authUser->can('ForceDelete:DoorVariant');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DoorVariant');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DoorVariant');
    }

    public function replicate(AuthUser $authUser, DoorVariant $doorVariant): bool
    {
        return $authUser->can('Replicate:DoorVariant');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DoorVariant');
    }
}
