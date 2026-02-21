<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    public function register(array $data): array
    {
        $data['password'] = Hash::make($data['password']);
        $data['username'] = $this->generateUsername($data['name']);

        $user = User::create($data);

        if (! empty($data['categories'])) {
            $user->categories()->sync($data['categories']);
        }

        $user->load('categories');

        return [
            'token' => $user->createToken('api')->plainTextToken,
            'data'  => $user,
        ];
    }

    public function login(string $email, string $password): array
    {
        $deletedUser = User::onlyTrashed()->where('email', $email)->first();

        if ($deletedUser) {
            if ($deletedUser->deleted_at->diffInDays(now()) > 60) {
                abort(401, 'Esta conta foi excluída permanentemente.');
            }

            if (! Hash::check($password, $deletedUser->password)) {
                abort(401, 'Credenciais inválidas');
            }

            $deletedUser->restore();

            return [
                'token'    => $deletedUser->createToken('api')->plainTextToken,
                'data'     => $deletedUser->load('categories'),
                'restored' => true,
            ];
        }

        if (! Auth::attempt(['email' => $email, 'password' => $password])) {
            abort(401, 'Credenciais inválidas');
        }

        $user = Auth::user()->load('categories');

        return [
            'token'    => $user->createToken('api')->plainTextToken,
            'data'     => $user,
            'restored' => false,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function generateUsername(string $name): string
    {
        $base = preg_replace('/[^a-z0-9.]/u', '.', Str::ascii(Str::lower($name)));
        $base = preg_replace('/\.{2,}/', '.', trim($base, '.'));

        $username = $base;
        $counter = 1;

        while (User::withTrashed()->where('username', $username)->exists()) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }
}
