<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TeamInvitationAlert from '@/components/TeamInvitationAlert.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';
import type { TeamInvitationContext } from '@/types';

defineProps<{ passwordRules: string; teamInvitation?: TeamInvitationContext | null }>();
defineOptions({ layout: { title: 'Tạo tài khoản', description: 'Đăng ký tài khoản để bắt đầu mua sắm tại TechStore.' } });
</script>
<template>
<Head title="Đăng ký"/><TeamInvitationAlert v-if="teamInvitation" :invitation="teamInvitation" action="Đăng ký"/>
<div class="register-note"><i class="bi bi-gift-fill"/><div><strong>Ưu đãi thành viên mới</strong><span>Đăng ký hôm nay để nhận thông tin khuyến mãi sớm nhất.</span></div></div>
<Form v-bind="store.form()" :reset-on-success="['password','password_confirmation']" v-slot="{ errors, processing }" class="vstack gap-3">
<div><label for="name" class="form-label">Họ và tên</label><input id="name" type="text" name="name" class="form-control form-control-lg" required autofocus autocomplete="name" placeholder="Nguyễn Văn A"/><InputError :message="errors.name"/></div>
<div><label for="email" class="form-label">Email</label><input id="email" type="email" name="email" class="form-control form-control-lg" required autocomplete="email" placeholder="email@example.com"/><InputError :message="errors.email"/></div>
<div><label for="password" class="form-label">Mật khẩu</label><input id="password" type="password" name="password" class="form-control form-control-lg" required autocomplete="new-password" placeholder="Nhập mật khẩu" :passwordrules="passwordRules"/><InputError :message="errors.password"/></div>
<div><label for="password_confirmation" class="form-label">Xác nhận mật khẩu</label><input id="password_confirmation" type="password" name="password_confirmation" class="form-control form-control-lg" required autocomplete="new-password" placeholder="Nhập lại mật khẩu" :passwordrules="passwordRules"/><InputError :message="errors.password_confirmation"/></div>
<button type="submit" class="btn btn-primary btn-lg w-100 mt-2" :disabled="processing">{{ processing ? 'Đang tạo tài khoản...' : 'Tạo tài khoản' }}</button>
</Form>
<div class="text-center auth-bottom">Đã có tài khoản? <Link :href="teamInvitation ? login.url({ query: { invitation: teamInvitation.code } }) : login()">Đăng nhập</Link></div>
</template>
