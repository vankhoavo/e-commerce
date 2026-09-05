<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const email = computed(() => (page.props as any).email ?? '');
const showError = ref(false);

function clearError(): void {
    showError.value = false;
}
</script>

<template>
    <Head title="Xác thực Email mới" />
    <div class="email-verify-page">
        <div class="email-verify-card">
            <div class="otp-icon"><i class="bi bi-envelope-check-fill" /></div>
            <span class="section-kicker">BẢO MẬT TÀI KHOẢN</span>
            <h1>Xác thực Email mới</h1>
            <p class="email-verify-intro">Để hoàn tất thay đổi Email, hãy nhập mã OTP 6 số đã được gửi đến:</p>
            <div class="verify-email-target"><i class="bi bi-envelope-fill" /><strong>{{ email }}</strong></div>
            <div class="verify-security"><i class="bi bi-shield-check" /><span>Mã có hiệu lực trong <strong>1 phút</strong>. Email hiện tại của bạn vẫn được giữ nguyên cho đến khi xác thực thành công.</span></div>

            <Form action="/settings/email/verify" method="post" v-slot="{ errors, processing }" class="mt-4" @error="showError = true">
                <label for="new-email-code" class="form-label">Mã xác thực</label>
                <input id="new-email-code" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" pattern="[0-9]{6}" class="form-control form-control-lg text-center otp-input" placeholder="000000" required autofocus @focus="clearError">
                <div v-if="errors.code || showError" class="otp-error-alert" role="alert"><i class="bi bi-exclamation-triangle-fill" /><div><strong>Mã xác thực không chính xác</strong><span>{{ errors.code || 'Vui lòng kiểm tra Email và nhập lại mã OTP.' }}</span></div></div>
                <button class="btn btn-primary btn-lg w-100 mt-3" :disabled="processing"><i class="bi bi-check2-circle me-2" />{{ processing ? 'Đang xác thực...' : 'Xác nhận Email mới' }}</button>
            </Form>

            <Form action="/settings/email/resend" method="post" class="mt-2" v-slot="{ processing }">
                <button class="btn btn-link w-100 text-decoration-none" type="submit" :disabled="processing"><i class="bi bi-arrow-clockwise me-1" />{{ processing ? 'Đang gửi...' : 'Gửi lại mã OTP' }}</button>
            </Form>
            <Link href="/settings/profile" class="auth-back mt-3 d-inline-block"><i class="bi bi-arrow-left me-1" />Hủy và quay lại hồ sơ</Link>
        </div>
    </div>
</template>

<style scoped>
.otp-error-alert{display:flex;align-items:flex-start;gap:9px;margin-top:10px;padding:11px 12px;border:1px solid #fecaca;border-radius:11px;color:#991b1b;background:#fef2f2;font-size:.72rem;line-height:1.45}.otp-error-alert>i{margin-top:1px}.otp-error-alert strong,.otp-error-alert span{display:block}.otp-error-alert span{margin-top:2px;color:#b42318}
</style>
