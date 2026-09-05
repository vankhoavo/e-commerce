<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { KeyRound } from '@lucide/vue';
import type { Passkey } from '@/types/auth';
import PasskeyItem from '@/components/PasskeyItem.vue';
import PasskeyRegister from '@/components/PasskeyRegister.vue';
import { destroy } from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyRegistrationController';

export type Props = { canManagePasskeys?: boolean; passkeys?: Passkey[] };
withDefaults(defineProps<Props>(), { canManagePasskeys: false, passkeys: () => [] });
const handleDelete = (id: number, onError: () => void) => router.delete(destroy.url(id), { preserveScroll: true, onError });
const handleRegisterSuccess = () => router.reload();
</script>

<template>
    <div v-if="canManagePasskeys" class="passkey-manager">
        <div class="passkey-list" :class="{ 'has-items': passkeys.length }">
            <template v-if="passkeys.length">
                <PasskeyItem v-for="passkey in passkeys" :key="passkey.id" :passkey="passkey" @remove="handleDelete" />
            </template>
            <div v-else class="passkey-empty">
                <div class="passkey-empty-icon"><KeyRound :size="24" stroke-width="1.8" /></div>
                <h4>Chưa có Passkey</h4>
                <p>Thêm Passkey để đăng nhập nhanh bằng vân tay, khuôn mặt hoặc thiết bị tin cậy.</p>
            </div>
        </div>
        <div class="passkey-action"><PasskeyRegister @success="handleRegisterSuccess" /></div>
    </div>
</template>

<style scoped>
.passkey-manager{width:100%}.passkey-list{min-height:154px;overflow:hidden;border:1px solid #e6eaf0;border-radius:15px;background:linear-gradient(180deg,#fcfdff,#f8fafc)}.passkey-list.has-items{background:#fff}.passkey-empty{min-height:154px;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px 18px;text-align:center}.passkey-empty-icon{width:56px;height:56px;display:grid;place-items:center;margin-bottom:12px;border:1px solid #e5d8ff;border-radius:16px;color:#7c3aed;background:linear-gradient(145deg,#fbf8ff,#f3ecff);box-shadow:0 8px 22px rgba(124,58,237,.1)}.passkey-empty h4{margin:0;color:#1d2939;font-size:.92rem;font-weight:850;letter-spacing:-.01em}.passkey-empty p{max-width:390px;margin:6px 0 0;color:#98a2b3;font-size:.7rem;line-height:1.55}.passkey-action{margin-top:14px}.passkey-action :deep(button){min-height:40px!important;padding:0 16px!important;border:1px solid #d6d9e0!important;border-radius:10px!important;color:#344054!important;background:#fff!important;box-shadow:0 2px 5px rgba(16,24,40,.04)!important;font-size:.75rem!important;font-weight:800!important;transition:all .18s ease}.passkey-action :deep(button:hover){border-color:#a9c2fb!important;color:#2563eb!important;background:#f7faff!important;box-shadow:0 7px 16px rgba(37,99,235,.09)!important;transform:translateY(-1px)}.passkey-action :deep(button:focus-visible){box-shadow:0 0 0 3px rgba(37,99,235,.12)!important}
</style>
