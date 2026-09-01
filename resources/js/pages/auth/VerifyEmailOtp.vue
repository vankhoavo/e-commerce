<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
const page = usePage();
const email = computed(() => (page.props as any).email ?? '');
</script>
<template>
    <Head title="Xác thực Email" />
    <div class="auth-otp-page">
        <div class="otp-icon"><i class="bi bi-envelope-check-fill"/></div>
        <span class="section-kicker">XÁC THỰC TÀI KHOẢN</span>
        <h1>Xác thực Email</h1>
        <p>Chúng tôi đã gửi mã OTP 6 số đến <strong>{{ email }}</strong>. Mã có hiệu lực trong 10 phút.</p>
        <Form action="/verify-email-otp" method="post" v-slot="{ errors, processing }" class="mt-4">
            <label class="form-label">Mã OTP</label>
            <input name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" class="form-control form-control-lg text-center otp-input" placeholder="••••••" required autofocus>
            <div v-if="errors.code" class="text-danger small mt-2">{{ errors.code }}</div>
            <button class="btn btn-primary btn-lg w-100 mt-3" :disabled="processing">{{ processing ? 'Đang xác thực...' : 'Xác thực Email' }}</button>
        </Form>
        <Form action="/verify-email-otp/resend" method="post" class="mt-2"><button class="btn btn-link w-100" type="submit">Gửi lại mã OTP</button></Form>
        <Link href="/" class="auth-back"><i class="bi bi-arrow-left me-1"/>Về trang chủ</Link>
    </div>
</template>
