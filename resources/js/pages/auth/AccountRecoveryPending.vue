<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
const props = defineProps<{ name?: string | null; email: string; method: string; status?: string }>();
</script>
<template>
<Head title="Chờ phê duyệt khôi phục"/>
<div class="page"><div class="card"><div class="icon"><i class="bi bi-hourglass-split"/></div><h1>Đang chờ phê duyệt</h1><p>Yêu cầu khôi phục tài khoản của <strong>{{ props.name || props.email }}</strong> đã được tiếp nhận.</p><div class="steps"><div class="done"><i class="bi bi-check-circle-fill"/><span>Xác thực {{ props.method === 'google' ? 'Google' : 'Email/OTP' }}</span></div><div class="waiting"><i class="bi bi-clock-fill"/><span>Quản trị viên hoặc Nhân viên cấp cao phê duyệt</span></div><div><i class="bi bi-person-check"/><span>Khôi phục tài khoản</span></div></div><p class="note">Bạn chưa thể đăng nhập cho đến khi yêu cầu được phê duyệt.</p><Link class="button" href="/login">Quay lại đăng nhập</Link></div></div>
</template>
<style scoped>
.page{min-height:70vh;display:grid;place-items:center;padding:32px 16px}.card{width:min(480px,100%);padding:32px;border:1px solid #e5e9f0;border-radius:22px;background:#fff;box-shadow:0 16px 45px rgba(16,24,40,.08);text-align:center}.icon{display:grid;width:56px;height:56px;place-items:center;margin:0 auto 16px;border-radius:16px;background:#fffbeb;color:#d97706;font-size:24px}.card h1{font-size:1.5rem;font-weight:900}.card p{color:#667085;font-size:.78rem;line-height:1.6}.steps{display:flex;flex-direction:column;gap:10px;margin:22px 0;text-align:left}.steps>div{display:flex;align-items:center;gap:10px;padding:11px 12px;border:1px solid #eaecf0;border-radius:11px;color:#667085;font-size:.72rem}.steps i{font-size:15px}.steps .done{border-color:#bbf7d0;background:#f0fdf4;color:#15803d}.steps .waiting{border-color:#fde68a;background:#fffbeb;color:#a16207}.note{font-size:.7rem!important}.button{display:block;padding:11px;border-radius:10px;background:#2563eb;color:#fff;text-decoration:none;font-size:.75rem;font-weight:800}
</style>
