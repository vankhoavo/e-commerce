<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { ChevronRight, KeyRound, LockKeyhole, Mail, Palette, ShieldCheck, UserRound, UsersRound } from '@lucide/vue';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import CreateTeamModal from '@/components/CreateTeamModal.vue';
import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import ManagePasskeys from '@/components/ManagePasskeys.vue';
import ManageTwoFactor from '@/components/ManageTwoFactor.vue';
import PasswordInput from '@/components/PasswordInput.vue';

type Props = {
    passwordRules: string;
    canManageTwoFactor: boolean;
    canManagePasskeys: boolean;
    twoFactorEnabled: boolean;
    requiresConfirmation: boolean;
    passkeys: any[];
    teams: any[];
};

const props = defineProps<Props>();
const page = usePage();
const user = computed(() => page.props.auth.user as any);
const roleLabel = computed(() => ({ customer: 'Khách hàng', admin: 'Quản trị viên', staff: 'Nhân viên', user: 'Người dùng' }[String(user.value?.role?.label ?? user.value?.role ?? 'customer').toLowerCase()] ?? 'Khách hàng'));
</script>

<template>
    <Head title="Cài đặt tài khoản" />
    <div class="settings-dashboard">
        <!-- THÔNG TIN CÁ NHÂN -->
        <section class="settings-view settings-profile-block">
            <div class="view-header">
                <div class="view-title-wrap">
                    <div class="view-icon profile-icon"><UserRound :size="21" /></div>
                    <div>
                        <span class="section-kicker">TÀI KHOẢN</span>
                        <h2>Thông tin cá nhân</h2>
                        <p>Quản lý thông tin cá nhân và địa chỉ email của bạn.</p>
                    </div>
                </div>
                <Form action="/logout" method="post">
                    <button type="submit" class="settings-logout"><i class="bi bi-box-arrow-right" /> Đăng xuất</button>
                </Form>
            </div>

            <div v-if="page.props.status" class="settings-success">
                <i class="bi bi-check-circle-fill" />
                <span>{{ page.props.status === 'profile-updated' ? 'Thông tin cá nhân đã được cập nhật.' : page.props.status === 'email-changed' ? 'Email mới đã được xác thực và cập nhật thành công.' : page.props.status === 'email-verified' ? 'Email của bạn đã được xác thực thành công.' : 'Thao tác đã được thực hiện thành công.' }}</span>
            </div>

            <div class="profile-layout">
                <aside class="account-card">
                    <div class="account-cover"></div>
                    <div class="account-avatar">
                        <img v-if="user.avatar" :src="user.avatar" alt="Ảnh đại diện" />
                        <span v-else>{{ String(user.name ?? 'U').charAt(0).toUpperCase() }}</span>
                    </div>
                    <div class="account-body">
                        <h3>{{ user.name }}</h3>
                        <p class="account-email">{{ user.email }}</p>
                        <span class="status-badge" :class="user.email_verified_at ? 'is-verified' : 'is-warning'">
                            <i :class="['bi', user.email_verified_at ? 'bi-patch-check-fill' : 'bi-exclamation-circle-fill']" />
                            {{ user.email_verified_at ? 'Email đã xác thực' : 'Chưa xác thực email' }}
                        </span>
                        <Link v-if="!user.email_verified_at" href="/verify-email-otp" class="verify-link">Xác thực ngay <ChevronRight :size="14" /></Link>
                        <div class="account-facts">
                            <div><span><UserRound :size="15" /></span><section><small>Vai trò</small><strong>{{ roleLabel }}</strong></section></div>
                            <div><span><ShieldCheck :size="15" /></span><section><small>Trạng thái</small><strong>{{ user.is_active ? 'Đang hoạt động' : 'Đã khóa' }}</strong></section></div>
                        </div>
                    </div>
                </aside>

                <div class="profile-main-stack">
                    <section class="settings-card">
                        <div class="card-heading"><span class="card-heading-icon"><UserRound :size="18" /></span><div><h3>Thông tin cơ bản</h3><p>Cập nhật thông tin hiển thị trên tài khoản.</p></div></div>
                        <Form v-bind="ProfileController.update.form()" v-slot="{ errors, processing }" class="settings-form">
                            <div class="field full"><label>Họ và tên</label><input name="name" :value="user.name" required autocomplete="name" placeholder="Nhập họ và tên" /><InputError :message="errors.name" /></div>
                            <div class="field"><label>Ngày sinh</label><input name="birth_date" type="date" :value="user.birth_date ? String(user.birth_date).slice(0,10) : ''" /><InputError :message="errors.birth_date" /></div>
                            <div class="field"><label>Số điện thoại</label><input name="phone" type="tel" :value="user.phone ?? ''" placeholder="09xxxxxxxx" autocomplete="tel" /><InputError :message="errors.phone" /></div>
                            <div class="form-actions"><button class="primary-button" :disabled="processing"><i class="bi bi-check2" />{{ processing ? 'Đang lưu...' : 'Lưu thay đổi' }}</button></div>
                        </Form>
                    </section>

                    <section class="settings-card email-card">
                        <div class="card-heading"><span class="card-heading-icon"><Mail :size="18" /></span><div><h3>Địa chỉ email</h3><p>Email dùng để đăng nhập và khôi phục tài khoản.</p></div></div>
                        <div class="current-email"><span class="email-icon"><Mail :size="17" /></span><div><small>Email hiện tại</small><strong>{{ user.email }}</strong></div><span class="email-state" :class="user.email_verified_at ? 'is-verified' : 'is-warning'">{{ user.email_verified_at ? 'Đã xác thực' : 'Chưa xác thực' }}</span></div>
                        <div class="email-divider"><span>Đổi địa chỉ email</span></div>
                        <Form action="/settings/email/request" method="post" class="email-form" v-slot="{ errors, processing }">
                            <div class="field"><label>Email mới</label><input name="email" type="email" placeholder="email-moi@example.com" required autocomplete="email" /><InputError :message="errors.email" /></div>
                            <button class="primary-button" :disabled="processing"><i class="bi bi-shield-check" />{{ processing ? 'Đang gửi...' : 'Gửi mã xác thực' }}</button>
                        </Form>
                    </section>
                </div>
            </div>
        </section>

        <!-- BẢO MẬT -->
        <section class="settings-view settings-tab-panel security-view">
            <div class="view-header">
                <div class="view-title-wrap"><div class="view-icon security-icon"><ShieldCheck :size="21" /></div><div><span class="section-kicker">BẢO MẬT TÀI KHOẢN</span><h2>Bảo mật</h2><p>Kiểm soát mật khẩu, xác thực hai lớp và phương thức đăng nhập.</p></div></div>
            </div>

            <section class="settings-card password-card">
                <div class="card-heading"><span class="card-heading-icon"><LockKeyhole :size="18" /></span><div><h3>Đổi mật khẩu</h3><p>Nên sử dụng mật khẩu dài, riêng biệt và khó đoán.</p></div></div>
                <Form v-bind="SecurityController.update.form()" :options="{ preserveScroll: true }" reset-on-success :reset-on-error="['password','password_confirmation','current_password']" v-slot="{ errors, processing }" class="settings-form password-form">
                    <div class="field full"><label>Mật khẩu hiện tại</label><PasswordInput name="current_password" autocomplete="current-password" placeholder="Nhập mật khẩu hiện tại" /><InputError :message="errors.current_password" /></div>
                    <div class="field"><label>Mật khẩu mới</label><PasswordInput name="password" autocomplete="new-password" placeholder="Nhập mật khẩu mới" :passwordrules="props.passwordRules" /><InputError :message="errors.password" /></div>
                    <div class="field"><label>Xác nhận mật khẩu</label><PasswordInput name="password_confirmation" autocomplete="new-password" placeholder="Nhập lại mật khẩu" :passwordrules="props.passwordRules" /><InputError :message="errors.password_confirmation" /></div>
                    <div class="password-footer"><div class="security-tip"><span><i class="bi bi-shield-check" /></span><div><strong>Mẹo bảo mật</strong><small>Kết hợp chữ hoa, chữ thường, số và ký tự đặc biệt.</small></div></div><button class="primary-button" :disabled="processing"><i class="bi bi-check2" />{{ processing ? 'Đang cập nhật...' : 'Cập nhật mật khẩu' }}</button></div>
                </Form>
            </section>

            <div class="security-grid">
                <section v-if="props.canManageTwoFactor" class="settings-card feature-card">
                    <div class="feature-head"><span class="feature-icon blue"><ShieldCheck :size="19" /></span><div><h3>Xác thực 2 lớp</h3><p>Bảo vệ tài khoản bằng mã xác thực từ điện thoại.</p></div></div>
                    <div class="feature-content"><ManageTwoFactor :canManageTwoFactor="props.canManageTwoFactor" :requiresConfirmation="props.requiresConfirmation" :twoFactorEnabled="props.twoFactorEnabled" /></div>
                </section>
                <section v-if="props.canManagePasskeys" class="settings-card feature-card">
                    <div class="feature-head"><span class="feature-icon purple"><KeyRound :size="19" /></span><div><h3>Passkey</h3><p>Đăng nhập không cần mật khẩu bằng thiết bị tin cậy.</p></div></div>
                    <div class="feature-content"><ManagePasskeys :canManagePasskeys="props.canManagePasskeys" :passkeys="props.passkeys" /></div>
                </section>
            </div>
        </section>

        <!-- NHÓM -->
        <section class="settings-view settings-tab-panel teams-view">
            <div class="view-header">
                <div class="view-title-wrap"><div class="view-icon teams-icon"><UsersRound :size="21" /></div><div><span class="section-kicker">KHÔNG GIAN LÀM VIỆC</span><h2>Nhóm</h2><p>Quản lý nhóm và không gian cộng tác của bạn.</p></div></div>
                <CreateTeamModal><button class="primary-button"><i class="bi bi-plus-lg" />Tạo nhóm</button></CreateTeamModal>
            </div>
            <div v-if="props.teams.length" class="teams-list">
                <div v-for="team in props.teams" :key="team.id" class="team-row">
                    <div class="team-avatar"><UsersRound :size="19" /></div><div class="team-info"><div class="team-title"><h3>{{ team.name }}</h3><span v-if="team.isPersonal">Cá nhân</span></div><p>{{ team.roleLabel ?? team.role ?? 'Thành viên' }}</p></div><Link v-if="team.slug" :href="`/teams/${team.slug}`" class="team-arrow"><ChevronRight :size="18" /></Link>
                </div>
            </div>
            <div v-else class="empty-state"><span><UsersRound :size="23" /></span><h3>Chưa có nhóm</h3><p>Tạo nhóm đầu tiên để bắt đầu cộng tác.</p><CreateTeamModal><button class="secondary-button">Tạo nhóm mới</button></CreateTeamModal></div>
        </section>

        <!-- GIAO DIỆN -->
        <section class="settings-view settings-tab-panel appearance-view">
            <div class="view-header"><div class="view-title-wrap"><div class="view-icon appearance-icon"><Palette :size="21" /></div><div><span class="section-kicker">TRẢI NGHIỆM HIỂN THỊ</span><h2>Giao diện</h2><p>Tùy chỉnh giao diện TechStore theo sở thích của bạn.</p></div></div></div>
            <section class="settings-card appearance-card">
                <div class="appearance-preview">
                    <div class="mock-browser"><div class="mock-dots"><i></i><i></i><i></i></div><div class="mock-layout"><div class="mock-side"><b></b><b></b><b></b><b></b></div><div class="mock-main"><strong></strong><span></span><span></span><div></div></div></div></div>
                </div>
                <div class="appearance-settings"><div class="card-heading"><span class="card-heading-icon"><Palette :size="18" /></span><div><h3>Chế độ hiển thị</h3><p>Thay đổi giao diện sáng, tối hoặc theo hệ thống.</p></div></div><div class="appearance-tabs-wrap"><AppearanceTabs /></div><div class="appearance-note"><i class="bi bi-stars" /><span>Lựa chọn của bạn được áp dụng ngay lập tức.</span></div></div>
            </section>
        </section>

        <div class="delete-account-wrap"><DeleteUser /></div>
    </div>
