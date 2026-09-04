<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const email = computed(() => (page.props as any).email ?? '');
const expiresAt = computed(() => (page.props as any).expiresAt ?? null);
const hasPendingCode = computed(() => Boolean((page.props as any).hasPendingCode));
const code = ref('');
const showResend = ref(false);

function normalizeCode(event: Event) {
    const input = event.target as HTMLInputElement;
    code.value = input.value.replace(/\D/g, '').slice(0, 6);
    input.value = code.value;
}

function openResend() {
    showResend.value = true;
}
</script>

<template>
    <Head title="Xác thực Email" />
    <div class="auth-otp-page">
        <div class="otp-icon"><i class="bi bi-envelope-check-fill" /></div>
        <span class="section-kicker">XÁC THỰC TÀI KHOẢN</span>
        <h1>Xác thực Email</h1>
        <p>Chúng tôi đã gửi một mã OTP 6 số đến <strong>{{ email }}</strong>. Mã chỉ có hiệu lực trong 10 phút.</p>

        <div v-if="page.props.status === 'verification-code-sent'" class="alert alert-success py-2 small mt-3">
            Mã OTP mới đã được gửi. Mã cũ đã hết hiệu lực.
        </div>

        <Form action="/verify-email-otp" method="post" v-slot="{ errors, processing }" class="mt-4">
            <label class="form-label">Mã OTP</label>
            <input
                v-model="code"
                name="code"
                inputmode="numeric"
                maxlength="6"
                autocomplete="one-time-code"
                class="form-control form-control-lg text-center otp-input"
                placeholder="••••••"
                required
                autofocus
                @input="normalizeCode"
            />
            <div v-if="errors.code" class="text-danger small mt-2">{{ errors.code }}</div>
            <button class="btn btn-primary btn-lg w-100 mt-3" :disabled="processing || code.length !== 6">
                {{ processing ? 'Đang xác thực...' : 'Xác thực' }}
            </button>
        </Form>

        <button type="button" class="btn btn-link w-100 mt-2" @click="openResend">
            Gửi lại mã OTP
        </button>

        <div v-if="showResend" class="mt-2">
            <p class="small text-muted mb-2">Chỉ gửi lại khi bạn chưa nhận được mã hoặc mã đã hết hạn.</p>
            <Form action="/verify-email-otp/resend" method="post" v-slot="{ processing }">
                <button class="btn btn-outline-primary w-100" type="submit" :disabled="processing">
                    {{ processing ? 'Đang gửi mã...' : 'Gửi một mã OTP mới' }}
                </button>
            </Form>
        </div>

        <Form action="/verify-email-otp/defer" method="post" class="mt-3">
            <button class="btn btn-outline-secondary w-100" type="submit">
                Tôi sẽ xác thực lại sau
            </button>
        </Form>

        <p v-if="hasPendingCode && expiresAt" class="small text-muted text-center mt-3 mb-0">
            Mã hiện tại vẫn đang có hiệu lực. Bạn có thể xác thực ngay hoặc xác thực lại sau trong Thông tin cá nhân.
        </p>

        <Link href="/" class="auth-back"><i class="bi bi-arrow-left me-1" />Về trang chủ</Link>
    </div>
</template>
