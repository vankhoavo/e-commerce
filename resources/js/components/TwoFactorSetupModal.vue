<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { useClipboard } from '@vueuse/core';
import { Check, Copy, ScanLine } from '@lucide/vue';
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';
import AlertError from '@/components/AlertError.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp';
import { Spinner } from '@/components/ui/spinner';
import { useAppearance } from '@/composables/useAppearance';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { confirm } from '@/routes/two-factor';

type Props = { requiresConfirmation: boolean; twoFactorEnabled: boolean };
const { resolvedAppearance } = useAppearance();
const props = defineProps<Props>();
const isOpen = defineModel<boolean>('isOpen');
const { copy, copied } = useClipboard();
const { qrCodeSvg, manualSetupKey, clearSetupData, fetchSetupData, errors } = useTwoFactorAuth();
const showVerificationStep = ref(false);
const code = ref('');
const pinInputContainerRef = useTemplateRef('pinInputContainerRef');

const modalConfig = computed(() => {
    if (props.twoFactorEnabled) return { title: 'Xác thực 2 lớp đã được bật', description: 'Tài khoản của bạn đã được bảo vệ. Bạn có thể đóng cửa sổ này.', buttonText: 'Đóng' };
    if (showVerificationStep.value) return { title: 'Xác nhận mã bảo mật', description: 'Mở ứng dụng xác thực và nhập mã gồm 6 chữ số.', buttonText: 'Xác nhận' };
    return { title: 'Thiết lập xác thực 2 lớp', description: 'Quét mã QR bằng ứng dụng xác thực trên điện thoại để liên kết tài khoản.', buttonText: props.requiresConfirmation ? 'Tiếp tục' : 'Hoàn tất' };
});

const handleModalNextStep = () => {
    if (props.requiresConfirmation) {
        showVerificationStep.value = true;
        nextTick(() => pinInputContainerRef.value?.querySelector('input')?.focus());
        return;
    }
    clearSetupData();
    isOpen.value = false;
};
const resetModalState = () => { if (props.twoFactorEnabled) clearSetupData(); showVerificationStep.value = false; code.value = ''; };
watch(() => isOpen.value, async (open) => { if (!open) { resetModalState(); return; } if (!qrCodeSvg.value) await fetchSetupData(); });
</script>

<template>
    <Dialog :open="isOpen" @update:open="isOpen = $event">
        <DialogContent class="two-factor-dialog sm:max-w-md">
            <DialogHeader class="two-factor-header">
                <div class="two-factor-modal-icon"><ScanLine :size="24" /></div>
                <span class="two-factor-eyebrow">TECHSTORE SECURITY</span>
                <DialogTitle>{{ modalConfig.title }}</DialogTitle>
                <DialogDescription>{{ modalConfig.description }}</DialogDescription>
            </DialogHeader>

            <div class="two-factor-modal-body">
                <template v-if="!showVerificationStep">
                    <AlertError v-if="errors?.length" :errors="errors" />
                    <template v-else>
                        <div class="two-factor-steps"><span class="active">1</span><i></i><span :class="{ active: props.requiresConfirmation }">2</span></div>
                        <div class="two-factor-qr-card"><div v-if="!qrCodeSvg" class="two-factor-qr-loading"><Spinner :size="26" /></div><div v-else class="two-factor-qr" :style="{ filter: resolvedAppearance === 'dark' ? 'invert(1) brightness(1.5)' : undefined }" v-html="qrCodeSvg"></div></div>
                        <p class="two-factor-scan"><strong>Bước 1 — Quét mã QR</strong><br>Trong Google Authenticator, Microsoft Authenticator hoặc ứng dụng TOTP tương thích, chọn thêm tài khoản rồi quét mã trên.</p>
                        <button v-if="manualSetupKey" type="button" class="two-factor-key" @click="copy(manualSetupKey)"><span><small>Mã thiết lập thủ công</small><strong>{{ manualSetupKey }}</strong></span><i v-if="copied"><Check :size="16" /></i><i v-else><Copy :size="16" /></i></button>
                        <Button class="two-factor-next" @click="handleModalNextStep">{{ modalConfig.buttonText }} <i class="bi bi-arrow-right" /></Button>
                    </template>
                </template>

                <Form v-else v-bind="confirm.form()" error-bag="confirmTwoFactorAuthentication" reset-on-error @finish="code = ''" @success="isOpen = false" v-slot="{ errors, processing }">
                    <input type="hidden" name="code" :value="code" />
                    <div ref="pinInputContainerRef" class="two-factor-verify">
                        <div class="verify-icon"><i class="bi bi-shield-lock-fill" /></div>
                        <h4>Bước 2 — Nhập mã xác nhận</h4>
                        <p>Nhập mã 6 số đang hiển thị trong ứng dụng xác thực.</p>
                        <InputOTP id="otp" v-model="code" :maxlength="6" :disabled="processing" autofocus><InputOTPGroup><InputOTPSlot v-for="index in 6" :key="index" :index="index - 1" /></InputOTPGroup></InputOTP>
                        <InputError :message="errors?.code" />
                        <div class="verify-actions"><Button type="button" variant="outline" @click="showVerificationStep = false" :disabled="processing">Quay lại</Button><Button type="submit" :disabled="processing || code.length < 6">{{ processing ? 'Đang xác nhận...' : 'Xác nhận & bật 2 lớp' }}</Button></div>
                    </div>
                </Form>
            </div>
        </DialogContent>
    </Dialog>
