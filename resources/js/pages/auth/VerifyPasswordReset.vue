<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { login } from '@/routes';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const page = usePage();
const email = computed(() => (page.props as any).email ?? '');
const expiresAt = computed(() => (page.props as any).expiresAt ?? null);
const remainingAttempts = computed(() => Number((page.props as any).remainingAttempts ?? 5));
const resendAvailableIn = computed(() => Number((page.props as any).resendAvailableIn ?? 0));
const resendLocked = computed(() => Boolean((page.props as any).resendLocked ?? false));
const code = ref('');
const showResend = ref(false);
const remainingSeconds = ref(0);
const resendCountdown = ref(0);
let countdownTimer: number | null = null;

function normalizeCode(event: Event): void {
    const input = event.target as HTMLInputElement;
    code.value = input.value.replace(/\D/g, '').slice(0, 6);
    input.value = code.value;
}

function updateCountdown(): void {
    if (expiresAt.value) {
        remainingSeconds.value = Math.max(0, Math.ceil((new Date(expiresAt.value).getTime() - Date.now()) / 1000));
    } else {
        remainingSeconds.value = 0;
    }
    resendCountdown.value = Math.max(0, resendCountdown.value - 1);
}

onMounted(() => {
    updateCountdown();
    resendCountdown.value = resendAvailableIn.value;
    countdownTimer = window.setInterval(updateCountdown, 1000);
});

onBeforeUnmount(() => {
    if (countdownTimer !== null) window.clearInterval(countdownTimer);
});
</script>

<template>
    <Head title="Xác minh OTP" />

    <div class="forgot-card">
        <div class="forgot-hero">
            <div class="forgot-hero-icon"><i class="bi bi-envelope-check-fill" /></div>
            <div>
                <span>TECHSTORE · BẢO MẬT</span>
                <h2>Xác minh Email</h2>
                <p>Nhập mã OTP 6 số đã được gửi tới <strong>{{ email || 'Email của bạn' }}</strong>.</p>
            </div>
        </div>

        <div v-if="page.props.status === 'otp-resent'" class="forgot-status" role="status">
            <i class="bi bi-check-circle-fill" />
            <span>Mã OTP mới đã được gửi đến Email của bạn.</span>
        </div>

        <Form action="/settings/security/forgot-password/verify" method="post" v-slot="{ errors, processing }" class="forgot-form">
            <div>
                <div class="otp-label-row">
                    <label for="code">Mã OTP</label>
                    <span :class="['otp-countdown', { expired: remainingSeconds === 0 }]">
                        <i class="bi bi-clock" />
                        {{ remainingSeconds > 0 ? `${Math.floor(remainingSeconds / 60)}:${String(remainingSeconds % 60).padStart(2, '0')}` : 'Hết hạn' }}
                    </span>
                </div>
                <div class="forgot-input" :class="{ 'has-error': !!errors.code }">
                    <i class="bi bi-shield-lock" />
                    <input id="code" v-model="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" minlength="6" required autofocus placeholder="123456" @input="normalizeCode" />
                </div>
                <div v-if="errors.code" class="otp-error-alert" role="alert">
                    <i class="bi bi-exclamation-triangle-fill" />
                    <div><strong>Mã OTP không chính xác</strong><span>{{ errors.code }}</span></div>
                </div>
                <InputError v-else :message="errors.code" />
                <p class="attempt-note">Bạn còn {{ remainingAttempts }} lần thử. Sai quá 5 lần, tài khoản sẽ bị khóa.</p>
            </div>

            <button type="submit" class="forgot-submit" :disabled="processing || code.length !== 6 || remainingSeconds === 0">
                <span v-if="processing" class="spinner-border spinner-border-sm" />
                <i v-else class="bi bi-check-circle-fill" />
                {{ processing ? 'Đang xác minh...' : 'Xác minh OTP' }}
            </button>
        </Form>

        <button type="button" class="resend-button" @click="showResend = !showResend" :disabled="resendLocked || resendCountdown > 0 || remainingSeconds > 0">
            <i class="bi bi-arrow-clockwise" />
            {{ resendLocked || resendCountdown > 0 ? 'Đang chờ giới hạn gửi lại' : remainingSeconds > 0 ? `Gửi lại sau ${remainingSeconds} giây` : 'Gửi lại mã OTP' }}
        </button>

        <Form v-if="showResend" action="/settings/security/forgot-password/verify/resend" method="post" v-slot="{ processing: resending }" class="resend-form">
            <button type="submit" class="resend-button secondary" :disabled="resending || remainingSeconds > 0 || resendLocked || resendCountdown > 0">
                <span v-if="resending" class="spinner-border spinner-border-sm" />
                <i v-else class="bi bi-arrow-clockwise" />
                {{ resending ? 'Đang gửi lại...' : 'Xác nhận gửi mã mới' }}
            </button>
        </Form>

        <div class="forgot-note"><i class="bi bi-clock" /><span>Mã OTP có hiệu lực trong 1 phút. Chỉ được gửi tối đa 5 mã; từ lần thứ 6 phải quay lại sau 60 phút. Không thể gửi mã mới trước khi mã hiện tại hết hạn.</span></div>
        <div class="forgot-back"><Link :href="login()"><i class="bi bi-arrow-left" /> Quay lại Đăng nhập</Link></div>
    </div>
