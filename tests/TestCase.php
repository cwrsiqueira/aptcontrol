<?php

namespace Tests;

use App\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /** Cria usuário com confirmed_user fora do fillable. */
    protected function makeUser(array $attributes = []): User
    {
        $confirmed = (int) ($attributes['confirmed_user'] ?? 0);
        unset($attributes['confirmed_user']);

        $user = User::create(array_merge([
            'name' => 'Usuário teste',
            'email' => 'user-' . uniqid('', true) . '@example.com',
            'password' => Hash::make('12345678'),
        ], $attributes));

        $user->confirmed_user = $confirmed;
        $user->save();

        return $user->fresh();
    }
}
