<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const page = usePage();
const email = computed(() => (page.props as any).email ?? '');
const expiresAt = computed(() => (page.props as any).expiresAt ?? null);
const remainingAttempts = computed(() => Number((page.props as any).remainingAttempts ?? 5));
const resendAvailableIn = computed(() => Number((page.props as any).resendAvailableIn ?? 0));
const resendLocked = computed(() => Boolean((page.props as any).resendLocked ?? false));
const code = ref('');
const remainingSeconds = ref(0);
let countdownTimer: number | null = null;
let resendLockedSeconds = ref(0);

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
    resendLockedSeconds.value = Math.max(resendLockedSeconds.value - 1, 0);
}

onMounted(() => {
    remainingSeconds.value = Math.max(0, Math.ceil((new Date(expiresAt.value).getTime() - Date.now()) / 1000));
    resendLockedSeconds.value = resendAvailableIn.value;
    countdownTimer = window.setInterval(updateCountdown, 1000);
});

onBeforeUnmount(() => {
    if (countdownTimer !== null) window.clearInterval(countdownTimer);
});
</script>

<template>
    <Head title="Xác thực Email" />
    <div class="auth-otp-page">
        <div class="otp-icon"><i class="bi bi-envelope-check-fill" /></div>
        <span class="section-kicker">XÁC THỰC TÀI KHOẢN</span>
        <h1>Xác thực Email</h1>
        <p>Chúng tôi đã gửi mã OTP 6 số đến <strong>{{ email }}</strong>. Mã chỉ có hiệu lực trong 1 phút.</p>

        <Form action="/verify-email-otp" method="post" v-slot="{ errors, processing }" class="mt-4">
            <label class="form-label">Mã OTP</label>
            <input v-model="code" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" class="form-control form-control-lg text-center otp-input" placeholder="••••••" required autofocus @input="normalizeCode" />
            <div v-if="errors.code" class="otp-error-alert" role="alert">
                <i class="bi bi-exclamation-triangle-fill" />
                <div><strong>Mã OTP không chính xác</strong><span>{{ errors.code }}</span></div>
            </div>
            <p class="small text-muted mt-2 mb-0">Bạn còn {{ remainingAttempts }} lần thử.</p>
            <button class="btn btn-primary btn-lg w-100 mt-3" :disabled="processing || code.length !== 6 || remainingSeconds === 0">
                {{ processing ? 'Đang xác thực...' : 'Xác thực' }}
            </button>
        </Form>

        <div class="timer-note">
            <i class="bi bi-clock" />
            <span>{{ remainingSeconds > 0 ? `OTP hiện tại còn ${remainingSeconds} giây.` : 'OTP đã hết hạn. Bạn có thể yêu cầu mã mới.' }}</span>
        </div>

        <Form action="/verify-email-otp/resend" method="post" v-slot="{ processing }" class="mt-2">
            <button class="btn btn-outline-primary w-100" type="submit" :disabled="processing || remainingSeconds > 0 || resendLocked || resendLockedSeconds > 0">
                {{ processing ? 'Đang gửi mã...' : resendLocked || resendLockedSeconds > 0 ? `Gửi lại sau ${Math.ceil((resendLockedSeconds || 0) / 60)} phút` : remainingSeconds > 0 ? `Gửi lại sau ${remainingSeconds} giây` : 'Gửi mã OTP mới' }}
            </button>
        </Form>

        <p class="small text-muted mt-2 mb-0">Mỗi Email chỉ được gửi tối đa 5 mã OTP. Từ lần thứ 6, vui lòng quay lại sau 60 phút.</p>

        <Form action="/verify-email-otp/defer" method="post" class="mt-3">
            <button class="btn btn-outline-secondary w-100" type="submit">Tôi sẽ xác thực lại sau</button>
        </Form>

        <Link href="/" class="auth-back"><i class="bi bi-arrow-left me-1" />Về trang chủ</Link>
    </div>
</template>

<style scoped>
.otp-error-alert{display:flex;align-items:flex-start;gap:9px;margin-top:10px;padding:11px 12px;border:1px solid #fecaca;border-radius:11px;color:#991b1b;background:#fef2f2;font-size:.72rem;line-height:1.45}.otp-error-alert>i{margin-top:1px}.otp-error-alert strong,.otp-error-alert span{display:block}.otp-error-alert span{margin-top:2px;color:#b42318}.timer-note{display:flex;align-items:center;gap:7px;margin-top:12px;padding:10px;border-radius:9px;background:#f8fafc;color:#667085;font-size:.7rem}.timer-note i{color:#2563eb}
</style>
