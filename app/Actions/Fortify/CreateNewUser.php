<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    /** @param array<string, string> $input */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->where(fn ($query) => $query->whereNull('google_id'))],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ])->validate();

        $email = mb_strtolower(trim($input['email']));
        $googleUser = User::query()->where('email', $email)->whereNotNull('google_id')->first();
        if ($googleUser) {
            abort_unless($googleUser->is_active, 403, 'Tài khoản đã bị khóa.');
            $googleUser->forceFill([
                'name' => $input['name'],
                'password' => $input['password'],
                'birth_date' => $googleUser->birth_date ?: today(),
                'registration_pending' => false,
            ])->save();

            return $googleUser->fresh();
        }

        return User::create([
            'name' => $input['name'],
            'email' => $email,
            'password' => $input['password'],
            'birth_date' => today(),
            'registration_pending' => true,
        ]);
    }
}
