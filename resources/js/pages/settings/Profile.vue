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
activeSection.value = 'profile';

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

    <h1 class="sr-only">Cài đặt tài khoản</h1>

    <div class="settings-content">
        <section class="settings-section-header">
            <div class="settings-section-icon"><UserRound :size="18" /></div>
            <div><span class="settings-section-eyebrow">THÔNG TIN CÁ NHÂN</span><h2>Thông tin cá nhân</h2><p>Quản lý thông tin tài khoản và thông tin liên hệ của bạn.</p></div>
        </section>

        <div class="settings-grid-two">
            <section class="settings-card">
                <div class="settings-card-head"><div class="settings-card-icon"><UserRound :size="17" /></div><div><h3>Thông tin cơ bản</h3><p>Cập nhật tên, số điện thoại và ngày sinh.</p></div></div>
                <Form v-bind="ProfileController.update.form()" :options="{ preserveScroll: true }" class="settings-form" v-slot="{ errors, processing }">
                    <div class="field"><label for="name">Họ và tên</label><input id="name" name="name" type="text" :value="user.name" autocomplete="name" required /><InputError :message="errors.name" /></div>
                    <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" :value="user.email" autocomplete="email" readonly /><InputError :message="errors.email" /></div>
                    <div class="field-row"><div class="field"><label for="phone">Số điện thoại</label><input id="phone" name="phone" type="tel" :value="user.phone ?? ''" autocomplete="tel" placeholder="Nhập số điện thoại" /><InputError :message="errors.phone" /></div>
                    <div class="field date-field"><label><CalendarDays :size="14" /> Ngày sinh</label><input type="hidden" name="birth_date" :value="birthDate" /><div class="date-picker" :class="{ open: datePickerOpen }"><button type="button" class="date-trigger" @click="datePickerOpen = !datePickerOpen"><span :class="{ placeholder: !birthDate }">{{ dateLabel }}</span><CalendarDays :size="17" /></button><div v-if="datePickerOpen" class="date-popover"><div class="date-popover-head"><button type="button" class="date-nav" @click="moveMonth(-1)"><ChevronLeft :size="16" /></button><select v-model="calendarMonth" class="date-month"><option v-for="(month, index) in monthNames" :key="month" :value="index">{{ month }}</option></select><button type="button" class="date-nav" @click="moveMonth(1)"><ChevronRight :size="16" /></button></div><div class="date-week"><span v-for="day in weekDays" :key="day">{{ day }}</span></div><div class="date-grid"><button v-for="(day,index) in calendarCells" :key="index" type="button" :class="{ selected: day === Number((birthDate.split('-')[2] ?? '0')) && calendarMonth === Number((birthDate.split('-')[1] ?? '0')) - 1 && calendarYear === Number((birthDate.split('-')[0] ?? '0')) }" :disabled="!day || isFutureDate(calendarYear, calendarMonth, day)" @click="selectDate(day)">{{ day || '' }}</button></div><div class="date-popover-footer"><button type="button" class="date-clear" @click="clearBirthDate">Xóa</button><button type="button" class="date-today" @click="chooseToday">Hôm nay</button></div></div></div><small class="field-hint">Chọn ngày sinh trong lịch.</small><InputError :message="errors.birth_date" /></div>
                    </div>
                    <input type="hidden" name="address_province" :value="selectedProvince" /><input type="hidden" name="address_ward" :value="selectedWard" /><input type="hidden" name="address_detail" :value="addressDetail" />
                    <div class="field"><label><MapPin :size="14" /> Tỉnh / thành phố</label><select v-model="selectedProvince" @change="onProvinceChange"><option value="">Chọn tỉnh / thành phố</option><option v-for="province in provinces" :key="province.code" :value="province.name">{{ province.name }}</option></select></div>
                    <div class="field"><label>Phường / xã</label><select v-model="selectedWard" :disabled="!selectedProvince || addressLoading"><option value="">Chọn phường / xã</option><option v-for="ward in wards" :key="ward.code" :value="ward.name">{{ ward.name }}</option></select></div>
                    <div class="field"><label>Địa chỉ chi tiết</label><input v-model="addressDetail" type="text" placeholder="Số nhà, đường, khu phố..." /></div>
                    <div class="settings-form-actions"><button type="submit" class="settings-save-button" :disabled="processing">{{ processing ? 'Đang lưu...' : 'Lưu thay đổi' }}</button></div>
                </Form>
            </section>

            <section class="settings-card"><div class="settings-card-head"><div class="settings-card-icon"><Mail :size="17" /></div><div><h3>Thông tin tài khoản</h3><p>Thông tin hệ thống của tài khoản hiện tại.</p></div></div><div class="account-meta-grid"><div><span>Vai trò</span><strong>{{ roleLabel }}</strong></div><div><span>Trạng thái</span><strong>Đang hoạt động</strong></div><div><span>Phương thức</span><strong>{{ user.google_id ? 'Google' : 'Email / Mật khẩu' }}</strong></div></div><div class="settings-divider" /><div class="settings-quick-links"><Link :href="edit()" class="settings-quick-link"><ShieldCheck :size="15" /><span><strong>Bảo mật</strong><small>Mật khẩu & bảo vệ tài khoản</small></span><ChevronRight :size="15" /></Link><Link :href="edit()" class="settings-quick-link"><Palette :size="15" /><span><strong>Giao diện</strong><small>Tùy chỉnh hiển thị</small></span><ChevronRight :size="15" /></Link></div></section>
        </div>

        <section class="settings-card danger-card"><div class="settings-card-head"><div class="settings-card-icon danger"><LockKeyhole :size="17" /></div><div><h3>Xóa tài khoản</h3><p>Xóa vĩnh viễn tài khoản và toàn bộ dữ liệu liên quan.</p></div></div><DeleteUser /></section>

        <section class="settings-card security-extra-card"><div class="settings-card-head"><div class="settings-card-icon"><ShieldCheck :size="17" /></div><div><h3>Bảo vệ tài khoản</h3><p>Xác thực 2 lớp và Passkey giúp tăng cường bảo mật.</p></div></div><div class="security-extra-grid"><ManageTwoFactor :canManageTwoFactor="props.canManageTwoFactor" :requiresConfirmation="props.requiresConfirmation" :twoFactorEnabled="props.twoFactorEnabled" /><ManagePasskeys :canManagePasskeys="props.canManagePasskeys" :passkeys="props.passkeys" /></div></section>
    </div>
</template>
