<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import { computed } from 'vue';
import { edit } from '@/routes/profile';

const page = usePage();
const user = computed(() => page.props.auth.user as any);
const logout = () => window.location.assign('/logout');
</script>

<template>
    <Head title="Thông tin cá nhân" />
    <div class="container py-4 py-lg-5 profile-page">
        <div class="profile-header mb-4">
            <div><span class="section-kicker">TÀI KHOẢN</span><h1>Thông tin cá nhân</h1><p>Quản lý thông tin, bảo mật và địa chỉ email của bạn.</p></div>
            <form method="post" action="/logout"><input type="hidden" name="_token" :value="(page.props as any).csrf_token ?? ''"><button type="submit" class="btn btn-outline-danger profile-logout"><i class="bi bi-box-arrow-right me-2"/>Đăng xuất</button></form>
        </div>

        <div v-if="page.props.status" class="alert alert-success border-0 shadow-sm rounded-4"><i class="bi bi-check-circle-fill me-2"/> <span v-if="page.props.status === 'profile-updated'">Thông tin cá nhân đã được cập nhật.</span><span v-else-if="page.props.status === 'email-changed'">Địa chỉ email đã được thay đổi và xác thực thành công.</span><span v-else-if="page.props.status === 'email-verified'">Email đã được xác thực thành công.</span><span v-else>Thao tác đã được thực hiện thành công.</span></div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="profile-card profile-summary h-100">
                    <div class="profile-avatar"><img v-if="user.avatar" :src="user.avatar" alt="Ảnh đại diện"><i v-else class="bi bi-person-fill"/></div>
                    <h2>{{ user.name }}</h2><p>{{ user.email }}</p>
                    <span class="badge rounded-pill" :class="user.email_verified_at ? 'text-bg-success' : 'text-bg-warning'">{{ user.email_verified_at ? 'Email đã xác thực' : 'Chưa xác thực email' }}</span>
                    <div class="profile-summary-list mt-4"><div><i class="bi bi-person-vcard"/><span><small>Vai trò</small><strong>{{ user.role?.label ?? user.role ?? 'Khách hàng' }}</strong></span></div><div><i class="bi bi-shield-check"/><span><small>Trạng thái</small><strong>{{ user.is_active ? 'Tài khoản đang hoạt động' : 'Tài khoản bị khóa' }}</strong></span></div></div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="profile-card">
                    <div class="profile-card-title"><span class="profile-title-icon"><i class="bi bi-person-lines-fill"/></span><div><h2>Thông tin cơ bản</h2><p>Cập nhật thông tin cá nhân của bạn.</p></div></div>
                    <Form v-bind="ProfileController.update.form()" v-slot="{ errors, processing }" class="row g-3 mt-1">
                        <div class="col-12"><label class="form-label">Họ và tên</label><input name="name" class="form-control form-control-lg" :value="user.name" required autocomplete="name"><InputError :message="errors.name"/></div>
                        <div class="col-md-6"><label class="form-label">Ngày tháng năm sinh</label><input name="birth_date" type="date" class="form-control form-control-lg" :value="user.birth_date ? String(user.birth_date).slice(0,10) : ''"><InputError :message="errors.birth_date"/></div>
                        <div class="col-md-6"><label class="form-label">Số điện thoại</label><input name="phone" type="tel" class="form-control form-control-lg" :value="user.phone ?? ''" placeholder="09xxxxxxxx"><InputError :message="errors.phone"/></div>
                        <div class="col-12"><label class="form-label">Địa chỉ Email</label><div class="input-group input-group-lg"><input name="email" type="email" class="form-control" :value="user.email" readonly><span class="input-group-text text-success"><i class="bi bi-patch-check-fill"/></span></div><small class="text-secondary">Email chỉ được thay đổi thông qua bước xác thực mã OTP.</small></div>
                        <div class="col-12 pt-2"><button class="btn btn-primary px-4" :disabled="processing"><i class="bi bi-check2-circle me-2"/>{{ processing ? 'Đang lưu...' : 'Lưu thay đổi' }}</button></div>
                    </Form>
                </div>

                <div class="profile-card mt-4">
                    <div class="profile-card-title"><span class="profile-title-icon"><i class="bi bi-envelope-at-fill"/></span><div><h2>Thay đổi Email</h2><p>Email mới bắt buộc phải xác thực bằng mã OTP.</p></div></div>
                    <Form action="/settings/email/request" method="post" class="row g-3 mt-1"><div class="col-md-8"><label class="form-label">Email mới</label><input name="email" type="email" class="form-control form-control-lg" placeholder="email-moi@example.com" required></div><div class="col-md-4 d-flex align-items-end"><button class="btn btn-outline-primary btn-lg w-100"><i class="bi bi-send me-2"/>Gửi mã OTP</button></div></Form>
                </div>
            </div>
        </div>
        <div class="mt-4"><DeleteUser /></div>
    </div>
</template>
