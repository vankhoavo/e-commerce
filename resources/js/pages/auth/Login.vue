<script setup lang="ts">
import { computed } from 'vue';
import { Form, Head, Link, router } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';

defineOptions({ layout: { title: 'Đăng nhập tài khoản', description: 'Đăng nhập để tiếp tục mua sắm tại TechStore.' } });

const friendlyAuthError = (message?: string) => message === 'auth.failed' ? 'Email hoặc mật khẩu không đúng.' : message;
const redirectTo = computed(() => {
    const value = new URLSearchParams(window.location.search).get('redirect') ?? '/';
    return value.startsWith('/') && !value.startsWith('//') ? value : '/';
});

function handleLoginSuccess() {
    router.visit(redirectTo.value);
}

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Đăng nhập" />

    <Transition name="success-toast" appear>
        <div v-if="status" class="registration-success-toast" role="status">
            <div class="success-toast-icon">
                <i :class="status === 'registration-success' || status === 'google-password-created' ? 'bi bi-check-lg' : 'bi bi-info-lg'" />
            </div>
            <div class="success-toast-content">
                <strong>{{ status === 'registration-success' ? 'Đăng ký thành công!' : status === 'google-password-created' ? 'Đã tạo mật khẩu thành công!' : 'Thông báo' }}</strong>
                <span>{{ status === 'registration-success' ? 'Tài khoản đã được tạo. Hãy đăng nhập để tiếp tục.' : status === 'google-password-created' ? 'Bạn có thể đăng nhập bằng Email + Mật khẩu hoặc tiếp tục dùng Google.' : status }}</span>
            </div>
        </div>
    </Transition>

    <a :href="`/auth/google?redirect=${encodeURIComponent(redirectTo)}`" class="btn btn-outline-dark w-100 auth-google-btn" aria-label="Đăng nhập bằng Google">
        <svg class="google-logo" viewBox="0 0 24 24" aria-hidden="true">
            <path fill="#4285F4" d="M21.35 12.27c0-.71-.06-1.4-.18-2.05H12v3.88h5.23a4.47 4.47 0 0 1-1.94 2.93v2.43h3.14c1.84-1.69 2.92-4.18 2.92-7.19z" />
            <path fill="#34A853" d="M12 21.82c2.63 0 4.84-.87 6.45-2.36l-3.14-2.43c-.87.58-1.98.92-3.31.92-2.54 0-4.69-1.72-5.46-4.03H3.3v2.51A9.74 9.74 0 0 0 12 21.82z" />
            <path fill="#FBBC05" d="M6.54 13.92A5.86 5.86 0 0 1 6.23 12c0-.67.11-1.32.31-1.92V7.57H3.3A9.8 9.8 0 0 0 2.25 12c0 1.59.38 3.1 1.05 4.43l3.24-2.51C7.31 7.77 9.46 6.05 12 6.05z" />
            <path fill="#EA4335" d="M12 6.05c1.43 0 2.71.49 3.72 1.46l2.79-2.79C16.83 3.07 14.63 2.18 12 2.18a9.74 9.74 0 0 0-8.7 5.39l3.24 2.51C7.31 7.77 9.46 6.05 12 6.05z" />
        </svg>
        <span>Tiếp tục với Google</span>
    </a>

    <div class="auth-divider"><span>Hoặc đăng nhập bằng Email</span></div>

    <Form v-bind="store.form()" :reset-on-success="['password']" @success="handleLoginSuccess" v-slot="{ errors, processing }" class="vstack gap-3">
        <div>
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" class="form-control form-control-lg" required autofocus autocomplete="email" placeholder="email@example.com" />
            <InputError :message="friendlyAuthError(errors.email)" />
        </div>

        <div>
            <label for="password" class="form-label mb-1">Mật khẩu</label>
            <PasswordInput id="password" name="password" class="form-control form-control-lg" required autocomplete="current-password" placeholder="Mật khẩu" />
            <div class="forgot-under-password">
                <Link href="/forgot-password"><i class="bi bi-key me-1" />Quên mật khẩu?</Link>
            </div>
            <InputError :message="friendlyAuthError(errors.password)" />
        </div>

        <div class="form-check">
            <input id="remember" name="remember" class="form-check-input" type="checkbox" />
            <label for="remember" class="form-check-label">Ghi nhớ đăng nhập</label>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100 mt-2" :disabled="processing">
            {{ processing ? 'Đang đăng nhập...' : 'Đăng nhập' }}
        </button>
    </Form>

    <div class="text-center auth-bottom">Chưa có tài khoản? <Link :href="register()">Đăng ký ngay</Link></div>
</template>

<style scoped>
.google-logo{width:19px;height:19px;flex:0 0 19px}.forgot-under-password{display:flex;justify-content:flex-end;margin-top:6px}.forgot-under-password a{color:#2563eb;font-size:10px;font-weight:750;text-decoration:none}.forgot-under-password a:hover{text-decoration:none}.registration-success-toast{display:flex;align-items:center;gap:12px;margin-bottom:16px;padding:12px 14px;border:1px solid #bfe8d1;border-radius:14px;background:linear-gradient(135deg,#f4fff8,#ecfbf2);box-shadow:0 8px 22px rgba(22,163,74,.09);animation:toast-in .35s ease}.success-toast-icon{display:grid;flex:0 0 36px;width:36px;height:36px;place-items:center;border-radius:50%;color:#fff;background:#16a34a}.success-toast-content{display:flex;min-width:0;flex-direction:column;gap:2px}.success-toast-content strong{color:#166534;font-size:12px;font-weight:850}.success-toast-content span{color:#4b6354;font-size:10px;line-height:1.5}.success-toast-enter-active,.success-toast-leave-active{transition:opacity .3s ease,transform .3s ease}.success-toast-enter-from,.success-toast-leave-to{opacity:0;transform:translateY(-8px)}
</style>
