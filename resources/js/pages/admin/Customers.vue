<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';

type Customer = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    address: string | null;
    address_province: string | null;
    address_ward: string | null;
    address_detail: string | null;
    is_active: boolean;
    email_verified_at: string | null;
    created_at: string;
};

const props = defineProps<{ users: { data: Customer[]; total: number; current_page: number; last_page: number } }>();
const show = ref(false);
const editing = ref<Customer | null>(null);
const form = ref({ name: '', email: '', phone: '', address_province: '', address_ward: '', address_detail: '' });

const openEdit = (customer: Customer) => {
    editing.value = customer;
    form.value = {
        name: customer.name,
        email: customer.email,
        phone: customer.phone ?? '',
        address_province: customer.address_province ?? '',
        address_ward: customer.address_ward ?? '',
        address_detail: customer.address_detail ?? '',
    };
    show.value = true;
};

const save = () => {
    if (!editing.value) return;
    router.patch(`/admin/customers/${editing.value.id}`, form.value, { onSuccess: () => { show.value = false; } });
};

const toggle = (customer: Customer) => router.patch(`/admin/customers/${customer.id}/toggle`, {});
const verify = (customer: Customer) => router.patch(`/admin/customers/${customer.id}/verify`, {});
const address = (customer: Customer) => [customer.address_detail, customer.address_ward, customer.address_province].filter(Boolean).join(', ') || customer.address || 'Chưa cập nhật';
</script>

<template>
    <div class="admin-page">
        <div class="customer-head">
            <div><div class="admin-kicker">HỆ THỐNG</div><h1>Khách hàng</h1><p>Kiểm tra xác thực tài khoản, địa chỉ và trạng thái kích hoạt của khách hàng.</p></div>
            <div class="customer-summary"><span><strong>{{ props.users.total.toLocaleString('vi-VN') }}</strong> tài khoản</span><i class="bi bi-people-fill"/></div>
        </div>

        <div class="verification-note mb-3"><span class="note-icon"><i class="bi bi-shield-check"/></span><div><strong>Quản trị viên / Nhân viên cấp cao</strong><small>Có thể xác thực email và kích hoạt tài khoản giúp khách hàng ngay tại bảng quản lý.</small></div></div>

        <div class="admin-panel overflow-hidden">
            <div class="table-responsive"><table class="table admin-table customer-table align-middle">
                <thead><tr><th>Khách hàng</th><th>Liên hệ</th><th>Địa chỉ</th><th>Xác thực</th><th>Kích hoạt</th><th class="text-end">Thao tác</th></tr></thead>
                <tbody>
                    <tr v-for="customer in props.users.data" :key="customer.id">
                        <td><div class="customer-person"><span class="customer-avatar"><i class="bi bi-person-fill"/></span><div><strong>{{ customer.name }}</strong><small>{{ customer.email }}</small></div></div></td>
                        <td><span class="contact-line"><i class="bi bi-telephone"/>{{ customer.phone || 'Chưa cập nhật' }}</span></td>
                        <td><div class="address-cell"><i class="bi bi-geo-alt-fill"/><span>{{ address(customer) }}</span></div></td>
                        <td><span :class="['verify-badge', customer.email_verified_at ? 'verified' : 'unverified']"><i :class="['bi', customer.email_verified_at ? 'bi-patch-check-fill' : 'bi-exclamation-circle-fill']"/>{{ customer.email_verified_at ? 'Đã xác thực' : 'Chưa xác thực' }}</span></td>
                        <td><button type="button" :class="['status-toggle', customer.is_active ? 'on' : 'off']" @click="toggle(customer)"><span/><b>{{ customer.is_active ? 'Đang hoạt động' : 'Đã khóa' }}</b></button></td>
                        <td class="text-end"><div class="action-buttons"><button class="btn btn-sm btn-outline-primary" @click="openEdit(customer)" title="Chỉnh sửa"><i class="bi bi-pencil"/></button><button v-if="!customer.email_verified_at" class="btn btn-sm btn-outline-success" @click="verify(customer)" title="Xác thực tài khoản"><i class="bi bi-patch-check"/></button></div></td>
                    </tr>
                    <tr v-if="!props.users.data.length"><td colspan="6" class="empty-state">Chưa có khách hàng.</td></tr>
                </tbody>
            </table></div>
        </div>

        <div v-if="show" class="modal d-block admin-modal-backdrop"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content border-0 shadow-lg customer-modal">
            <div class="modal-header"><div><div class="modal-kicker">HỒ SƠ KHÁCH HÀNG</div><h5 class="mb-0">Cập nhật thông tin</h5></div><button class="btn-close" @click="show=false"/></div>
            <form @submit.prevent="save"><div class="modal-body"><div class="row g-3"><div class="col-md-6"><label class="form-label">Họ tên</label><input v-model="form.name" class="form-control" required/></div><div class="col-md-6"><label class="form-label">Email</label><input v-model="form.email" type="email" class="form-control" required/></div><div class="col-md-6"><label class="form-label">Số điện thoại</label><input v-model="form.phone" class="form-control"/></div><div class="col-md-6"><label class="form-label">Tỉnh / Thành phố</label><input v-model="form.address_province" class="form-control"/></div><div class="col-md-6"><label class="form-label">Phường / Xã</label><input v-model="form.address_ward" class="form-control"/></div><div class="col-md-6"><label class="form-label">Số nhà, tên đường</label><input v-model="form.address_detail" class="form-control"/></div></div></div><div class="modal-footer"><button type="button" class="btn btn-light" @click="show=false">Hủy</button><button class="btn btn-primary px-4"><i class="bi bi-check2 me-2"/>Lưu thay đổi</button></div></form>
        </div></div></div>
    </div>
