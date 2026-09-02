<?php

namespace Tests\Feature\Auth;

use App\Models\PasswordResetCode;
use App\Models\User;
use App\Services\PasswordResetOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmailPasswordResetOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_request_screen_can_be_rendered(): void
    {
        $this->get(route('password.email.request'))
            ->assertOk();
    }

    public function test_active_user_can_request_an_email_otp(): void
    {
        $user = User::factory()->create();
        $this->fakeOtpFor($user, '123456');

        $this->post(route('password.email.send'), [
            'email' => $user->email,
        ])
            ->assertRedirect(route('password.email.verify'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('password_reset_codes', [
            'user_id' => $user->id,
            'email' => $user->email,
            'used_at' => null,
        ]);
    }

    public function test_unknown_email_does_not_reveal_account_existence(): void
    {
        $response = $this->from(route('password.email.request'))
            ->post(route('password.email.send'), [
                'email' => 'unknown@example.com',
            ]);

        $response
            ->assertRedirect(route('password.email.request'))
            ->assertSessionHas('status', 'Nếu email tồn tại, mã OTP đã được gửi. Vui lòng kiểm tra hộp thư và thư mục Spam.');

        $this->assertDatabaseCount('password_reset_codes', 0);
    }

    public function test_valid_otp_allows_password_reset(): void
    {
        $user = User::factory()->create([
            'password' => 'old-password',
        ]);
        $this->fakeOtpFor($user, '123456');

        $this->post(route('password.email.send'), [
            'email' => $user->email,
        ]);

        $this->post(route('password.email.verify.submit'), [
            'code' => '123456',
        ])
            ->assertRedirect(route('password.email.reset'))
            ->assertSessionHasNoErrors();

        $this->post(route('password.email.reset.submit'), [
            'password' => 'New-password-123',
            'password_confirmation' => 'New-password-123',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('New-password-123', $user->fresh()->password));
    }

    public function test_invalid_otp_increments_attempts(): void
    {
        $user = User::factory()->create();
        $this->fakeOtpFor($user, '123456');

        $this->post(route('password.email.send'), [
            'email' => $user->email,
        ]);

        $this->post(route('password.email.verify.submit'), [
            'code' => '654321',
        ])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseHas('password_reset_codes', [
            'user_id' => $user->id,
            'attempts' => 1,
        ]);
    }

    public function test_otp_cannot_be_used_after_five_failed_attempts(): void
    {
        $user = User::factory()->create();
        $this->fakeOtpFor($user, '123456');

        $this->post(route('password.email.send'), [
            'email' => $user->email,
        ]);

        $this->post(route('password.email.verify.submit'), ['code' => '654321']);
        $this->post(route('password.email.verify.submit'), ['code' => '654321']);
        $this->post(route('password.email.verify.submit'), ['code' => '654321']);
        $this->post(route('password.email.verify.submit'), ['code' => '654321']);
        $this->post(route('password.email.verify.submit'), ['code' => '654321']);
        $this->post(route('password.email.verify.submit'), ['code' => '123456']);

        $this->assertFalse(session('password_reset_verified'));
    }

    public function test_expired_otp_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->fakeOtpFor($user, '123456');

        $this->post(route('password.email.send'), [
            'email' => $user->email,
        ]);

        PasswordResetCode::query()->where('user_id', $user->id)->update([
            'expires_at' => now()->subMinute(),
        ]);

        $this->post(route('password.email.verify.submit'), [
            'code' => '123456',
        ])
            ->assertRedirect(route('password.email.request'))
            ->assertSessionHasErrors('email');
    }

    private function fakeOtpFor(User $user, string $otp): void
    {
        $service = new class($otp) extends PasswordResetOtpService
        {
            public function __construct(private readonly string $otp) {}

            public function send(User $user): PasswordResetCode
            {
                PasswordResetCode::query()
                    ->where('user_id', $user->id)
                    ->whereNull('used_at')
                    ->delete();

                return PasswordResetCode::query()->create([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'code_hash' => Hash::make($this->otp),
                    'expires_at' => now()->addMinutes(10),
                    'attempts' => 0,
                ]);
            }
        };

        $this->instance(PasswordResetOtpService::class, $service);
    }
}
