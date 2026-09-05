<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, KeyRound, LockKeyhole, Mail, MapPin, Palette, ShieldCheck, UserRound, CalendarDays, ChevronDown } from '@lucide/vue';
import { computed, inject, onMounted, ref, type Ref } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import ManagePasskeys from '@/components/ManagePasskeys.vue';
import ManageTwoFactor from '@/components/ManageTwoFactor.vue';
import PasswordInput from '@/components/PasswordInput.vue';

type SettingsSection = 'profile' | 'security' | 'appearance' | 'orders';
type Province = { code: number; name: string; wards?: Ward[] };
type Ward = { code: number; name: string; province_code?: number };

type Props = {
    passwordRules: string;
    canManageTwoFactor: boolean;
    canManagePasskeys: boolean;
    twoFactorEnabled: boolean;
    requiresConfirmation: boolean;
    passkeys: any[];
};

const props = defineProps<Props>();
const page = usePage();
const user = computed(() => page.props.auth.user as any);
const activeSection = inject<Ref<SettingsSection>>('techstore-settings-section')!;

const maxBirthDate = computed(() => {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
});

const birthDate = ref(user.value.birth_date ? String(user.value.birth_date).slice(0, 10) : '');
const datePickerOpen = ref(false);
const calendarDate = ref(birthDate.value ? new Date(`${birthDate.value}T12:00:00`) : new Date());
const calendarMonth = computed(() => calendarDate.value.getMonth());
const calendarYear = computed(() => calendarDate.value.getFullYear());
const monthNames = ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'];
const weekDays = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
const dateLabel = computed(() => {
    if (!birthDate.value) return 'dd/mm/yyyy';
    const [year, month, day] = birthDate.value.split('-');
    return `${day}/${month}/${year}`;
});
const calendarCells = computed(() => {
    const firstDay = new Date(calendarYear.value, calendarMonth.value, 1);
    const offset = (firstDay.getDay() + 6) % 7;
    const days = new Date(calendarYear.value, calendarMonth.value + 1, 0).getDate();
    return Array.from({ length: 42 }, (_, index) => {
        const day = index - offset + 1;
        return day >= 1 && day <= days ? day : null;
    });
});
function isFutureDate(year: number, month: number, day: number) {
    const today = new Date();
    return new Date(year, month, day) > new Date(today.getFullYear(), today.getMonth(), today.getDate());
}
function selectDate(day: number | null) {
    if (!day || isFutureDate(calendarYear.value, calendarMonth.value, day)) return;
    birthDate.value = `${calendarYear.value}-${String(calendarMonth.value + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    datePickerOpen.value = false;
}
function moveMonth(step: number) {
    const next = new Date(calendarYear.value, calendarMonth.value + step, 1);
    const now = new Date();
    if (next < new Date(1900, 0, 1) || next > new Date(now.getFullYear(), now.getMonth(), 1)) return;
    calendarDate.value = next;
}
function chooseToday() {
    const now = new Date();
    birthDate.value = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
    calendarDate.value = now;
    datePickerOpen.value = false;
}
function clearBirthDate() {
    birthDate.value = '';
    datePickerOpen.value = false;
}

const provinces = ref<Province[]>([]);
const wards = ref<Ward[]>([]);
const selectedProvince = ref('');
const selectedWard = ref('');
const addressDetail = ref(String(user.value.address_detail ?? ''));
const addressLoading = ref(false);
const addressError = ref('');

async function loadWards(provinceName: string) {
    wards.value = [];
    selectedWard.value = '';
    if (!provinceName) return;
    const province = provinces.value.find((item) => item.name === provinceName);
    if (!province) return;
    if (province.wards?.length) {
        wards.value = province.wards;
        return;
    }
    try {
        const response = await fetch(`https://provinces.open-api.vn/api/v2/p/${province.code}?depth=2`);
        if (!response.ok) throw new Error('address-api');
        const data = await response.json();
        wards.value = Array.isArray(data.wards) ? data.wards : [];
    } catch {
        addressError.value = 'Không thể tải danh sách phường/xã. Vui lòng thử lại.';
    }
}
async function onProvinceChange() {
    addressError.value = '';
    await loadWards(selectedProvince.value);
}
onMounted(async () => {
    addressLoading.value = true;
    try {
        const response = await fetch('https://provinces.open-api.vn/api/v2/?depth=2');
        if (!response.ok) throw new Error('address-api');
        const data = await response.json();
        provinces.value = Array.isArray(data) ? data : [];
        const savedProvince = String(user.value.address_province ?? '');
        const province = provinces.value.find((item) => item.name === savedProvince);
        if (province) {
            selectedProvince.value = province.name;
            await loadWards(province.name);
            const savedWard = String(user.value.address_ward ?? '');
            if (wards.value.some((item) => item.name === savedWard)) selectedWard.value = savedWard;
        }
    } catch {
        addressError.value = 'Không thể tải danh sách tỉnh/thành phố. Vui lòng thử lại.';
    } finally {
        addressLoading.value = false;
    }
});
const roleLabel = computed(() => ({ customer: 'Khách hàng', admin: 'Quản trị viên', staff: 'Nhân viên', user: 'Người dùng' }[String(user.value?.role?.label ?? user.value?.role ?? 'customer').toLowerCase()] ?? 'Khách hàng'));
</script>

