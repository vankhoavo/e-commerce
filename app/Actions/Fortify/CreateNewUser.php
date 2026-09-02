<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Services\EmailOtpService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    public function __construct(private EmailOtpService $emailOtpService) {}

    /** @param array<string, string> $input */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ])->validate();

        $email = mb_strtolower(trim($input['email']));
        $user = User::create([
            'name' => $input['name'],
            'email' => $email,
            'password' => $input['password'],
            'birth_date' => today(),
        ]);

        $this->emailOtpService->send($user, $user->email);
        return $user;
    }
}
