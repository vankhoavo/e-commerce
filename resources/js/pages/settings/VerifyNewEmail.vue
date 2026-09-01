<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
const page = usePage();
const email = computed(() => (page.props as any).email ?? '');
</script>
<template>
<Head title="Xác thực Email mới" />
<div class="container py-5"><div class="auth-otp-page mx-auto"><div class="otp-icon"><i class="bi bi-envelope-check-fill"/></div><span class="section-kicker">THAY ĐỔI EMAIL</span><h1>Xác thực Email mới</h1><p>Mã OTP đã được gửi đến <strong>{{ email }}</strong>. Bạn phải xác thực mã này trước khi email mới được cập nhật.</p><Form action="/settings/email/verify" method="post" v-slot="{ errors, processing }" class="mt-4"><label class="form-label">Mã OTP 6 số</label><input name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" class="form-control form-control-lg text-center otp-input" placeholder="••••••" required autofocus><div v-if="errors.code" class="text-danger small mt-2">{{ errors.code }}</div><button class="btn btn-primary btn-lg w-100 mt-3" :disabled="processing">{{ processing ? 'Đang xác thực...' : 'Xác nhận Email mới' }}</button></Form><Link href="/settings/profile" class="auth-back mt-4 d-inline-block"><i class="bi bi-arrow-left me-1"/>Hủy và quay lại hồ sơ</Link></div></div>
</template>