<template>
    <Head title="Cài đặt tài khoản" />
    <div class="settings-dashboard">
        <section v-show="activeSection === 'profile'" class="settings-view settings-profile-block">
            <div class="view-header"><div class="view-title-wrap"><div class="view-icon profile-icon"><UserRound :size="21" /></div><div><span class="section-kicker">TÀI KHOẢN</span><h2>Thông tin cá nhân</h2><p>Quản lý thông tin cá nhân, liên hệ và địa chỉ nhận hàng.</p></div></div><Form action="/logout" method="post"><button type="submit" class="settings-logout"><i class="bi bi-box-arrow-right" /> Đăng xuất</button></Form></div>
            <div v-if="page.props.status" class="settings-success"><i class="bi bi-check-circle-fill" /><span>{{ page.props.status === 'profile-updated' ? 'Thông tin cá nhân đã được cập nhật.' : 'Thao tác đã được thực hiện thành công.' }}</span></div>
            <div class="profile-layout">
                <aside class="account-card"><div class="account-cover" /><div class="account-avatar"><img v-if="user.avatar" :src="user.avatar" alt="Ảnh đại diện" /><span v-else>{{ String(user.name ?? 'U').charAt(0).toUpperCase() }}</span></div><div class="account-body"><h3>{{ user.name }}</h3><p class="account-email">{{ user.email }}</p><span class="status-badge" :class="user.email_verified_at ? 'is-verified' : 'is-warning'"><i :class="['bi', user.email_verified_at ? 'bi-patch-check-fill' : 'bi-exclamation-circle-fill']" />{{ user.email_verified_at ? 'Email đã xác thực' : 'Chưa xác thực email' }}</span><Link v-if="!user.email_verified_at" href="/verify-email-otp" class="verify-link">Xác thực ngay <ChevronRight :size="14" /></Link><div class="account-facts"><div><span><UserRound :size="15" /></span><section><small>Vai trò</small><strong>{{ roleLabel }}</strong></section></div><div><span><ShieldCheck :size="15" /></span><section><small>Trạng thái</small><strong>{{ user.is_active ? 'Đang hoạt động' : 'Đã khóa' }}</strong></section></div></div></div></aside>
                <div class="profile-main-stack">
                    <section class="settings-card">
                        <div class="card-heading"><span class="card-heading-icon"><UserRound :size="18" /></span><div><h3>Thông tin cơ bản</h3><p>Cập nhật thông tin và địa chỉ nhận hàng.</p></div></div>
                        <Form v-bind="ProfileController.update.form()" v-slot="{ errors, processing }" class="settings-form">
                            <div class="field full"><label>Họ và tên</label><input name="name" :value="user.name" required autocomplete="name" placeholder="Nhập họ và tên" /><InputError :message="errors.name" /></div>
                            <div class="field date-field"><label><CalendarDays :size="14" /> Ngày sinh</label><input type="hidden" name="birth_date" :value="birthDate" /><div class="date-picker" :class="{ open: datePickerOpen }"><button type="button" class="date-trigger" @click="datePickerOpen = !datePickerOpen"><span :class="{ placeholder: !birthDate }">{{ dateLabel }}</span><CalendarDays :size="17" /></button><div v-if="datePickerOpen" class="date-popover"><div class="date-popover-head"><button type="button" class="date-nav" :disabled="calendarYear === 1900 && calendarMonth === 0" @click="moveMonth(-1)"><ChevronLeft :size="17" /></button><strong>{{ monthNames[calendarMonth] }} {{ calendarYear }}</strong><button type="button" class="date-nav" @click="moveMonth(1)"><ChevronRight :size="17" /></button></div><div class="date-week"><span v-for="day in weekDays" :key="day">{{ day }}</span></div><div class="date-grid"><button v-for="(day,index) in calendarCells" :key="index" type="button" :disabled="!day || isFutureDate(calendarYear,calendarMonth,day)" :class="{ selected: day && birthDate === `${calendarYear}-${String(calendarMonth+1).padStart(2,'0')}-${String(day).padStart(2,'0')}` }" @click="selectDate(day)">{{ day ?? '' }}</button></div><div class="date-popover-foot"><button type="button" @click="clearBirthDate">Xóa</button><button type="button" @click="chooseToday">Hôm nay</button></div></div></div><small class="field-hint">Chọn ngày sinh trong lịch.</small><InputError :message="errors.birth_date" /></div>
                            <div class="field"><label>Số điện thoại</label><input name="phone" type="tel" :value="user.phone ?? ''" placeholder="09xxxxxxxx" autocomplete="tel" /><InputError :message="errors.phone" /></div>
                            <div class="field full address-selector-card"><label><MapPin :size="14" /> Địa chỉ nhận hàng</label><div class="address-grid"><div class="select-field"><span>Tỉnh / Thành phố</span><div class="select-wrap"><select name="address_province" v-model="selectedProvince" :disabled="addressLoading" @change="onProvinceChange"><option value="">{{ addressLoading ? 'Đang tải...' : 'Chọn tỉnh / thành phố' }}</option><option v-for="province in provinces" :key="province.code" :value="province.name">{{ province.name }}</option></select><ChevronDown :size="15" /></div></div><div class="select-field"><span>Phường / Xã</span><div class="select-wrap"><select name="address_ward" v-model="selectedWard" :disabled="!selectedProvince || addressLoading"><option value="">{{ selectedProvince ? 'Chọn phường / xã' : 'Chọn tỉnh trước' }}</option><option v-for="ward in wards" :key="ward.code" :value="ward.name">{{ ward.name }}</option></select><ChevronDown :size="15" /></div></div></div><div class="field address-detail-field"><label>Số nhà, tên đường</label><input name="address_detail" v-model="addressDetail" maxlength="250" placeholder="Ví dụ: 25 Nguyễn Văn Linh" autocomplete="street-address" /></div><div class="address-preview"><MapPin :size="15" /><span>{{ addressDetail || 'Số nhà, tên đường' }}, {{ selectedWard || 'Phường / Xã' }}, {{ selectedProvince || 'Tỉnh / Thành phố' }}</span></div><small class="field-hint">Chọn tỉnh/thành phố và phường/xã từ danh sách, sau đó chỉ nhập số nhà và tên đường.</small><small v-if="addressError" class="field-error-text">{{ addressError }}</small></div>
                            <div class="form-actions"><button class="primary-button" :disabled="processing"><i class="bi bi-check2" />{{ processing ? 'Đang lưu...' : 'Lưu thay đổi' }}</button></div>
                        </Form>
                    </section>
                    <section class="settings-card email-card"><div class="card-heading"><span class="card-heading-icon"><Mail :size="18" /></span><div><h3>Địa chỉ email</h3><p>Email dùng để đăng nhập và khôi phục tài khoản.</p></div></div><div class="current-email"><span class="email-icon"><Mail :size="17" /></span><div><small>Email hiện tại</small><strong>{{ user.email }}</strong></div><span class="email-state" :class="user.email_verified_at ? 'is-verified' : 'is-warning'">{{ user.email_verified_at ? 'Đã xác thực' : 'Chưa xác thực' }}</span></div><div class="email-divider"><span>Đổi địa chỉ email</span></div><Form action="/settings/email/request" method="post" class="email-form" v-slot="{ errors, processing }"><div class="field"><label>Email mới</label><input name="email" type="email" placeholder="email-moi@example.com" required autocomplete="email" /><InputError :message="errors.email" /></div><button class="primary-button" :disabled="processing"><i class="bi bi-shield-check" />{{ processing ? 'Đang gửi...' : 'Gửi mã xác thực' }}</button></Form></section>
                </div>
            </div>
            <div class="delete-account-wrap"><DeleteUser /></div>
        </section>

        <section v-show="activeSection === 'security'" class="settings-view security-view"><div class="view-header"><div class="view-title-wrap"><div class="view-icon security-icon"><ShieldCheck :size="21" /></div><div><span class="section-kicker">BẢO MẬT TÀI KHOẢN</span><h2>Bảo mật</h2><p>Kiểm soát mật khẩu, xác thực hai lớp và phương thức đăng nhập.</p></div></div></div><section class="settings-card password-card"><div class="card-heading"><span class="card-heading-icon"><LockKeyhole :size="18" /></span><div><h3>Đổi mật khẩu</h3><p>Nên sử dụng mật khẩu dài, riêng biệt và khó đoán.</p></div></div><Form v-bind="SecurityController.update.form()" :options="{ preserveScroll: true }" reset-on-success :reset-on-error="['password','password_confirmation','current_password']" v-slot="{ errors, processing }" class="settings-form"><div class="field full"><label>Mật khẩu hiện tại</label><PasswordInput name="current_password" autocomplete="current-password" placeholder="Nhập mật khẩu hiện tại" /><InputError :message="errors.current_password" /></div><div class="field"><label>Mật khẩu mới</label><PasswordInput name="password" autocomplete="new-password" placeholder="Nhập mật khẩu mới" :passwordrules="props.passwordRules" /><InputError :message="errors.password" /></div><div class="field"><label>Xác nhận mật khẩu</label><PasswordInput name="password_confirmation" autocomplete="new-password" placeholder="Nhập lại mật khẩu" :passwordrules="props.passwordRules" /><InputError :message="errors.password_confirmation" /></div><div class="password-footer"><div class="security-tip"><span><i class="bi bi-shield-check" /></span><div><strong>Mẹo bảo mật</strong><small>Kết hợp chữ hoa, chữ thường, số và ký tự đặc biệt.</small></div></div><button class="primary-button" :disabled="processing"><i class="bi bi-check2" />{{ processing ? 'Đang cập nhật...' : 'Cập nhật mật khẩu' }}</button></div></Form></section><div class="security-grid"><section v-if="props.canManageTwoFactor" class="settings-card feature-card"><div class="feature-head"><span class="feature-icon blue"><ShieldCheck :size="19" /></span><div><h3>Xác thực 2 lớp</h3><p>Bảo vệ tài khoản bằng mã xác thực từ điện thoại.</p></div></div><div class="feature-content"><ManageTwoFactor :canManageTwoFactor="props.canManageTwoFactor" :requiresConfirmation="props.requiresConfirmation" :twoFactorEnabled="props.twoFactorEnabled" /></div></section><section v-if="props.canManagePasskeys" class="settings-card feature-card"><div class="feature-head"><span class="feature-icon purple"><KeyRound :size="19" /></span><div><h3>Passkey</h3><p>Đăng nhập không cần mật khẩu bằng thiết bị tin cậy.</p></div></div><div class="feature-content"><ManagePasskeys :canManagePasskeys="props.canManagePasskeys" :passkeys="props.passkeys" /></div></section></div></section>

        <section v-show="activeSection === 'appearance'" class="settings-view appearance-view"><div class="view-header"><div class="view-title-wrap"><div class="view-icon appearance-icon"><Palette :size="21" /></div><div><span class="section-kicker">TRẢI NGHIỆM HIỂN THỊ</span><h2>Giao diện</h2><p>Tùy chỉnh giao diện TechStore theo sở thích của bạn.</p></div></div></div><section class="settings-card appearance-card"><div class="appearance-preview"><div class="mock-browser"><div class="mock-dots"><i></i><i></i><i></i></div><div class="mock-layout"><div class="mock-side"><b></b><b></b><b></b><b></b></div><div class="mock-main"><strong></strong><span></span><span></span><div></div></div></div></div></div><div class="appearance-settings"><div class="card-heading"><span class="card-heading-icon"><Palette :size="18" /></span><div><h3>Chế độ hiển thị</h3><p>Thay đổi giao diện sáng, tối hoặc theo hệ thống.</p></div></div><div class="appearance-tabs-wrap"><AppearanceTabs /></div><div class="appearance-note"><i class="bi bi-stars" /><span>Lựa chọn của bạn được áp dụng ngay lập tức.</span></div></div></section></section>
    </div>
