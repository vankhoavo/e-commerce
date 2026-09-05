<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{ email: string; expiresAt: string; remainingAttempts: number; sendLimitReached?: boolean; status?: string }>();
const remainingSeconds = ref(0);
let countdownTimer: number | null = null;
const code = ref('');
const formattedRemaining = computed(() => `${Math.floor(remainingSeconds.value / 60)}:${String(remainingSeconds.value % 60).padStart(2, '0')}`);

function updateCountdown(): void {
    remainingSeconds.value = Math.max(0, Math.ceil((new Date(props.expiresAt).getTime() - Date.now()) / 1000));
}

function normalizeCode(event: Event): void {
    const input = event.target as HTMLInputElement;
    code.value = input.value.replace(/\D/g, '').slice(0, 6);
    input.value = code.value;
}

onMounted(() => {
    updateCountdown();
    countdownTimer = window.setInterval(updateCountdown, 1000);
});

onBeforeUnmount(() => {
    if (countdownTimer !== null) window.clearInterval(countdownTimer);
});
</script>
<template>
<Head title="Xác minh khôi phục tài khoản"/>
<div class="page"><div class="card"><div class="icon"><i class="bi bi-shield-lock"/></div><h1>Xác minh email</h1><p>Mã OTP đã được gửi đến <strong>{{ props.email }}</strong>.</p><div v-if="props.status === 'recovery-otp-sent'" class="success">Mã OTP đã được gửi. Kiểm tra hộp thư đến và thư mục Spam.</div><div class="notice">Mã OTP chỉ có hiệu lực trong 1 phút. Bạn được nhập sai tối đa 5 lần cho mã hiện tại.</div><Form action="/account/recovery/verify" method="post" v-slot="{errors,processing}" class="vstack gap-3"><div><div class="label-row"><label>Mã OTP</label><span :class="['timer', {expired: remainingSeconds===0}]">{{ remainingSeconds > 0 ? formattedRemaining : 'Hết hạn' }}</span></div><input v-model="code" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" required placeholder="Nhập 6 chữ số" @input="normalizeCode"/><div v-if="errors.code" class="error">{{ errors.code }}</div><p class="attempts">Bạn còn {{ props.remainingAttempts }} lần thử.</p></div><button :disabled="processing || code.length !== 6 || remainingSeconds === 0" type="submit">{{ processing ? 'Đang xác minh...' : 'Xác minh OTP' }}</button></Form><div class="expiry">{{ remainingSeconds > 0 ? `Mã OTP hiện tại còn ${remainingSeconds} giây.` : 'Mã OTP đã hết hạn. Quay lại để yêu cầu mã mới.' }}</div><div class="limit" v-if="props.sendLimitReached">Bạn đã sử dụng đủ 5 lần gửi OTP. Từ lần thứ 6, vui lòng quay lại sau 60 phút.</div><div class="back"><Link href="/account/recovery">Quay lại nhập Email</Link><span> · </span><Link href="/login">Đăng nhập</Link></div></div></div>
</template>
<style scoped>
.page{min-height:70vh;display:grid;place-items:center;padding:32px 16px}.card{width:min(430px,100%);padding:30px;border:1px solid #e5e9f0;border-radius:22px;background:#fff;box-shadow:0 16px 45px rgba(16,24,40,.08)}.icon{display:grid;width:54px;height:54px;place-items:center;margin:0 auto 16px;border-radius:16px;background:#eff6ff;color:#2563eb;font-size:24px}.card h1{text-align:center;font-size:1.5rem;font-weight:900}.card p{text-align:center;color:#667085;font-size:.78rem}.notice,.success,.limit{margin:14px 0;padding:11px 12px;border-radius:10px;font-size:.7rem;line-height:1.55}.notice{border:1px solid #bfdbfe;background:#eff6ff;color:#1e40af}.success{border:1px solid #bbf7d0;background:#f0fdf4;color:#166534}.limit{border:1px solid #fde68a;background:#fffbeb;color:#92400e}.label-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px}.card label{display:block;font-size:.72rem;font-weight:800}.timer{font-size:.72rem;font-weight:800;color:#2563eb}.timer.expired{color:#dc2626}.card input{width:100%;padding:11px 12px;border:1px solid #d0d5dd;border-radius:10px;font-size:.9rem;letter-spacing:.18em;text-align:center}.card button{padding:11px;border:0;border-radius:10px;background:#2563eb;color:#fff;font-weight:800}.card button:disabled{opacity:.6}.error{margin-top:6px;color:#b42318;font-size:.68rem}.attempts{margin:6px 0 0!important;color:#667085!important;font-size:.68rem!important}.expiry{margin-top:12px;padding:10px;border-radius:9px;background:#f8fafc;color:#667085;text-align:center;font-size:.68rem;line-height:1.5}.back{text-align:center;margin-top:18px;font-size:.7rem}.back a{color:#2563eb;text-decoration:none}
</style>
