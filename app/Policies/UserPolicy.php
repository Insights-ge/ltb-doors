<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:User');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:User');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:User');
    }

    public function update(User $authUser, User $user): bool
    {
        if ($this->targetsSuperAdminWithoutBeingOne($authUser, $user)) {
            return false;
        }

        if ($this->targetsSelfWithoutBeingSuperAdmin($authUser, $user)) {
            return false;
        }

        return $authUser->can('Update:User');
    }

    public function delete(User $authUser, User $user): bool
    {
        if ($this->targetsSuperAdminWithoutBeingOne($authUser, $user)) {
            return false;
        }

        if ($this->targetsSelfWithoutBeingSuperAdmin($authUser, $user)) {
            return false;
        }

        return $authUser->can('Delete:User');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:User');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:User');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:User');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:User');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:User');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:User');
    }

    private function targetsSuperAdminWithoutBeingOne(User $authUser, User $user): bool
    {
        return $user->hasRole(Role::SuperAdmin->value) && ! $authUser->hasRole(Role::SuperAdmin->value);
    }

    private function targetsSelfWithoutBeingSuperAdmin(User $authUser, User $user): bool
    {
        return $authUser->is($user) && ! $authUser->hasRole(Role::SuperAdmin->value);
    }
}
