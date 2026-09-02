<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { login } from '@/routes';

defineOptions({
    layout: {
        title: 'Xác minh OTP',
        description: 'Nhập mã OTP được gửi tới Email của bạn.',
    },
});

defineProps<{ email?: string; status?: string }>();
</script>

<template>
    <Head title="Xác minh OTP" />

    <div class="forgot-card">
        <div class="forgot-hero">
            <div class="forgot-hero-icon"><i class="bi bi-envelope-check-fill" /></div>
            <div>
                <span>TECHSTORE ACCOUNT</span>
                <h2>Xác minh Email</h2>
                <p>Nhập mã OTP 6 số đã được gửi tới <strong>{{ email || 'Email của bạn' }}</strong>.</p>
            </div>
        </div>

        <div v-if="status" class="forgot-status">
            <i class="bi bi-check-circle-fill" />
            <span>{{ status }}</span>
        </div>

        <Form action="/forgot-password/verify" method="post" v-slot="{ errors, processing }" class="forgot-form">
            <div>
                <label for="code">Mã OTP</label>
                <div class="forgot-input">
                    <i class="bi bi-shield-lock" />
                    <input
                        id="code"
                        type="text"
                        name="code"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        pattern="[0-9]{6}"
                        maxlength="6"
                        minlength="6"
                        required
                        autofocus
                        placeholder="123456"
                    />
                </div>
                <InputError :message="errors.code" />
            </div>

            <button type="submit" class="forgot-submit" :disabled="processing">
                <span v-if="processing" class="spinner-border spinner-border-sm" />
                <i v-else class="bi bi-check-circle-fill" />
                {{ processing ? 'Đang xác minh...' : 'Xác minh OTP' }}
            </button>
        </Form>

        <Form action="/forgot-password/verify/resend" method="post" v-slot="{ processing: resending }" class="resend-form">
            <button type="submit" class="resend-button" :disabled="resending">
                <span v-if="resending" class="spinner-border spinner-border-sm" />
                <i v-else class="bi bi-arrow-clockwise" />
                {{ resending ? 'Đang gửi lại...' : 'Gửi lại mã OTP' }}
            </button>
        </Form>

        <div class="forgot-note">
            <i class="bi bi-clock" />
            <span>Mã OTP có hiệu lực trong 10 phút và tối đa 5 lần nhập.</span>
        </div>

        <div class="forgot-back">
            <Link :href="login()"><i class="bi bi-arrow-left" /> Quay lại Đăng nhập</Link>
        </div>
    </div>
</template>

<style scoped>
.forgot-card{padding:26px;border:1px solid #e4e7ec;border-radius:18px;background:#fff;box-shadow:0 10px 30px rgba(16,24,40,.05)}
.forgot-hero{display:flex;align-items:center;gap:14px;padding-bottom:20px;border-bottom:1px solid #edf0f4}
.forgot-hero-icon{display:grid;flex:0 0 52px;width:52px;height:52px;place-items:center;border:1px solid #d8e5ff;border-radius:15px;color:#2563eb;background:#eff6ff;font-size:21px}
.forgot-hero span{color:#2563eb;font-size:8px;font-weight:900;letter-spacing:.16em}
.forgot-hero h2{margin:4px 0 0;color:#101828;font-size:20px;font-weight:850}
.forgot-hero p{margin:5px 0 0;color:#667085;font-size:10px;line-height:1.55}
.forgot-hero strong{color:#1d4ed8}
.forgot-form{display:grid;gap:15px;margin-top:20px}
.forgot-form label{display:block;margin-bottom:7px;color:#344054;font-size:11px;font-weight:800}
.forgot-input{display:flex;align-items:center;height:45px;padding:0 12px;border:1px solid #dfe4ec;border-radius:10px;background:#fff;transition:border-color .18s}
.forgot-input:focus-within{border-color:#8fb2f4;box-shadow:none}
.forgot-input i{margin-right:9px;color:#98a2b3}
.forgot-input input{width:100%;border:0;outline:0;color:#344054;background:transparent;font-size:11px;letter-spacing:.18em}
.forgot-submit{display:flex;align-items:center;justify-content:center;gap:7px;height:43px;border:0;border-radius:10px;color:#fff;background:linear-gradient(135deg,#2563eb,#4f46e5);box-shadow:0 8px 18px rgba(37,99,235,.18);font-size:11px;font-weight:850}
.forgot-submit:disabled,.resend-button:disabled{opacity:.6}
.resend-form{margin-top:11px}
.resend-button{display:flex;align-items:center;justify-content:center;gap:7px;width:100%;height:40px;border:1px solid #dbe4f0;border-radius:10px;color:#2563eb;background:#f8fbff;font-size:10px;font-weight:800}
.forgot-note{display:flex;align-items:center;gap:7px;margin-top:15px;padding:10px;border-radius:9px;color:#667085;background:#f8fafc;font-size:9px}
.forgot-note i{color:#2563eb}
.forgot-status{display:flex;align-items:center;gap:8px;margin-top:16px;padding:10px 12px;border:1px solid #b7ebcc;border-radius:10px;color:#067647;background:#f0fdf4;font-size:10px}
.forgot-back{margin-top:17px;padding-top:15px;border-top:1px solid #edf0f4;text-align:center}
.forgot-back a{color:#2563eb;font-size:10px;font-weight:800;text-decoration:none}
</style>
