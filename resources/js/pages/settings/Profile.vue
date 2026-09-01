<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user as any);
const roleLabel = computed(() => {
    const role = user.value?.role?.label ?? user.value?.role;
    const labels: Record<string, string> = {
        customer: 'Khách hàng',
        admin: 'Quản trị viên',
        staff: 'Nhân viên',
        user: 'Người dùng',
    };
    return labels[String(role ?? '').toLowerCase()] ?? role ?? 'Khách hàng';
});
</script>

<template>
    <Head title="Thông tin cá nhân" />
    <div class="profile-page">
        <div class="profile-header">
            <div>
                <span class="section-kicker">TÀI KHOẢN CỦA BẠN</span>
                <h1>Thông tin cá nhân</h1>
                <p>Quản lý thông tin cá nhân và bảo mật tài khoản tại TechStore.</p>
            </div>
            <Form action="/logout" method="post">
                <button type="submit" class="btn btn-outline-danger profile-logout">
                    <i class="bi bi-box-arrow-right me-2" />Đăng xuất
                </button>
            </Form>
        </div>

        <div v-if="page.props.status" class="profile-alert">
            <i class="bi bi-check-circle-fill" />
            <span v-if="page.props.status === 'profile-updated'">Thông tin cá nhân đã được cập nhật.</span>
            <span v-else-if="page.props.status === 'email-changed'">Email mới đã được xác thực và cập nhật thành công.</span>
            <span v-else-if="page.props.status === 'email-verified'">Email của bạn đã được xác thực thành công.</span>
            <span v-else>Thao tác đã được thực hiện thành công.</span>
        </div>

        <div class="row g-4">
            <div class="col-xl-4">
                <div class="profile-card profile-summary h-100">
                    <div class="profile-summary-top">
                        <div class="profile-avatar">
                            <img v-if="user.avatar" :src="user.avatar" alt="Ảnh đại diện">
                            <i v-else class="bi bi-person-fill" />
                        </div>
                    </div>
                    <h2>{{ user.name }}</h2>
                    <p class="profile-email">{{ user.email }}</p>
                    <div class="profile-status-row">
                        <span class="profile-verified" :class="user.email_verified_at ? 'verified' : 'unverified'">
                            <i :class="['bi', user.email_verified_at ? 'bi-patch-check-fill' : 'bi-exclamation-circle-fill']" />
                            {{ user.email_verified_at ? 'Email đã xác thực' : 'Chưa xác thực Email' }}
                        </span>
                        <Link v-if="!user.email_verified_at" href="/verify-email-otp" class="verify-email-btn">
                            Xác thực ngay <i class="bi bi-arrow-right" />
                        </Link>
                    </div>

                    <div class="profile-summary-list mt-4">
                        <div>
                            <span class="profile-summary-icon"><i class="bi bi-person-badge" /></span>
                            <span><small>Vai trò</small><strong>{{ roleLabel }}</strong></span>
                        </div>
                        <div>
                            <span class="profile-summary-icon"><i class="bi bi-shield-check" /></span>
                            <span><small>Trạng thái tài khoản</small><strong>{{ user.is_active ? 'Đang hoạt động' : 'Đã khóa' }}</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <section class="profile-card">
                    <div class="profile-card-title">
                        <span class="profile-title-icon"><i class="bi bi-person-lines-fill" /></span>
                        <div><h2>Thông tin cơ bản</h2><p>Cập nhật họ tên, ngày sinh và số điện thoại.</p></div>
                    </div>
                    <Form v-bind="ProfileController.update.form()" v-slot="{ errors, processing }" class="row g-3 mt-1">
                        <div class="col-12">
                            <label class="form-label">Họ và tên</label>
                            <input name="name" class="form-control form-control-lg" :value="user.name" required autocomplete="name">
                            <InputError :message="errors.name" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ngày tháng năm sinh</label>
                            <input name="birth_date" type="date" class="form-control form-control-lg" :value="user.birth_date ? String(user.birth_date).slice(0,10) : ''">
                            <InputError :message="errors.birth_date" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Số điện thoại</label>
                            <input name="phone" type="tel" class="form-control form-control-lg" :value="user.phone ?? ''" placeholder="09xxxxxxxx" autocomplete="tel">
                            <InputError :message="errors.phone" />
                        </div>
                        <div class="col-12 pt-2">
                            <button class="btn btn-primary profile-save" :disabled="processing">
                                <i class="bi bi-check2-circle me-2" />{{ processing ? 'Đang lưu...' : 'Lưu thay đổi' }}
                            </button>
                        </div>
                    </Form>
                </section>

                <section class="profile-card email-change-card mt-4">
                    <div class="email-change-head">
                        <div class="profile-title-icon email-icon"><i class="bi bi-envelope-at-fill" /></div>
                        <div><span class="section-kicker">BẢO MẬT EMAIL</span><h2>Địa chỉ Email</h2><p>Email hiện tại được dùng để đăng nhập và khôi phục tài khoản.</p></div>
                    </div>
                    <div class="current-email-box">
                        <div class="email-status-icon"><i class="bi bi-envelope-check" /></div>
                        <div class="flex-grow-1"><small>Email hiện tại</small><strong>{{ user.email }}</strong></div>
                        <span class="email-status-badge" :class="user.email_verified_at ? 'verified' : 'unverified'">
                            <i :class="['bi', user.email_verified_at ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill']" />
                            {{ user.email_verified_at ? 'Đã xác thực' : 'Chưa xác thực' }}
                        </span>
                    </div>
                    <div class="email-change-divider"><span>Đổi địa chỉ Email</span></div>
                    <Form action="/settings/email/request" method="post" class="row g-3" v-slot="{ errors, processing }">
                        <div class="col-lg-8">
                            <label class="form-label">Email mới</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text"><i class="bi bi-envelope" /></span>
                                <input name="email" type="email" class="form-control" placeholder="email-moi@example.com" required autocomplete="email">
                            </div>
                            <InputError :message="errors.email" />
                        </div>
                        <div class="col-lg-4 d-flex align-items-end">
                            <button class="btn btn-primary btn-lg w-100" :disabled="processing">
                                <i class="bi bi-shield-check me-2" />{{ processing ? 'Đang gửi...' : 'Gửi mã xác thực' }}
                            </button>
                        </div>
                    </Form>
                    <div class="email-security-note">
                        <i class="bi bi-info-circle-fill" />
                        <span>Email mới <strong>chưa được thay đổi ở bước này</strong>. Hệ thống chỉ cập nhật sau khi bạn nhập đúng OTP 6 số được gửi đến Email mới.</span>
                    </div>
                </section>
            </div>
        </div>

        <div class="mt-4"><DeleteUser /></div>
    </div>