</template>

<style scoped>
.two-factor-dialog{border-radius:22px!important;padding:0!important;overflow:hidden}.two-factor-header{align-items:center;padding:28px 28px 18px;background:linear-gradient(135deg,#f7faff,#f4f3ff);border-bottom:1px solid #e9edf4}.two-factor-modal-icon{display:grid;width:56px;height:56px;place-items:center;margin-bottom:10px;border:1px solid #cfe0ff;border-radius:16px;color:#2563eb;background:#fff;box-shadow:0 9px 24px rgba(37,99,235,.12)}.two-factor-eyebrow{color:#2563eb;font-size:8px;font-weight:900;letter-spacing:.18em}.two-factor-header :deep([data-slot='dialog-title']){margin-top:5px;font-size:20px;font-weight:850}.two-factor-header :deep([data-slot='dialog-description']){max-width:390px;color:#667085;font-size:10px;line-height:1.55;text-align:center}.two-factor-modal-body{padding:22px 28px 26px}.two-factor-steps{display:flex;align-items:center;justify-content:center;gap:7px;margin-bottom:14px}.two-factor-steps span{display:grid;width:22px;height:22px;place-items:center;border-radius:50%;color:#98a2b3;background:#edf0f5;font-size:9px;font-weight:850}.two-factor-steps span.active{color:#fff;background:#2563eb}.two-factor-steps i{width:40px;height:1px;background:#dfe4ec}.two-factor-qr-card{display:grid;width:190px;height:190px;place-items:center;margin:0 auto;padding:12px;border:1px solid #dfe6f1;border-radius:16px;background:#fff;box-shadow:0 10px 28px rgba(16,24,40,.07)}.two-factor-qr{display:flex;width:100%;height:100%;align-items:center;justify-content:center}.two-factor-qr :deep(svg){width:100%;height:100%}.two-factor-qr-loading{color:#2563eb}.two-factor-scan{margin:15px auto 11px;max-width:370px;color:#667085;font-size:10px;line-height:1.55;text-align:center}.two-factor-scan strong{color:#344054}.two-factor-key{display:flex;width:100%;align-items:center;justify-content:space-between;gap:10px;padding:9px 11px;border:1px solid #e1e7f0;border-radius:10px;background:#f8fafc;color:#344054;text-align:left;cursor:pointer}.two-factor-key small,.two-factor-key strong{display:block}.two-factor-key small{margin-bottom:3px;color:#98a2b3;font-size:8px}.two-factor-key strong{font-size:10px;letter-spacing:.05em;word-break:break-all}.two-factor-key>i{display:grid;place-items:center;flex:0 0 30px;width:30px;height:30px;border-radius:8px;color:#2563eb;background:#fff}.two-factor-next{width:100%;height:42px;margin-top:11px!important;border-radius:10px!important;background:#2563eb!important;font-size:10px!important;font-weight:800!important}.two-factor-next i{margin-left:4px}.two-factor-verify{display:flex;flex-direction:column;align-items:center;text-align:center}.verify-icon{display:grid;width:50px;height:50px;place-items:center;margin-bottom:10px;border-radius:14px;color:#2563eb;background:#eef5ff;font-size:20px}.two-factor-verify h4{margin:0;color:#101828;font-size:16px;font-weight:850}.two-factor-verify p{margin:5px 0 18px;color:#667085;font-size:10px}.verify-actions{display:flex;width:100%;gap:8px;margin-top:20px}.verify-actions :deep(button){flex:1;min-height:40px;border-radius:10px;font-size:10px}.two-factor-dialog :deep([data-slot='input-otp']){gap:5px}.two-factor-dialog :deep([data-slot='input-otp-slot']){width:38px;height:44px;border-radius:9px;font-size:16px;font-weight:800}@media(max-width:500px){.two-factor-header,.two-factor-modal-body{padding-left:20px;padding-right:20px}.two-factor-qr-card{width:170px;height:170px}}
</style>
