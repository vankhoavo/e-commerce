<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
const props = defineProps<{ status?: string }>();
const email = ref('');
</script>
<template>
<Head title="Khôi phục tài khoản"/>
<div class="recovery-page"><div class="recovery-card"><div class="icon"><i class="bi bi-person-check"/></div><h1>Khôi phục tài khoản</h1><p class="intro">Tài khoản của bạn đã được xóa mềm. Nhập email đã đăng ký để bắt đầu quy trình khôi phục.</p><div v-if="props.status" class="notice">{{ props.status === 'recovery-pending' ? 'Yêu cầu khôi phục của bạn đang chờ phê duyệt.' : props.status }}</div><Form action="/account/recovery/email" method="post" v-slot="{errors,processing}" class="vstack gap-3"><div><label for="email">Email tài khoản</label><input id="email" v-model="email" name="email" type="email" autocomplete="email" required placeholder="email@example.com"/><div v-if="errors.email" class="error">{{ errors.email }}</div></div><button :disabled="processing" type="submit">{{ processing ? 'Đang gửi...' : 'Gửi mã OTP' }}</button></Form><div class="back"><Link href="/login">← Quay lại đăng nhập</Link></div></div></div>
</template>
<style scoped>
.recovery-page{min-height:70vh;display:grid;place-items:center;padding:32px 16px}.recovery-card{width:min(430px,100%);padding:30px;border:1px solid #e5e9f0;border-radius:22px;background:#fff;box-shadow:0 16px 45px rgba(16,24,40,.08)}.icon{display:grid;width:54px;height:54px;place-items:center;margin:0 auto 16px;border-radius:16px;background:#eff6ff;color:#2563eb;font-size:24px}.recovery-card h1{text-align:center;font-size:1.5rem;font-weight:900}.intro{text-align:center;color:#667085;font-size:.78rem;line-height:1.6}.notice{margin:14px 0;padding:10px 12px;border:1px solid #bfdbfe;border-radius:10px;background:#eff6ff;color:#1d4ed8;font-size:.72rem}.recovery-card label{display:block;margin-bottom:6px;font-size:.72rem;font-weight:800}.recovery-card input{width:100%;padding:11px 12px;border:1px solid #d0d5dd;border-radius:10px;font-size:.8rem}.recovery-card button{padding:11px;border:0;border-radius:10px;background:#2563eb;color:#fff;font-weight:800}.recovery-card button:disabled{opacity:.6}.error{margin-top:6px;color:#b42318;font-size:.68rem}.back{text-align:center;margin-top:18px;font-size:.7rem}.back a{color:#667085;text-decoration:none}
</style>
