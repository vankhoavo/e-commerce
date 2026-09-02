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
<Transition name="success-toast" appear><div v-if="status" class="registration-success-toast" role="status"><div class="success-toast-icon"><i :class="status === 'registration-success' || status === 'google-password-created' ? 'bi bi-check-lg' : 'bi bi-info-lg'"/></div><div class="success-toast-content"><strong>{{ status === 'registration-success' ? 'Đăng ký thành công!' : status === 'google-password-created' ? 'Đã tạo mật khẩu thành công!' : 'Thông báo' }}</strong><span>{{ status === 'registration-success' ? 'Tài khoản đã được tạo. Hãy đăng nhập để tiếp tục.' : status === 'google-password-created' ? 'Bạn có thể đăng nhập bằng Email + Mật khẩu hoặc tiếp tục dùng Google.' : status }}</span></div></div></Transition>
<TeamInvitationAlert v-if="teamInvitation" :invitation="teamInvitation" action="Đăng nhập"/>
<a href="/auth/google" class="btn btn-outline-dark w-100 auth-google-btn"><i class="bi bi-google me-2"/>Tiếp tục với Google</a>
<div class="auth-divider"><span>Hoặc đăng nhập bằng Email</span></div>
<Form v-bind="store.form()" :reset-on-success="['password']" v-slot="{ errors, processing }" class="vstack gap-3">
<div><label for="email" class="form-label">Email</label><input id="email" type="email" name="email" class="form-control form-control-lg" required autofocus autocomplete="email" placeholder="email@example.com"/><InputError :message="errors.email"/></div>
<div><label for="password" class="form-label mb-1">Mật khẩu</label><input id="password" type="password" name="password" class="form-control form-control-lg" required autocomplete="current-password" placeholder="Mật khẩu"/><div v-if="canResetPassword" class="forgot-under-password"><Link :href="request()"><i class="bi bi-key me-1"/>Quên mật khẩu?</Link></div><InputError :message="errors.password"/></div>
<div class="form-check"><input id="remember" name="remember" class="form-check-input" type="checkbox"/><label for="remember" class="form-check-label">Ghi nhớ đăng nhập</label></div>
<button type="submit" class="btn btn-primary btn-lg w-100 mt-2" :disabled="processing">{{ processing ? 'Đang đăng nhập...' : 'Đăng nhập' }}</button>
</Form>
<div class="text-center auth-bottom">Chưa có tài khoản? <Link :href="register({ query: { invitation: teamInvitation?.code } })">Đăng ký ngay</Link></div>
</template>
<style scoped>
.forgot-under-password{display:flex;justify-content:flex-end;margin-top:6px}.forgot-under-password a{color:#2563eb;font-size:10px;font-weight:750;text-decoration:none}.forgot-under-password a:hover{text-decoration:underline}.registration-success-toast{display:flex;align-items:center;gap:12px;margin-bottom:16px;padding:12px 14px;border:1px solid #bfe8d1;border-radius:14px;background:linear-gradient(135deg,#f4fff8,#ecfbf2);box-shadow:0 8px 22px rgba(22,163,74,.09);animation:toast-in .35s ease}.success-toast-icon{display:grid;flex:0 0 36px;width:36px;height:36px;place-items:center;border-radius:50%;color:#fff;background:#16a34a}.success-toast-content{display:flex;min-width:0;flex-direction:column;gap:2px}.success-toast-content strong{color:#166534;font-size:12px;font-weight:850}.success-toast-content span{color:#4b6354;font-size:10px;line-height:1.5}.success-toast-enter-active,.success-toast-leave-active{transition:opacity .3s ease,transform .3s ease}.success-toast-enter-from,.success-toast-leave-to{opacity:0;transform:translateY(-8px)}
</style>
