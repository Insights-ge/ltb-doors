<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'demo@demo.com'],
            [
                'name' => 'Insights Demo Admin',
                'password' => Hash::make('demo'),
                'email_verified_at' => now(),
            ]
        );

        User::query()->firstOrCreate(
            ['email' => 'admin@ltb.ge'],
            [
                'name' => 'LTB admin',
                'password' => Hash::make('1234'),
                'email_verified_at' => now(),
            ]
        );
    }
}
