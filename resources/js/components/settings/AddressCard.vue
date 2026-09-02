<script setup lang="ts">
import { Form, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { MapPin } from '@lucide/vue';

const page = usePage();
const user = computed(() => (page.props as any).auth?.user ?? {});
</script>

<template>
    <section class="profile-address-card settings-card">
        <div class="address-card-heading">
            <span class="address-card-icon"><MapPin :size="18" /></span>
            <div>
                <h3>Địa chỉ nhận hàng</h3>
                <p>Lưu địa chỉ thường dùng để TechStore tự điền khi bạn đặt hàng.</p>
            </div>
        </div>

        <Form action="/settings/address" method="post" v-slot="{ errors, processing }" class="address-form">
            <div class="address-field">
                <label for="profile-address">Địa chỉ</label>
                <textarea id="profile-address" name="address" :value="user.address ?? ''" rows="3" maxlength="500" placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành phố" />
                <small v-if="errors.address" class="address-error">{{ errors.address }}</small>
            </div>
            <div class="address-form-footer">
                <span><i class="bi bi-info-circle" /> Địa chỉ này sẽ được gợi ý khi thanh toán.</span>
                <button type="submit" :disabled="processing">
                    <i class="bi bi-check2" /> {{ processing ? 'Đang lưu...' : 'Lưu địa chỉ' }}
                </button>
            </div>
        </Form>
    </section>
</template>

<style>
.profile-address-card{margin-top:18px;padding:22px;border:1px solid #e4e7ec;border-radius:18px;background:#fff;box-shadow:0 8px 28px rgba(16,24,40,.045)}
.address-card-heading{display:flex;align-items:flex-start;gap:11px;margin-bottom:17px}.address-card-icon{display:grid;width:38px;height:38px;flex:0 0 38px;place-items:center;border-radius:11px;color:#2563eb;background:#eff6ff}.address-card-heading h3{margin:0;color:#101828;font-size:.96rem;font-weight:850}.address-card-heading p{margin:4px 0 0;color:#667085;font-size:.73rem}.address-field label{display:block;margin-bottom:6px;color:#344054;font-size:.72rem;font-weight:750}.address-field textarea{display:block;width:100%;padding:11px 12px;border:1px solid #dfe3ea;border-radius:10px;outline:0;resize:vertical;color:#101828;background:#fff;font-size:.78rem;line-height:1.5;transition:.18s}.address-field textarea:focus{border-color:#93b4f8;box-shadow:0 0 0 3px rgba(37,99,235,.09)}.address-field textarea::placeholder{color:#98a2b3}.address-form-footer{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-top:11px}.address-form-footer span{color:#98a2b3;font-size:.64rem}.address-form-footer span i{margin-right:4px;color:#2563eb}.address-form-footer button{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:39px;padding:0 14px;border:1px solid #2563eb;border-radius:10px;color:#fff;background:linear-gradient(135deg,#2563eb,#4f46e5);font-size:.72rem;font-weight:800;cursor:pointer}.address-form-footer button:disabled{opacity:.6;cursor:not-allowed}.address-error{display:block;margin-top:5px;color:#dc2626;font-size:.64rem}@media(max-width:700px){.address-form-footer{align-items:stretch;flex-direction:column}.address-form-footer button{width:100%}}
</style>
