<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineOptions({ layout: { title: 'Khôi phục mật khẩu', description: 'Nhập email đã đăng ký để nhận liên kết đặt lại mật khẩu.' } });
defineProps<{ status?: string }>();
</script>

<template>
    <Head title="Quên mật khẩu" />
    <div class="forgot-intro mb-4"><div class="forgot-icon"><i class="bi bi-shield-lock-fill" /></div><div><h2 class="h5 fw-bold mb-1">Bạn quên mật khẩu?</h2><p class="text-secondary small mb-0">Đừng lo. Chúng tôi sẽ gửi hướng dẫn khôi phục đến email của bạn.</p></div></div>
    <div v-if="status" class="alert alert-success border-0 rounded-3 small"><i class="bi bi-check-circle-fill me-2" />{{ status }}</div>
    <Form v-bind="email.form()" v-slot="{ errors, processing }" class="vstack gap-3">
        <div><label for="email" class="form-label fw-semibold">Địa chỉ email</label><div class="input-group input-group-lg"><span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-secondary" /></span><input id="email" type="email" name="email" class="form-control border-start-0" required autofocus autocomplete="email" placeholder="email@example.com" /></div><InputError :message="errors.email" /></div>
        <button type="submit" class="btn btn-primary btn-lg w-100" :disabled="processing"><i v-if="!processing" class="bi bi-send me-2" />{{ processing ? 'Đang gửi...' : 'Gửi liên kết khôi phục' }}</button>
    </Form>
    <div class="forgot-help mt-4"><i class="bi bi-info-circle me-2" /><span>Kiểm tra cả thư mục Spam nếu bạn chưa thấy email.</span></div>
    <div class="text-center auth-bottom mt-4"><Link :href="login()"><i class="bi bi-arrow-left me-1" />Quay lại đăng nhập</Link></div>
</template>