</template>

<style>
.settings-dashboard{--blue:#2563eb;--text:#101828;--muted:#667085;color:var(--text);width:100%;padding:2px 0 8px}.settings-dashboard *{box-sizing:border-box}.settings-dashboard .settings-view{animation:settingsIn .2s ease both}.settings-dashboard .view-header{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:22px}.settings-dashboard .view-title-wrap{display:flex;align-items:center;gap:14px;min-width:0}.settings-dashboard .view-icon{width:46px;height:46px;display:grid;place-items:center;flex:0 0 46px;border-radius:14px}.settings-dashboard .profile-icon{color:#2563eb;background:#eff6ff}.settings-dashboard .security-icon{color:#0f766e;background:#ecfdf5}.settings-dashboard .appearance-icon{color:#c2410c;background:#fff7ed}.settings-dashboard .section-kicker{display:block;margin-bottom:3px;color:#2563eb;font-size:10px;font-weight:900;letter-spacing:.15em}.settings-dashboard .view-header h2{margin:0;font-size:1.55rem;line-height:1.15;font-weight:850;letter-spacing:-.035em}.settings-dashboard .view-header p{margin:5px 0 0;color:#667085;font-size:.78rem}.settings-dashboard .settings-card,.settings-dashboard .account-card{border:1px solid #e4e7ec;border-radius:18px;background:#fff;box-shadow:0 8px 28px rgba(16,24,40,.045)}.settings-dashboard .settings-card{padding:24px}.settings-dashboard .profile-layout{display:grid;grid-template-columns:270px minmax(0,1fr);gap:18px}.settings-dashboard .account-card{position:relative;overflow:hidden;min-width:0}.settings-dashboard .account-cover{height:78px;background:linear-gradient(120deg,#dbeafe,#eef2ff 48%,#f5f3ff)}.settings-dashboard .account-avatar{position:absolute;top:38px;left:24px;width:78px;height:78px;display:grid;place-items:center;overflow:hidden;border:4px solid #fff;border-radius:50%;color:#fff;background:linear-gradient(135deg,#2563eb,#7c3aed);font-size:1.5rem;font-weight:850;box-shadow:0 6px 18px rgba(16,24,40,.15)}.settings-dashboard .account-avatar img{width:100%;height:100%;object-fit:cover}.settings-dashboard .account-body{padding:50px 20px 22px}.settings-dashboard .account-body h3{margin:0;font-size:1rem;font-weight:850}.settings-dashboard .account-email{margin:4px 0 12px;color:#667085;font-size:.73rem;word-break:break-word}.settings-dashboard .status-badge,.settings-dashboard .email-state{display:inline-flex;align-items:center;gap:5px;border-radius:999px;font-size:.64rem;font-weight:800}.settings-dashboard .status-badge{padding:6px 9px}.settings-dashboard .is-verified{color:#067647;background:#ecfdf3}.settings-dashboard .is-warning{color:#b54708;background:#fffaeb}.settings-dashboard .verify-link{display:flex;align-items:center;gap:3px;margin-top:9px;color:#2563eb;font-size:.7rem;font-weight:800;text-decoration:none}.settings-dashboard .account-facts{display:grid;gap:10px;margin-top:18px;padding-top:16px;border-top:1px solid #eef0f3}.settings-dashboard .account-facts>div{display:flex;align-items:center;gap:9px}.settings-dashboard .account-facts>div>span{display:grid;place-items:center;width:30px;height:30px;border-radius:9px;color:#667085;background:#f8fafc}.settings-dashboard .account-facts section{display:grid;gap:1px}.settings-dashboard .account-facts small{color:#98a2b3;font-size:.61rem}.settings-dashboard .account-facts strong{font-size:.72rem}.settings-dashboard .profile-main-stack{display:grid;gap:18px;min-width:0}.settings-dashboard .card-heading{display:flex;align-items:flex-start;gap:10px;margin-bottom:18px}.settings-dashboard .card-heading-icon{display:grid;place-items:center;width:36px;height:36px;flex:0 0 36px;border-radius:10px;color:#2563eb;background:#eff6ff}.settings-dashboard .card-heading h3{margin:0;font-size:.93rem;font-weight:850}.settings-dashboard .card-heading p{margin:3px 0 0;color:#667085;font-size:.7rem}.settings-dashboard .settings-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.settings-dashboard .field{min-width:0}.settings-dashboard .field.full{grid-column:1/-1}.settings-dashboard .field label{display:flex;align-items:center;gap:5px;margin:0 0 7px;color:#344054;font-size:.69rem;font-weight:800}.settings-dashboard .field input,.settings-dashboard .select-wrap select{width:100%;border:1px solid #dfe3e8;border-radius:11px;background:#fff;color:#101828;padding:10px 12px;outline:none;font:inherit;font-size:.76rem;transition:border-color .15s,box-shadow .15s}.settings-dashboard .field input{height:43px}.settings-dashboard .field input:focus,.settings-dashboard .select-wrap select:focus{border-color:#93b4ff;box-shadow:0 0 0 3px rgba(37,99,235,.08)}.settings-dashboard .field-hint{display:block;margin-top:6px;color:#98a2b3;font-size:.62rem}.settings-dashboard .form-actions{grid-column:1/-1;display:flex;justify-content:flex-end}.settings-dashboard .primary-button,.settings-dashboard .settings-logout{border:0;border-radius:10px;font:inherit;font-size:.72rem;font-weight:800;cursor:pointer}.settings-dashboard .primary-button{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:40px;padding:0 16px;color:#fff;background:linear-gradient(135deg,#2563eb,#4f46e5);box-shadow:0 8px 18px rgba(37,99,235,.2)}.settings-dashboard .primary-button:disabled{opacity:.6;cursor:not-allowed}.settings-dashboard .settings-logout{padding:9px 12px;color:#475467;background:#f8fafc}.settings-dashboard .email-card{padding-top:22px}.settings-dashboard .current-email{display:flex;align-items:center;gap:10px;padding:12px;border:1px solid #edf0f3;border-radius:12px;background:#fafbfc}.settings-dashboard .email-icon{display:grid;place-items:center;width:34px;height:34px;border-radius:9px;color:#2563eb;background:#eff6ff}.settings-dashboard .current-email div{min-width:0;flex:1}.settings-dashboard .current-email small{display:block;color:#98a2b3;font-size:.6rem}.settings-dashboard .current-email strong{display:block;margin-top:2px;font-size:.73rem;overflow:hidden;text-overflow:ellipsis}.settings-dashboard .email-state{padding:5px 8px}.settings-dashboard .email-divider{display:flex;align-items:center;gap:10px;margin:18px 0 13px;color:#667085;font-size:.68rem;font-weight:800}.settings-dashboard .email-divider:before,.settings-dashboard .email-divider:after{content:'';height:1px;flex:1;background:#edf0f3}.settings-dashboard .email-form{display:flex;align-items:flex-end;gap:10px}.settings-dashboard .email-form .field{flex:1}.settings-dashboard .security-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin-top:18px}.settings-dashboard .feature-head{display:flex;gap:11px;align-items:flex-start}.settings-dashboard .feature-icon{display:grid;place-items:center;width:38px;height:38px;border-radius:11px;flex:0 0 38px}.settings-dashboard .feature-icon.blue{color:#2563eb;background:#eff6ff}.settings-dashboard .feature-icon.purple{color:#7c3aed;background:#f5f3ff}.settings-dashboard .feature-head h3{margin:0;font-size:.9rem}.settings-dashboard .feature-head p{margin:4px 0 0;color:#667085;font-size:.68rem}.settings-dashboard .feature-content{margin-top:15px;padding-top:15px;border-top:1px solid #edf0f3}.settings-dashboard .password-footer{grid-column:1/-1;display:flex;align-items:center;justify-content:space-between;gap:14px}.settings-dashboard .security-tip{display:flex;align-items:center;gap:9px}.settings-dashboard .security-tip>span{display:grid;place-items:center;width:34px;height:34px;border-radius:9px;color:#067647;background:#ecfdf3}.settings-dashboard .security-tip strong,.settings-dashboard .security-tip small{display:block}.settings-dashboard .security-tip strong{font-size:.68rem}.settings-dashboard .security-tip small{margin-top:2px;color:#667085;font-size:.61rem}.settings-dashboard .appearance-card{display:grid;grid-template-columns:.9fr 1.1fr;gap:24px}.settings-dashboard .appearance-preview{display:grid;place-items:center;min-height:250px;border-radius:15px;background:linear-gradient(135deg,#eff6ff,#f5f3ff)}.settings-dashboard .mock-browser{width:78%;overflow:hidden;border:1px solid #dfe3e8;border-radius:12px;background:#fff;box-shadow:0 15px 35px rgba(16,24,40,.1)}.settings-dashboard .mock-dots{display:flex;gap:4px;padding:8px;border-bottom:1px solid #eef0f3}.settings-dashboard .mock-dots i{width:5px;height:5px;border-radius:50%;background:#d0d5dd}.settings-dashboard .mock-layout{display:grid;grid-template-columns:28% 1fr;min-height:145px}.settings-dashboard .mock-side{padding:10px;background:#f8fafc}.settings-dashboard .mock-side b{display:block;height:7px;margin-bottom:9px;border-radius:4px;background:#e4e7ec}.settings-dashboard .mock-main{padding:13px}.settings-dashboard .mock-main strong,.settings-dashboard .mock-main span,.settings-dashboard .mock-main div{display:block;border-radius:5px;background:#eef2f6}.settings-dashboard .mock-main strong{width:45%;height:10px;margin-bottom:12px}.settings-dashboard .mock-main span{width:90%;height:6px;margin-bottom:7px}.settings-dashboard .mock-main div{height:50px;margin-top:12px;background:#eff6ff}.settings-dashboard .appearance-settings{padding:4px}.settings-dashboard .appearance-tabs-wrap{padding:5px 0}.settings-dashboard .appearance-note{display:flex;align-items:center;gap:7px;padding:10px 12px;border-radius:10px;color:#667085;background:#f8fafc;font-size:.65rem}.settings-dashboard .delete-account-wrap{margin-top:18px}.settings-dashboard .settings-success{display:flex;align-items:center;gap:8px;margin-bottom:16px;padding:11px 13px;border:1px solid #abefc6;border-radius:11px;color:#067647;background:#ecfdf3;font-size:.7rem;font-weight:700}.settings-dashboard .address-selector-card{padding:14px;border:1px solid #edf0f3;border-radius:14px;background:#fafbfc}.settings-dashboard .address-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.settings-dashboard .select-field>span{display:block;margin:0 0 6px;color:#667085;font-size:.61rem;font-weight:750}.settings-dashboard .select-wrap{position:relative}.settings-dashboard .select-wrap select{height:43px;appearance:none;padding-right:36px}.settings-dashboard .select-wrap svg{position:absolute;right:12px;top:14px;color:#98a2b3;pointer-events:none}.settings-dashboard .address-detail-field{margin-top:10px}.settings-dashboard .address-detail-field label{font-size:.61rem;color:#667085}.settings-dashboard .address-preview{display:flex;align-items:center;gap:7px;margin-top:10px;padding:9px 10px;border:1px solid #dbeafe;border-radius:10px;color:#1d4ed8;background:#eff6ff;font-size:.65rem}.settings-dashboard .field-error-text{display:block;margin-top:7px;color:#b42318;font-size:.62rem}.settings-dashboard .date-picker{position:relative}.settings-dashboard .date-trigger{display:flex;align-items:center;justify-content:space-between;width:100%;height:43px;padding:0 12px;border:1px solid #dfe3e8;border-radius:11px;color:#101828;background:#fff;font:inherit;font-size:.76rem;cursor:pointer}.settings-dashboard .date-trigger:hover,.settings-dashboard .date-picker.open .date-trigger{border-color:#93b4ff;box-shadow:0 0 0 3px rgba(37,99,235,.08)}.settings-dashboard .date-trigger .placeholder{color:#98a2b3}.settings-dashboard .date-popover{position:absolute;z-index:30;top:calc(100% + 7px);left:0;width:280px;padding:12px;border:1px solid #dfe3e8;border-radius:14px;background:#fff;box-shadow:0 18px 40px rgba(16,24,40,.15)}.settings-dashboard .date-popover-head{display:grid;grid-template-columns:34px 1fr 34px;align-items:center;gap:4px;margin-bottom:10px}.settings-dashboard .date-popover-head strong{text-align:center;font-size:.75rem}.settings-dashboard .date-nav{display:grid;width:32px;height:32px;place-items:center;border:0;border-radius:8px;color:#475467;background:#f8fafc;cursor:pointer}.settings-dashboard .date-nav:disabled{opacity:.35;cursor:not-allowed}.settings-dashboard .date-week,.settings-dashboard .date-grid{display:grid;grid-template-columns:repeat(7,1fr);text-align:center}.settings-dashboard .date-week{margin-bottom:4px;color:#98a2b3;font-size:.58rem;font-weight:800}.settings-dashboard .date-grid button{width:30px;height:30px;margin:1px auto;border:0;border-radius:8px;color:#344054;background:transparent;font-size:.66rem;cursor:pointer}.settings-dashboard .date-grid button:hover:not(:disabled){background:#eff6ff;color:#2563eb}.settings-dashboard .date-grid button.selected{color:#fff;background:#2563eb;box-shadow:0 4px 10px rgba(37,99,235,.22)}.settings-dashboard .date-grid button:disabled{color:#d0d5dd;cursor:not-allowed}.settings-dashboard .date-popover-foot{display:flex;justify-content:space-between;margin-top:8px;padding-top:8px;border-top:1px solid #edf0f3}.settings-dashboard .date-popover-foot button{border:0;color:#2563eb;background:transparent;font-size:.64rem;font-weight:800;cursor:pointer}.settings-dashboard .date-popover-foot button:hover{text-decoration:underline}@keyframes settingsIn{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:none}}@media(max-width:900px){.settings-dashboard .profile-layout,.settings-dashboard .appearance-card{grid-template-columns:1fr}.settings-dashboard .security-grid{grid-template-columns:1fr}}@media(max-width:620px){.settings-dashboard .view-header{align-items:flex-start}.settings-dashboard .settings-card{padding:18px}.settings-dashboard .settings-form,.settings-dashboard .address-grid{grid-template-columns:1fr}.settings-dashboard .field.full{grid-column:auto}.settings-dashboard .form-actions,.settings-dashboard .password-footer{grid-column:auto}.settings-dashboard .email-form{align-items:stretch;flex-direction:column}.settings-dashboard .email-form .field{width:100%}.settings-dashboard .password-footer{align-items:stretch;flex-direction:column}.settings-dashboard .primary-button{width:100%}.settings-dashboard .settings-logout{display:none}.settings-dashboard .date-popover{width:min(280px,calc(100vw - 50px))}}
</style>
