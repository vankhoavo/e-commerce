<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Đặt lại mật khẩu quản trị viên" />
    <div class="admin-security-card">
        <div class="security-kicker">BẢO MẬT QUẢN TRỊ VIÊN</div>
        <h1>Đặt lại mật khẩu</h1>
        <p>Tạo mật khẩu mới cho tài khoản admin.</p>
        <Form action="/admin/administrator/password/reset" method="post" class="security-form" v-slot="{ errors, processing }">
            <label for="password">Mật khẩu mới</label>
            <input id="password" type="password" name="password" minlength="8" required autocomplete="new-password" />
            <span v-if="errors.password" class="security-error">{{ errors.password }}</span>
            <label for="password_confirmation">Xác nhận mật khẩu</label>
            <input id="password_confirmation" type="password" name="password_confirmation" minlength="8" required autocomplete="new-password" />
            <span v-if="errors.password_confirmation" class="security-error">{{ errors.password_confirmation }}</span>
            <button class="btn btn-primary" :disabled="processing">{{ processing ? 'Đang lưu...' : 'Đặt lại mật khẩu' }}</button>
        </Form>
        <Link href="/admin/administrators" class="security-back">Quay lại Quản trị viên</Link>
    </div>
</template>

<style scoped>
.admin-security-card{max-width:460px;margin:50px auto;padding:28px;border:1px solid #e5e7eb;border-radius:18px;background:#fff;box-shadow:0 12px 30px rgba(16,24,40,.06)}
.security-kicker{color:#2563eb;font-size:10px;font-weight:900;letter-spacing:.13em}.admin-security-card h1{margin:6px 0;font-size:1.45rem;font-weight:900}.admin-security-card p{color:#667085;font-size:.82rem}.security-form{display:grid;gap:8px;margin-top:20px}.security-form label{font-size:.75rem;font-weight:800;color:#344054}.security-form input{height:46px;padding:0 12px;border:1px solid #d0d5dd;border-radius:10px}.security-error{color:#dc2626;font-size:.72rem}.security-back{display:block;margin-top:16px;text-align:center;font-size:.75rem;color:#2563eb;text-decoration:none}
</style>
