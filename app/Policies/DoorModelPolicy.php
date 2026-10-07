<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DoorModel;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DoorModelPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DoorModel');
    }

    public function view(AuthUser $authUser, DoorModel $doorModel): bool
    {
        return $authUser->can('View:DoorModel');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DoorModel');
    }

    public function update(AuthUser $authUser, DoorModel $doorModel): bool
    {
        return $authUser->can('Update:DoorModel');
    }

    public function restore(AuthUser $authUser, DoorModel $doorModel): bool
    {
        return $authUser->can('Restore:DoorModel');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DoorModel');
    }

    public function replicate(AuthUser $authUser, DoorModel $doorModel): bool
    {
        return $authUser->can('Replicate:DoorModel');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DoorModel');
    }
}
