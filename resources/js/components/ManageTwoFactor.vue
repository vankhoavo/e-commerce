<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ShieldCheck } from '@lucide/vue';
import { onUnmounted, ref } from 'vue';
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import { Button } from '@/components/ui/button';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { disable, enable } from '@/routes/two-factor';

export type Props = { canManageTwoFactor?: boolean; requiresConfirmation?: boolean; twoFactorEnabled?: boolean };
withDefaults(defineProps<Props>(), { canManageTwoFactor: false, requiresConfirmation: false, twoFactorEnabled: false });
const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref(false);
onUnmounted(() => clearTwoFactorAuthData());
</script>

<template>
    <div v-if="canManageTwoFactor" class="two-factor-manager">
        <div v-if="!twoFactorEnabled" class="two-factor-state">
            <div class="two-factor-explain"><span class="two-factor-mini-icon"><ShieldCheck :size="18" /></span><div><strong>Bảo vệ tài khoản bằng mã xác thực</strong><p>Sau khi bật, TechStore sẽ yêu cầu mã 6 số từ ứng dụng xác thực khi bạn đăng nhập.</p></div></div>
            <Form v-if="!hasSetupData" v-bind="enable.form()" @success="showSetupModal = true" #default="{ processing }"><Button class="two-factor-button" type="submit" :disabled="processing"><ShieldCheck :size="15" />{{ processing ? 'Đang khởi tạo...' : 'Bật xác thực 2 lớp' }}</Button></Form>
            <Button v-else class="two-factor-button" @click="showSetupModal = true"><ShieldCheck :size="15" />Tiếp tục thiết lập</Button>
        </div>
        <div v-else class="two-factor-state enabled"><div class="two-factor-explain"><span class="two-factor-mini-icon enabled"><i class="bi bi-check-lg" /></span><div><strong>Xác thực 2 lớp đang bật</strong><p>Tài khoản được bảo vệ bằng mã xác thực từ ứng dụng trên điện thoại.</p></div></div><Form v-bind="disable.form()" #default="{ processing }"><Button variant="destructive" type="submit" :disabled="processing">{{ processing ? 'Đang tắt...' : 'Tắt xác thực 2 lớp' }}</Button></Form><TwoFactorRecoveryCodes /></div>
        <TwoFactorSetupModal v-model:isOpen="showSetupModal" :requiresConfirmation="requiresConfirmation" :twoFactorEnabled="twoFactorEnabled" />
    </div>
</template>

<style scoped>
.two-factor-manager{width:100%}.two-factor-state{display:flex;flex-direction:column;gap:13px}.two-factor-explain{display:flex;align-items:flex-start;gap:10px;padding:12px;border:1px solid #e7ecf3;border-radius:12px;background:#fbfcfe}.two-factor-mini-icon{display:grid;flex:0 0 34px;width:34px;height:34px;place-items:center;border-radius:10px;color:#2563eb;background:#eef5ff}.two-factor-mini-icon.enabled{color:#15915c;background:#eafaf2}.two-factor-explain strong{display:block;color:#344054;font-size:11px;font-weight:850}.two-factor-explain p{margin:3px 0 0;color:#98a2b3;font-size:9px;line-height:1.55}.two-factor-button{display:inline-flex!important;align-items:center;gap:7px!important;min-height:39px!important;padding:0 14px!important;border-radius:10px!important;background:#2563eb!important;color:#fff!important;font-size:10px!important;font-weight:800!important}.two-factor-state.enabled>.destructive{align-self:flex-start}.two-factor-manager :deep(.destructive){min-height:39px;border-radius:10px;font-size:10px}.two-factor-manager :deep([data-slot='dialog-content']){border-radius:20px}
</style>
