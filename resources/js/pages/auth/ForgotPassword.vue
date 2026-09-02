<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineOptions({ layout: { title: 'Khôi phục mật khẩu', description: 'Nhập email đã đăng ký để nhận hướng dẫn khôi phục.' } });
defineProps<{ status?: string }>();
</script>
<template>
    <Head title="Quên mật khẩu" />
    <div class="forgot-card">
        <div class="forgot-hero"><div class="forgot-hero-icon"><i class="bi bi-shield-lock-fill" /></div><div><span>TECHSTORE ACCOUNT</span><h2>Khôi phục mật khẩu</h2><p>Nhập email đã đăng ký. Chúng tôi sẽ gửi hướng dẫn để bạn đặt lại mật khẩu.</p></div></div>
        <div v-if="status" class="forgot-status"><i class="bi bi-check-circle-fill" /><span>{{ status }}</span></div>
        <Form v-bind="email.form()" v-slot="{ errors, processing }" class="forgot-form">
            <div><label for="email">Địa chỉ Email</label><div class="forgot-input"><i class="bi bi-envelope"/><input id="email" type="email" name="email" required autofocus autocomplete="email" placeholder="email@example.com"/></div><InputError :message="errors.email"/></div>
            <button type="submit" class="forgot-submit" :disabled="processing"><span v-if="processing" class="spinner-border spinner-border-sm"/><i v-else class="bi bi-send-fill"/>{{ processing ? 'Đang gửi...' : 'Gửi hướng dẫn khôi phục' }}</button>
        </Form>
        <div class="forgot-note"><i class="bi bi-info-circle"/><span>Không thấy email? Hãy kiểm tra cả thư mục Spam hoặc Quảng cáo.</span></div>
        <div class="forgot-back"><Link :href="login()"><i class="bi bi-arrow-left"/> Quay lại Đăng nhập</Link></div>
    </div>
</template>
<style scoped>
.forgot-card{padding:26px;border:1px solid #e4e7ec;border-radius:18px;background:#fff;box-shadow:0 10px 30px rgba(16,24,40,.05)}.forgot-hero{display:flex;align-items:center;gap:14px;padding-bottom:20px;border-bottom:1px solid #edf0f4}.forgot-hero-icon{display:grid;flex:0 0 52px;width:52px;height:52px;place-items:center;border:1px solid #d8e5ff;border-radius:15px;color:#2563eb;background:#eff6ff;font-size:21px}.forgot-hero span{color:#2563eb;font-size:8px;font-weight:900;letter-spacing:.16em}.forgot-hero h2{margin:4px 0 0;color:#101828;font-size:20px;font-weight:850}.forgot-hero p{margin:5px 0 0;color:#667085;font-size:10px;line-height:1.55}.forgot-status{display:flex;align-items:center;gap:8px;margin-top:16px;padding:10px 12px;border:1px solid #b7ebcc;border-radius:10px;color:#067647;background:#f0fdf4;font-size:10px}.forgot-form{display:grid;gap:15px;margin-top:20px}.forgot-form label{display:block;margin-bottom:7px;color:#344054;font-size:11px;font-weight:800}.forgot-input{display:flex;align-items:center;height:45px;padding:0 12px;border:1px solid #dfe4ec;border-radius:10px;background:#fff;transition:.18s}.forgot-input:focus-within{border-color:#8fb2f4;box-shadow:0 0 0 4px rgba(37,99,235,.08)}.forgot-input i{margin-right:9px;color:#98a2b3}.forgot-input input{width:100%;border:0;outline:0;color:#344054;background:transparent;font-size:11px}.forgot-submit{display:flex;align-items:center;justify-content:center;gap:7px;height:43px;border:0;border-radius:10px;color:#fff;background:linear-gradient(135deg,#2563eb,#4f46e5);box-shadow:0 8px 18px rgba(37,99,235,.18);font-size:11px;font-weight:850}.forgot-submit:disabled{opacity:.6}.forgot-note{display:flex;align-items:center;gap:7px;margin-top:15px;padding:10px;border-radius:9px;color:#667085;background:#f8fafc;font-size:9px}.forgot-note i{color:#2563eb}.forgot-back{margin-top:17px;padding-top:15px;border-top:1px solid #edf0f4;text-align:center}.forgot-back a{color:#2563eb;font-size:10px;font-weight:800;text-decoration:none}
</style>
