<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';

defineProps<{ email: string; expiresAt: string; remainingAttempts: number; status?: string }>();
</script>

<template>
    <Head title="Xác minh OTP quản trị viên" />
    <div class="admin-security-card">
        <div class="security-kicker">BẢO MẬT QUẢN TRỊ VIÊN</div>
        <h1>Xác minh mã OTP</h1>
        <p>Mã xác minh đã được gửi đến <strong>{{ email }}</strong>.</p>
        <div v-if="status" class="security-status">{{ status }}</div>
        <Form action="/admin/administrator/password/verify" method="post" class="security-form" v-slot="{ errors, processing }">
            <label for="code">Mã OTP 6 chữ số</label>
            <input id="code" name="code" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required autofocus autocomplete="one-time-code" />
            <span v-if="errors.code" class="security-error">{{ errors.code }}</span>
            <small>Còn {{ remainingAttempts }} lần thử.</small>
            <button class="btn btn-primary" :disabled="processing">{{ processing ? 'Đang xác minh...' : 'Xác minh' }}</button>
        </Form>
        <Link href="/admin/administrators" class="security-back">Quay lại Quản trị viên</Link>
    </div>
</template>

<style scoped>
.admin-security-card{max-width:460px;margin:50px auto;padding:28px;border:1px solid #e5e7eb;border-radius:18px;background:#fff;box-shadow:0 12px 30px rgba(16,24,40,.06)}
.security-kicker{color:#2563eb;font-size:10px;font-weight:900;letter-spacing:.13em}.admin-security-card h1{margin:6px 0;font-size:1.45rem;font-weight:900}.admin-security-card p{color:#667085;font-size:.82rem}.security-status{margin:15px 0;padding:10px 12px;border-radius:9px;background:#f0fdf4;color:#067647;font-size:.76rem}.security-form{display:grid;gap:8px;margin-top:20px}.security-form label{font-size:.75rem;font-weight:800;color:#344054}.security-form input{height:46px;padding:0 12px;border:1px solid #d0d5dd;border-radius:10px;font-size:1.15rem;letter-spacing:.2em}.security-form small{color:#98a2b3}.security-error{color:#dc2626;font-size:.72rem}.security-back{display:block;margin-top:16px;text-align:center;font-size:.75rem;color:#2563eb;text-decoration:none}
</style>