</template>

<style scoped>
.settings-dashboard{--blue:#2563eb;--blue-dark:#1d4ed8;--text:#101828;--muted:#667085;--line:#e4e7ec;--soft:#f8fafc;color:var(--text);padding:2px 0 8px}.settings-view{animation:settingsIn .24s ease both}.view-header{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:22px}.view-title-wrap{display:flex;align-items:center;gap:14px;min-width:0}.view-icon{width:46px;height:46px;display:grid;place-items:center;flex:0 0 46px;border-radius:14px}.profile-icon{color:#2563eb;background:#eff6ff}.security-icon{color:#0f766e;background:#ecfdf5}.teams-icon{color:#7c3aed;background:#f5f3ff}.appearance-icon{color:#c2410c;background:#fff7ed}.section-kicker{display:block;margin-bottom:3px;color:#2563eb;font-size:10px;font-weight:900;letter-spacing:.15em}.view-header h2{margin:0;font-size:1.55rem;line-height:1.15;font-weight:850;letter-spacing:-.035em}.view-header p{margin:5px 0 0;color:var(--muted);font-size:.78rem}.settings-card,.account-card{border:1px solid var(--line);border-radius:18px;background:#fff;box-shadow:0 8px 28px rgba(16,24,40,.045)}.settings-card{padding:24px}.profile-layout{display:grid;grid-template-columns:270px minmax(0,1fr);gap:18px}.account-card{position:relative;overflow:hidden}.account-cover{height:78px;background:linear-gradient(120deg,#dbeafe 0%,#eef2ff 48%,#f5f3ff 100%)}.account-avatar{position:absolute;top:38px;left:24px;width:78px;height:78px;display:grid;place-items:center;overflow:hidden;border:4px solid #fff;border-radius:22px;color:#fff;background:linear-gradient(135deg,#2563eb,#7c3aed);font-size:1.7rem;font-weight:850;box-shadow:0 8px 20px rgba(37,99,235,.18)}.account-avatar img{width:100%;height:100%;object-fit:cover}.account-body{padding:48px 20px 22px}.account-body h3{margin:0;font-size:1.03rem;font-weight:850}.account-email{margin:4px 0 11px;color:var(--muted);font-size:.72rem;word-break:break-word}.status-badge,.email-state{display:inline-flex;align-items:center;gap:5px;padding:5px 9px;border-radius:999px;font-size:.62rem;font-weight:800}.is-verified{color:#067647;background:#ecfdf3}.is-warning{color:#b54708;background:#fffaeb}.verify-link{display:flex;align-items:center;gap:2px;width:max-content;margin-top:9px;color:#2563eb;font-size:.67rem;font-weight:800;text-decoration:none}.account-facts{display:grid;gap:10px;margin-top:20px;padding-top:16px;border-top:1px solid #eef0f3}.account-facts>div{display:flex;align-items:center;gap:9px}.account-facts>div>span{width:30px;height:30px;display:grid;place-items:center;border-radius:9px;color:#667085;background:#f2f4f7}.account-facts section{display:grid}.account-facts small{color:#98a2b3;font-size:.61rem}.account-facts strong{margin-top:1px;font-size:.7rem}.profile-main-stack{display:grid;gap:18px}.card-heading{display:flex;align-items:flex-start;gap:10px;margin-bottom:20px}.card-heading-icon{width:38px;height:38px;display:grid;place-items:center;flex:0 0 38px;border-radius:11px;color:#2563eb;background:#eff6ff}.card-heading h3,.feature-head h3{margin:0;font-size:.91rem;font-weight:850}.card-heading p,.feature-head p{margin:3px 0 0;color:#98a2b3;font-size:.69rem;line-height:1.45}.settings-form{display:grid;grid-template-columns:1fr 1fr;gap:16px}.field{min-width:0}.field.full{grid-column:1/-1}.field label{display:block;margin:0 0 7px;color:#344054;font-size:.67rem;font-weight:800}.field input{display:block;width:100%;height:43px;padding:0 13px;border:1px solid #d8dde6;border-radius:10px;outline:0;color:#101828;background:#fff;font:inherit;font-size:.75rem;box-sizing:border-box;transition:.18s}.field input::placeholder{color:#a0a8b5}.field input:focus{border-color:#84aef7;box-shadow:0 0 0 4px rgba(37,99,235,.08)}.form-actions{grid-column:1/-1;display:flex;justify-content:flex-end;margin-top:1px}.primary-button,.secondary-button,.settings-logout{display:inline-flex;align-items:center;justify-content:center;gap:7px;border-radius:10px;font-size:.7rem;font-weight:800;cursor:pointer;transition:.18s}.primary-button{min-height:39px;padding:0 15px;border:1px solid var(--blue);color:#fff;background:linear-gradient(135deg,#2563eb,#3b5fe8);box-shadow:0 6px 14px rgba(37,99,235,.16)}.primary-button:hover{background:var(--blue-dark);transform:translateY(-1px)}.primary-button:disabled{opacity:.6;cursor:not-allowed;transform:none}.secondary-button{min-height:37px;padding:0 14px;border:1px solid #d9dee7;color:#344054;background:#fff}.secondary-button:hover{border-color:#b8cdf8;color:#2563eb}.settings-logout{min-height:38px;padding:0 12px;border:1px solid #e4e7ec;color:#475467;background:#fff}.settings-logout:hover{border-color:#fda4af;color:#be123c;background:#fff8f8}.settings-success{display:flex;align-items:center;gap:8px;margin:-4px 0 18px;padding:11px 13px;border:1px solid #abefc6;border-radius:11px;color:#067647;background:#f0fdf4;font-size:.71rem;font-weight:700}.current-email{display:flex;align-items:center;gap:11px;padding:12px;border:1px solid #edf0f4;border-radius:12px;background:#f8fafc}.email-icon{width:35px;height:35px;display:grid;place-items:center;border-radius:10px;color:#2563eb;background:#eff6ff}.current-email div{display:grid;min-width:0;flex:1}.current-email small{color:#98a2b3;font-size:.61rem}.current-email strong{margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.73rem}.email-state{font-size:.58rem}.email-divider{display:flex;align-items:center;gap:12px;margin:19px 0 14px;color:#667085;font-size:.65rem;font-weight:800}.email-divider:after{content:"";height:1px;flex:1;background:#edf0f4}.email-form{display:grid;grid-template-columns:minmax(0,1fr) 150px;align-items:end;gap:12px}.email-form .primary-button{height:43px}.security-view .view-header,.teams-view .view-header,.appearance-view .view-header{margin-bottom:24px}.password-card{margin-bottom:18px}.password-form .relative{position:relative}.password-form :deep(input){height:43px!important;border:1px solid #d8dde6!important;border-radius:10px!important;box-shadow:none!important;padding-left:13px!important;font-size:.75rem!important}.password-form :deep(input:focus){border-color:#84aef7!important;box-shadow:0 0 0 4px rgba(37,99,235,.08)!important}.password-form :deep(button){color:#98a2b3!important}.password-footer{grid-column:1/-1;display:flex;align-items:center;justify-content:space-between;gap:18px;padding-top:3px}.security-tip{display:flex;align-items:center;gap:9px}.security-tip>span{width:34px;height:34px;display:grid;place-items:center;border-radius:10px;color:#b54708;background:#fffaeb}.security-tip strong,.security-tip small{display:block}.security-tip strong{font-size:.66rem}.security-tip small{margin-top:2px;color:#98a2b3;font-size:.6rem}.security-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.feature-card{min-width:0}.feature-head{display:flex;gap:11px;align-items:flex-start;padding-bottom:17px;border-bottom:1px solid #edf0f4}.feature-icon{width:39px;height:39px;display:grid;place-items:center;flex:0 0 39px;border-radius:11px}.feature-icon.blue{color:#2563eb;background:#eff6ff}.feature-icon.purple{color:#7c3aed;background:#f5f3ff}.feature-content{padding-top:16px}.feature-content :deep(.space-y-6){display:block!important}.feature-content :deep([class*="text-muted-foreground"]){color:#667085!important;font-size:.68rem!important;line-height:1.6!important}.feature-content :deep(h3),.feature-content :deep(h4){color:#101828;font-size:.76rem;font-weight:800}.feature-content :deep(button){border-radius:9px!important;font-size:.64rem!important;font-weight:800!important;min-height:35px}.feature-content :deep(.bg-primary),.feature-content :deep(button:not([class*="destructive"])){background:#2563eb!important;color:#fff!important;border-color:#2563eb!important}.feature-content :deep([class*="space-y-4"]){gap:10px!important}.teams-list{display:grid;gap:10px}.team-row{display:flex;align-items:center;gap:12px;padding:13px 14px;border:1px solid var(--line);border-radius:14px;background:#fff;transition:.18s}.team-row:hover{border-color:#c9d8f8;box-shadow:0 8px 22px rgba(16,24,40,.055);transform:translateY(-1px)}.team-avatar{width:42px;height:42px;display:grid;place-items:center;flex:0 0 42px;border-radius:12px;color:#7c3aed;background:#f5f3ff}.team-info{min-width:0;flex:1}.team-title{display:flex;align-items:center;gap:7px}.team-title h3{margin:0;font-size:.78rem;font-weight:850}.team-title span{padding:3px 6px;border-radius:5px;color:#667085;background:#f2f4f7;font-size:.53rem;font-weight:800}.team-info p{margin:3px 0 0;color:#98a2b3;font-size:.62rem}.team-arrow{width:32px;height:32px;display:grid;place-items:center;border-radius:9px;color:#98a2b3;background:#f8fafc}.team-arrow:hover{color:#2563eb;background:#eff6ff}.empty-state{display:flex;align-items:center;flex-direction:column;padding:52px 20px;border:1px dashed #d8dde6;border-radius:16px;text-align:center;background:#fcfdff}.empty-state>span{width:48px;height:48px;display:grid;place-items:center;border-radius:14px;color:#7c3aed;background:#f5f3ff}.empty-state h3{margin:13px 0 4px;font-size:.85rem;font-weight:850}.empty-state p{margin:0 0 15px;color:#98a2b3;font-size:.67rem}.appearance-card{display:grid;grid-template-columns:1fr 1fr;overflow:hidden;padding:0}.appearance-preview{display:flex;align-items:center;justify-content:center;padding:30px;background:linear-gradient(145deg,#f8fbff,#f7f5ff);border-right:1px solid #edf0f4}.mock-browser{width:min(100%,360px);overflow:hidden;border:1px solid #dfe5ee;border-radius:13px;background:#fff;box-shadow:0 14px 30px rgba(16,24,40,.08)}.mock-dots{display:flex;gap:4px;padding:9px 11px;border-bottom:1px solid #edf0f4}.mock-dots i{width:5px;height:5px;border-radius:50%;background:#d0d5dd}.mock-layout{display:grid;grid-template-columns:62px 1fr;min-height:158px}.mock-side{display:grid;align-content:start;gap:8px;padding:13px 10px;border-right:1px solid #edf0f4;background:#f8fafc}.mock-side b{height:8px;border-radius:5px;background:#e4e7ec}.mock-side b:first-child{background:#bfdbfe}.mock-main{padding:16px}.mock-main strong,.mock-main span,.mock-main div{display:block;border-radius:6px;background:#e4e7ec}.mock-main strong{width:70%;height:15px;margin-bottom:13px}.mock-main span{width:52%;height:27px;margin-bottom:8px}.mock-main div{width:88%;height:52px;margin-top:13px;background:#eef2ff}.appearance-settings{padding:30px}.appearance-tabs-wrap{margin-top:3px}.appearance-tabs-wrap :deep(.flex),.appearance-tabs-wrap :deep([role="radiogroup"]){display:flex!important;gap:8px!important;flex-wrap:wrap}.appearance-tabs-wrap :deep(button){min-height:38px!important;padding:0 12px!important;border:1px solid #d9dee7!important;border-radius:9px!important;background:#fff!important;color:#475467!important;font-size:.66rem!important;font-weight:800!important}.appearance-tabs-wrap :deep(button:hover){border-color:#a9c4f7!important;color:#2563eb!important;background:#f8fbff!important}.appearance-note{display:flex;align-items:center;gap:6px;margin-top:13px;color:#98a2b3;font-size:.61rem}.appearance-note i{color:#f59e0b}.delete-account-wrap{margin-top:20px}.delete-account-wrap :deep(> *){border:1px solid #fecdd3!important;border-radius:16px!important;background:#fff7f8!important;box-shadow:none!important}.delete-account-wrap :deep(button){border-radius:9px!important;font-size:.67rem!important;font-weight:800!important}@keyframes settingsIn{from{opacity:.6;transform:translateY(4px)}to{opacity:1;transform:none}}@media(max-width:900px){.profile-layout,.appearance-card{grid-template-columns:1fr}.appearance-preview{border-right:0;border-bottom:1px solid #edf0f4}.security-grid{grid-template-columns:1fr}}@media(max-width:650px){.view-header{align-items:flex-start;flex-direction:column}.view-header>.primary-button,.view-header>form{align-self:stretch}.view-header>.primary-button{width:100%}.settings-card{padding:18px}.settings-form,.email-form{grid-template-columns:1fr}.field.full{grid-column:auto}.form-actions{grid-column:auto}.form-actions .primary-button{width:100%}.password-footer{align-items:stretch;flex-direction:column}.password-footer .primary-button{width:100%}.appearance-settings{padding:20px}.appearance-preview{padding:20px}.current-email{align-items:flex-start;flex-wrap:wrap}.email-state{margin-left:46px}.settings-logout{width:100%}}
</style>
