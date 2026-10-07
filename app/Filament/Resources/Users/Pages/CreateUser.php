<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('users.layout.create_user');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $verified = $data['email_verified'] ?? false;
        unset($data['email_verified']);

        $user = new User($data);
        $user->email_verified_at = $verified ? now() : null;
        $user->save();

        return $user;
    }

    protected function getRedirectUrl(): string
    {
        return UserResource::getUrl('index');
    }
}