</template>

<style>
.profile-summary-top {
    display: flex;
    justify-content: center;
    margin: -30px -24px 22px;
    padding: 28px 24px 22px;
    border-bottom: 1px solid #edf0f4;
    border-radius: 22px 22px 0 0;
    background: linear-gradient(145deg, #eff6ff 0%, #f8f5ff 100%);
}
.profile-avatar {
    display: grid;
    width: 86px;
    height: 86px;
    place-items: center;
    overflow: hidden;
    border: 4px solid #fff;
    border-radius: 24px;
    color: #fff;
    background: linear-gradient(135deg,#2563eb,#7c3aed);
    font-size: 2.35rem;
    box-shadow: 0 12px 28px rgba(37,99,235,.18);
}
.profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
.profile-status-row {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
}
.verify-email-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 10px;
    border: 1px solid #bfdbfe;
    border-radius: 9px;
    color: #1d4ed8;
    background: #eff6ff;
    font-size: .68rem;
    font-weight: 800;
}
.verify-email-btn:hover { color: #1e40af; background: #dbeafe; }
.profile-summary-icon {
    display: grid;
    width: 36px;
    height: 36px;
    flex: 0 0 36px;
    place-items: center;
    border-radius: 10px;
    color: #2563eb;
    background: #eff6ff;
}
.profile-summary-list > div { padding: 10px !important; }
.profile-summary-list > div > span:last-child { min-width: 0; }
</style>