</template>

<style scoped>
.customer-head{display:flex;justify-content:space-between;align-items:end;gap:20px;margin-bottom:18px}.admin-kicker{color:#2563eb;font-size:.68rem;font-weight:900;letter-spacing:.14em}.customer-head h1{margin:3px 0;font-size:1.8rem;font-weight:900;letter-spacing:-.04em}.customer-head p{margin:0;color:#667085;font-size:.8rem}.customer-summary{display:flex;align-items:center;gap:9px;padding:9px 12px;border:1px solid #e5e9f0;border-radius:12px;background:#fff;color:#667085;font-size:.7rem;font-weight:700}.customer-summary strong{color:#101828;font-size:1rem}.customer-summary i{display:grid;width:29px;height:29px;place-items:center;border-radius:9px;background:#eff6ff;color:#2563eb}.verification-note{display:flex;align-items:center;gap:11px;padding:12px 14px;border:1px solid #dbeafe;border-radius:14px;background:linear-gradient(135deg,#f8fbff,#eff6ff)}.note-icon{display:grid;flex:0 0 34px;width:34px;height:34px;place-items:center;border-radius:10px;background:#dbeafe;color:#2563eb}.verification-note div{display:flex;flex-direction:column}.verification-note strong{font-size:.72rem;color:#1e3a8a}.verification-note small{margin-top:2px;color:#64748b;font-size:.65rem}.admin-panel{border:1px solid #e5e9f0;border-radius:16px;background:#fff;box-shadow:0 8px 28px rgba(16,24,40,.05)}.admin-table{margin:0}.admin-table th{font-size:.67rem;color:#667085;text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid #edf0f4;background:#f8fafc}.admin-table td{font-size:.76rem;border-color:#f2f4f7}.customer-person{display:flex;align-items:center;gap:10px}.customer-avatar{display:grid;flex:0 0 40px;width:40px;height:40px;place-items:center;border-radius:12px;background:#eff6ff;color:#2563eb}.customer-person div{display:flex;flex-direction:column}.customer-person strong{font-size:.77rem}.customer-person small{margin-top:2px;color:#98a2b3;font-size:.62rem}.contact-line{display:inline-flex;align-items:center;gap:6px;color:#475467}.contact-line i{color:#98a2b3}.address-cell{display:flex;align-items:flex-start;gap:7px;max-width:260px;white-space:normal;color:#475467;line-height:1.35}.address-cell i{margin-top:2px;color:#2563eb}.verify-badge{display:inline-flex;align-items:center;gap:5px;padding:5px 8px;border-radius:999px;font-size:.61rem;font-weight:800}.verify-badge.verified{background:#ecfdf3;color:#15803d}.verify-badge.unverified{background:#fffbeb;color:#b45309}.status-toggle{display:inline-flex;align-items:center;gap:7px;border:0;border-radius:999px;padding:5px 9px;background:#f2f4f7;color:#667085;cursor:pointer}.status-toggle span{width:8px;height:8px;border-radius:50%;background:#98a2b3}.status-toggle b{font-size:.61rem}.status-toggle.on{background:#ecfdf3;color:#15803d}.status-toggle.on span{background:#22c55e}.action-buttons{display:flex;justify-content:flex-end;gap:5px}.empty-state{text-align:center;padding:48px!important;color:#98a2b3}.admin-modal-backdrop{background:rgba(15,23,42,.5);backdrop-filter:blur(4px)}.customer-modal{border-radius:20px;overflow:hidden}.modal-kicker{color:#4f46e5;font-size:8px;font-weight:900;letter-spacing:.14em;margin-bottom:3px}@media(max-width:767px){.customer-head{align-items:stretch;flex-direction:column}.customer-summary{align-self:flex-start}}
</style>
