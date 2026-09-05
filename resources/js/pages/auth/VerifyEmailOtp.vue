<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const email = computed(() => (page.props as any).email ?? '');
const remainingAttempts = computed(() => Number((page.props as any).remainingAttempts ?? 3));
const code = ref('');
const showResend = ref(false);

function normalizeCode(event: Event): void {
    const input = event.target as HTMLInputElement;
    code.value = input.value.replace(/\D/g, '').slice(0, 6);
    input.value = code.value;
}

function clearCode(): void {
    code.value = '';
}
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
            <input
                v-model="code"
                name="code"
                inputmode="numeric"
                maxlength="6"
                autocomplete="one-time-code"
                class="form-control form-control-lg text-center otp-input"
                placeholder="••••••"
                required
                autofocus
                @input="normalizeCode"
                @focus="clearCode"
            />
            <div v-if="errors.code" class="otp-error-alert" role="alert">
                <i class="bi bi-exclamation-triangle-fill" />
                <div>
                    <strong>Mã OTP không chính xác</strong>
                    <span>{{ errors.code }}</span>
                </div>
            </div>
            <p class="small text-muted mt-2 mb-0">Bạn còn {{ remainingAttempts }} lần thử. Sai quá 3 lần, tài khoản sẽ bị khóa.</p>
            <button class="btn btn-primary btn-lg w-100 mt-3" :disabled="processing || code.length !== 6">
                {{ processing ? 'Đang xác thực...' : 'Xác thực' }}
            </button>
        </Form>

        <button type="button" class="btn btn-link w-100 mt-2" @click="showResend = !showResend">
            Gửi lại mã OTP
        </button>

        <div v-if="showResend" class="mt-2">
            <p class="small text-muted mb-2">Chỉ gửi lại khi mã đã hết hạn hoặc bạn chưa nhận được mã.</p>
            <Form action="/verify-email-otp/resend" method="post" v-slot="{ processing }">
                <button class="btn btn-outline-primary w-100" type="submit" :disabled="processing">
                    {{ processing ? 'Đang gửi mã...' : 'Gửi một mã OTP mới' }}
                </button>
            </Form>
        </div>

        <Form action="/verify-email-otp/defer" method="post" class="mt-3">
            <button class="btn btn-outline-secondary w-100" type="submit">
                Tôi sẽ xác thực lại sau
            </button>
        </Form>

        <Link href="/" class="auth-back"><i class="bi bi-arrow-left me-1" />Về trang chủ</Link>
    </div>
</template>

<style scoped>
.otp-error-alert{display:flex;align-items:flex-start;gap:9px;margin-top:10px;padding:11px 12px;border:1px solid #fecaca;border-radius:11px;color:#991b1b;background:#fef2f2;font-size:.72rem;line-height:1.45}.otp-error-alert>i{margin-top:1px}.otp-error-alert strong,.otp-error-alert span{display:block}.otp-error-alert span{margin-top:2px;color:#b42318}
</style>
