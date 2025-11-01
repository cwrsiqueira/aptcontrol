<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\User;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Admin padrão
        User::firstOrCreate(
            ['email' => 'admin@email.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('12345678'), // altere depois!
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
                'confirmed_user' => 1, // 1 = admin (no seu código, libera o painel/menu)
            ]
        );
    }
}
