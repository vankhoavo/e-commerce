<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use App\Services\EmailOtpService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(
        private EmailOtpService $emailOtpService,
    ) {}

    /**
     * Validate and create a newly registered user.
     *
     * Google-linked emails may continue through the Email + Password flow.
     * In that case the existing account receives the chosen password instead
     * of attempting to create a duplicate email address.
     *
     * @param array<string, string> $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class)->where(fn ($query) => $query->whereNull('google_id')),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        $email = mb_strtolower(trim($input['email']));
        $googleUser = User::query()
            ->where('email', $email)
            ->whereNotNull('google_id')
            ->first();

        if ($googleUser) {
            abort_unless($googleUser->is_active, 403, 'Tài khoản đã bị khóa.');

            $googleUser->forceFill([
                'name' => $input['name'],
                'password' => $input['password'],
                'birth_date' => $googleUser->birth_date ?: today(),
            ])->save();

            return $googleUser->fresh();
        }

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
