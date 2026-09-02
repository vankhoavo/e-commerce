<?php

namespace Tests\Feature\Auth;

use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailPasswordResetOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_password_reset_request_screen_can_be_rendered(): void
    {
        $this->get(route('password.email.request'))
            ->assertOk();
    }

    public function test_active_user_can_request_an_email_otp(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.email.send'), [
            'email' => $user->email,
        ])
            ->assertRedirect(route('password.email.verify'))
            ->assertSessionHasNoErrors();

        $code = PasswordResetCode::query()->where('user_id', $user->id)->sole();

        $this->assertSame($user->email, $code->email);
        $this->assertNull($code->used_at);
        $this->assertTrue($code->expires_at->isFuture());
        $this->assertSame(0, $code->attempts);
        $this->assertSame($code->id, session('password_reset_code_id'));
        $this->assertFalse((bool) session('password_reset_verified'));
    }

    public function test_unknown_email_does_not_reveal_account_existence(): void
    {
        $this->from(route('password.email.request'))
            ->post(route('password.email.send'), [
                'email' => 'unknown@example.com',
            ])
            ->assertRedirect(route('password.email.request'))
            ->assertSessionHas('status', 'Nếu email tồn tại, mã OTP đã được gửi. Vui lòng kiểm tra hộp thư và thư mục Spam.');

        $this->assertDatabaseCount('password_reset_codes', 0);
    }

    public function test_inactive_user_does_not_receive_an_otp(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->post(route('password.email.send'), [
            'email' => $user->email,
        ])
            ->assertRedirect(route('password.email.request'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('password_reset_codes', 0);
        $this->assertSessionMissing('password_reset_user_id');
        $this->assertSessionMissing('password_reset_code_id');
        $this->assertSessionMissing('password_reset_verified');
    }

    public function test_valid_otp_allows_password_reset(): void
    {
        $user = User::factory()->create([
            'password' => 'old-password',
        ]);
        $code = $this->createOtp($user, '123456');

        $this->withSession($this->resetSession($user, $code))
            ->post(route('password.email.verify.submit'), [
                'code' => '123456',
            ])
            ->assertRedirect(route('password.email.reset'))
            ->assertSessionHas('password_reset_verified', true);

        $this->assertNotNull($code->fresh()->used_at);

        $this->post(route('password.email.reset.submit'), [
            'password' => 'New-password-123',
            'password_confirmation' => 'New-password-123',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('New-password-123', $user->fresh()->password));
        $this->assertSessionMissing('password_reset_user_id');
        $this->assertSessionMissing('password_reset_code_id');
        $this->assertSessionMissing('password_reset_verified');
    }

    public function test_resend_replaces_the_previous_otp(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.email.send'), [
            'email' => $user->email,
        ])->assertRedirect(route('password.email.verify'));

        $previousId = PasswordResetCode::query()->where('user_id', $user->id)->value('id');

        $this->post(route('password.email.verify.resend'))
            ->assertRedirect(route('password.email.verify'))
            ->assertSessionHas('status', 'Mã OTP mới đã được gửi. Vui lòng kiểm tra hộp thư.');

        $code = PasswordResetCode::query()->where('user_id', $user->id)->sole();

        $this->assertNotSame($previousId, $code->id);
        $this->assertSame($code->id, session('password_reset_code_id'));
        $this->assertFalse((bool) session('password_reset_verified'));
    }

    public function test_invalid_otp_increments_attempts(): void
    {
        $user = User::factory()->create();
        $code = $this->createOtp($user, '123456');

        $this->withSession($this->resetSession($user, $code))
            ->post(route('password.email.verify.submit'), [
                'code' => '654321',
            ])
            ->assertSessionHasErrors('code');

        $this->assertSame(1, $code->fresh()->attempts);
        $this->assertNull($code->fresh()->used_at);
    }

    public function test_otp_is_invalidated_after_five_failed_attempts(): void
    {
        $user = User::factory()->create();
        $code = $this->createOtp($user, '123456');
        $session = $this->resetSession($user, $code);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withSession($session)
                ->post(route('password.email.verify.submit'), ['code' => '654321'])
                ->assertSessionHasErrors('code');
        }

        $this->withSession($session)
            ->post(route('password.email.verify.submit'), ['code' => '123456'])
            ->assertRedirect(route('password.email.request'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('password_reset_codes', ['id' => $code->id]);
        $this->assertSessionMissing('password_reset_user_id');
        $this->assertSessionMissing('password_reset_code_id');
        $this->assertSessionMissing('password_reset_verified');
    }

    public function test_expired_otp_is_rejected_and_session_is_cleared(): void
    {
        $user = User::factory()->create();
        $code = $this->createOtp($user, '123456', now()->subMinute());
        $session = $this->resetSession($user, $code);

        $this->withSession($session)
            ->get(route('password.email.verify'))
            ->assertRedirect(route('password.email.request'));

        $this->assertDatabaseMissing('password_reset_codes', ['id' => $code->id]);
        $this->assertSessionMissing('password_reset_user_id');
        $this->assertSessionMissing('password_reset_code_id');
        $this->assertSessionMissing('password_reset_verified');
    }

    public function test_reset_page_requires_successful_otp_verification(): void
    {
        $this->get(route('password.email.reset'))
            ->assertRedirect(route('password.email.request'));
    }

    public function test_new_reset_request_invalidates_previous_reset_session(): void
    {
        $user = User::factory()->create();
        $oldCode = $this->createOtp($user, '123456');

        $this->withSession($this->resetSession($user, $oldCode, true))
            ->post(route('password.email.send'), [
                'email' => 'unknown@example.com',
            ])
            ->assertRedirect(route('password.email.request'));

        $this->assertSessionMissing('password_reset_user_id');
        $this->assertSessionMissing('password_reset_code_id');
        $this->assertSessionMissing('password_reset_verified');
    }

    private function createOtp(User $user, string $otp, $expiresAt = null): PasswordResetCode
    {
        return PasswordResetCode::query()->create([
            'user_id' => $user->id,
            'email' => $user->email,
            'code_hash' => Hash::make($otp),
            'expires_at' => $expiresAt ?? now()->addMinutes(10),
            'attempts' => 0,
        ]);
    }

    /**
     * @return array<string, int|bool>
     */
    private function resetSession(User $user, PasswordResetCode $code, bool $verified = false): array
    {
        return [
            'password_reset_user_id' => $user->id,
            'password_reset_code_id' => $code->id,
            'password_reset_verified' => $verified,
        ];
    }
}
