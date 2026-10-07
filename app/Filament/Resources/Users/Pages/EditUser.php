<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('users.layout.edit_user');
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $verified = $data['email_verified'] ?? false;
        unset($data['email_verified']);

        /** @var User $record */
        $record->fill($data);
        $record->email_verified_at = $verified
            ? ($record->isDirty('email') ? now() : ($record->email_verified_at ?? now()))
            : null;
        $record->save();

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