</template>

<style scoped>
.forgot-card{padding:26px;border:1px solid #e4e7ec;border-radius:18px;background:#fff;box-shadow:0 10px 30px rgba(16,24,40,.05)}.forgot-hero{display:flex;align-items:center;gap:14px;padding-bottom:20px;border-bottom:1px solid #edf0f4}.forgot-hero-icon{display:grid;flex:0 0 52px;width:52px;height:52px;place-items:center;border:1px solid #d8e5ff;border-radius:15px;color:#2563eb;background:#eff6ff;font-size:21px}.forgot-hero span{color:#2563eb;font-size:8px;font-weight:900;letter-spacing:.16em}.forgot-hero h2{margin:4px 0 0;color:#101828;font-size:20px;font-weight:850}.forgot-hero p{margin:5px 0 0;color:#667085;font-size:10px;line-height:1.55}.forgot-hero strong{color:#1d4ed8}.forgot-status{display:flex;align-items:center;gap:8px;margin-top:16px;padding:10px 12px;border:1px solid #b7ebcc;border-radius:10px;color:#067647;background:#f0fdf4;font-size:10px}.forgot-form{display:grid;gap:15px;margin-top:20px}.otp-label-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:7px}.otp-label-row label{margin:0!important;color:#344054;font-size:11px;font-weight:800}.otp-countdown{display:inline-flex;align-items:center;gap:5px;color:#2563eb;font-size:10px;font-weight:850}.otp-countdown.expired{color:#dc2626}.forgot-input{display:flex;align-items:center;height:45px;padding:0 12px;border:1px solid #dfe4ec;border-radius:10px;background:#fff;transition:border-color .18s}.forgot-input.has-error{border-color:#fca5a5}.forgot-input:focus-within{border-color:#8fb2f4}.forgot-input i{margin-right:9px;color:#98a2b3}.forgot-input input{width:100%;border:0;outline:0;color:#344054;background:transparent;font-size:11px;letter-spacing:.18em}.forgot-submit{display:flex;align-items:center;justify-content:center;gap:7px;height:43px;border:0;border-radius:10px;color:#fff;background:linear-gradient(135deg,#2563eb,#4f46e5);box-shadow:0 8px 18px rgba(37,99,235,.18);font-size:11px;font-weight:850}.forgot-submit:disabled{opacity:.6}.otp-error-alert{display:flex;align-items:flex-start;gap:9px;margin-top:9px;padding:10px 11px;border:1px solid #fecaca;border-radius:10px;color:#991b1b;background:#fef2f2;font-size:10px;line-height:1.45}.otp-error-alert i{margin-top:1px}.otp-error-alert strong,.otp-error-alert span{display:block}.otp-error-alert span{margin-top:2px;color:#b42318}.attempt-note{margin:7px 0 0;color:#667085;font-size:9px;line-height:1.5}.resend-button{display:flex;align-items:center;justify-content:center;gap:7px;width:100%;height:40px;margin-top:11px;border:1px solid #dbe4f0;border-radius:10px;color:#2563eb;background:#f8fbff;font-size:10px;font-weight:800}.resend-button:disabled{opacity:.65}.resend-button.secondary{margin-top:8px;background:#fff}.forgot-note{display:flex;align-items:center;gap:7px;margin-top:15px;padding:10px;border-radius:9px;color:#667085;background:#f8fafc;font-size:9px;line-height:1.5}.forgot-note i{color:#2563eb}.forgot-back{margin-top:17px;padding-top:15px;border-top:1px solid #edf0f4;text-align:center}.forgot-back a{color:#2563eb;font-size:10px;font-weight:800;text-decoration:none}
</style>
