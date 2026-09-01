<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const email = computed(() => (page.props as any).email ?? '');
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
            <div class="verify-security"><i class="bi bi-shield-check" /><span>Mã có hiệu lực trong <strong>10 phút</strong>. Email hiện tại của bạn vẫn được giữ nguyên cho đến khi xác thực thành công.</span></div>
            <Form action="/settings/email/verify" method="post" v-slot="{ errors, processing }" class="mt-4">
                <label for="new-email-code" class="form-label">Mã xác thực</label>
                <input id="new-email-code" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" pattern="[0-9]{6}" class="form-control form-control-lg text-center otp-input" placeholder="000000" required autofocus>
                <div v-if="errors.code" class="text-danger small mt-2 text-start">{{ errors.code }}</div>
                <button class="btn btn-primary btn-lg w-100 mt-3" :disabled="processing"><i class="bi bi-check2-circle me-2" />{{ processing ? 'Đang xác thực...' : 'Xác nhận Email mới' }}</button>
            </Form>
            <Form action="/settings/email/resend" method="post" class="mt-2" v-slot="{ processing }"><button class="btn btn-link w-100 text-decoration-none" type="submit" :disabled="processing"><i class="bi bi-arrow-clockwise me-1" />{{ processing ? 'Đang gửi...' : 'Gửi lại mã OTP' }}</button></Form>
            <Link href="/settings/profile" class="auth-back mt-3 d-inline-block"><i class="bi bi-arrow-left me-1" />Hủy và quay lại hồ sơ</Link>
        </div>
    </div>
</template>
