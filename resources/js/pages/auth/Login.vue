<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TeamInvitationAlert from '@/components/TeamInvitationAlert.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import type { TeamInvitationContext } from '@/types';

defineOptions({ layout: { title: 'Đăng nhập tài khoản', description: 'Đăng nhập để tiếp tục mua sắm tại TechStore.' } });
defineProps<{ status?: string; canResetPassword: boolean; teamInvitation?: TeamInvitationContext | null }>();
</script>
<template>
<Head title="Đăng nhập"/>
<div v-if="status === 'registration-success'" class="alert alert-success border-0 rounded-3 shadow-sm"><i class="bi bi-check-circle-fill me-2"/>Đăng ký thành công! Hãy đăng nhập để tiếp tục.</div>
<div v-else-if="status" class="alert alert-success border-0 rounded-3 shadow-sm">{{ status }}</div>
<TeamInvitationAlert v-if="teamInvitation" :invitation="teamInvitation" action="Đăng nhập"/>
<a href="/auth/google" class="btn btn-outline-dark w-100 auth-google-btn"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M21.35 12.23c0-.79-.07-1.55-.23-2.27H12v4.3h5.22a4.46 4.46 0 0 1-1.94 2.93v2.43h3.14c1.84-1.7 2.93-4.2 2.93-7.39Z"/><path fill="#34A853" d="M12 21.96c2.63 0 4.84-.87 6.45-2.34l-3.14-2.43c-.87.58-1.98.92-3.31.92-2.54 0-4.69-1.72-5.46-4.03H3.3v2.51A9.74 9.74 0 0 0 12 21.96Z"/><path fill="#FBBC05" d="M6.54 14.08A5.85 5.85 0 0 1 6.23 12c0-.72.12-1.42.31-2.08V7.41H3.3A9.99 9.99 0 0 0 2.25 12c0 1.66.4 3.23 1.05 4.59l3.24-2.51Z"/><path fill="#EA4335" d="M12 5.89c1.43 0 2.71.49 3.72 1.45l2.79-2.79C16.84 2.98 14.63 2.04 12 2.04a9.74 9.74 0 0 0-8.7 5.37l3.24 2.51C7.31 7.61 9.46 5.89 12 5.89Z"/></svg>Tiếp tục với Google</a>
<div class="auth-divider"><span>Hoặc đăng nhập bằng Email</span></div>
<Form v-bind="store.form()" :reset-on-success="['password']" v-slot="{ errors, processing }" class="vstack gap-3">
<div><label for="email" class="form-label">Email</label><input id="email" type="email" name="email" class="form-control form-control-lg" required autofocus autocomplete="email" placeholder="email@example.com"/><InputError :message="errors.email"/></div>
<div><div class="d-flex justify-content-between align-items-center"><label for="password" class="form-label">Mật khẩu</label><Link v-if="canResetPassword" :href="request()" class="small text-primary fw-semibold">Quên mật khẩu?</Link></div><input id="password" type="password" name="password" class="form-control form-control-lg" required autocomplete="current-password" placeholder="Mật khẩu"/><InputError :message="errors.password"/></div>
<div class="form-check"><input id="remember" name="remember" class="form-check-input" type="checkbox"/><label for="remember" class="form-check-label">Ghi nhớ đăng nhập</label></div>
<button type="submit" class="btn btn-primary btn-lg w-100 mt-2" :disabled="processing">{{ processing ? 'Đang đăng nhập...' : 'Đăng nhập' }}</button>
</Form>
<div class="text-center auth-bottom">Chưa có tài khoản? <Link :href="register({ query: { invitation: teamInvitation?.code } })">Đăng ký ngay</Link></div>
</template>
