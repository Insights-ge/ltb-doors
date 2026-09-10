<?php

namespace App\Filament\Pages\Auth;

use App\Filament\Resources\Users\UserResource;
use DiogoGPinto\AuthUIEnhancer\Pages\Auth\AuthUiEnhancerLogin as BaseLogin;
use Filament\Facades\Filament;

class Login extends BaseLogin
{
    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(UserResource::getUrl());

            return;
        }

        parent::mount();

        $this->form->fill([
            'email' => 'admin@ltb.ge',
            'password' => '1234',
            'remember' => true,
        ]);
    }
}
