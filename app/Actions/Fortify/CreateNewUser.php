<?php

namespace App\Actions\Fortify;

use App\Actions\Teams\CreateTeam;
use App\Concerns\PasswordValidationRules;
use App\Models\User;
use App\Services\EmailOtpService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(
        private CreateTeam $createTeam,
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
            ])->save();

            return $googleUser->fresh();
        }

        $user = DB::transaction(function () use ($input, $email): User {
            $user = User::create([
                'name' => $input['name'],
                'email' => $email,
                'password' => $input['password'],
            ]);

            $this->createTeam->handle($user, $user->name."'s Team", isPersonal: true);

            return $user;
        });

        // A normal registration must still verify ownership of the mailbox.
        $this->emailOtpService->send($user, $user->email);

        return $user;
    }
}
