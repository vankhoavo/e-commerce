<script setup lang="ts">
import { computed } from 'vue';
import type { UrlMethodPair } from '@inertiajs/core';
import { router } from '@inertiajs/vue3';
import { usePasskeyVerify } from '@laravel/passkeys/vue';
import { KeyRound } from '@lucide/vue';
import { Spinner } from '@/components/ui/spinner';

type Props = {
    routes?: { options: UrlMethodPair; submit: UrlMethodPair };
    label?: string;
    loadingLabel?: string;
    separator?: string;
};

const props = defineProps<Props>();

const { verify, isLoading, error, isSupported } = usePasskeyVerify({
    ...(props.routes ? { routes: { options: props.routes.options.url, submit: props.routes.submit.url } } : {}),
    onSuccess: (response) => router.visit(response.redirect ?? '/dashboard'),
});

const displayError = computed(() => {
    if (!error.value) return '';

    const message = String(error.value);
    if (/passkey operation was cancelled|operation was cancelled|notallowederror/i.test(message)) {
        return 'Thao tác Passkey đã bị hủy. Bạn có thể thử lại hoặc xác nhận bằng mật khẩu.';
    }

    return message;
});
</script>

<template>
    <div v-if="isSupported" class="passkey-verify-block">
        <button type="button" class="passkey-verify-button" @click="verify" :disabled="isLoading">
            <Spinner v-if="isLoading" :size="16" />
            <KeyRound v-else :size="17" />
            {{ isLoading ? (props.loadingLabel ?? 'Đang xác nhận...') : (props.label ?? 'Xác nhận bằng Passkey') }}
        </button>

        <div v-if="displayError" class="passkey-verify-error" role="status">
            <i class="bi bi-info-circle-fill" aria-hidden="true" />
            <span>{{ displayError }}</span>
        </div>

        <div class="passkey-separator"><span>{{ props.separator ?? 'Hoặc tiếp tục bằng Email' }}</span></div>
    </div>
</template>

<style scoped>
.passkey-verify-block{width:100%}
.passkey-verify-button{display:flex;align-items:center;justify-content:center;gap:9px;width:100%;height:45px;padding:0 16px;border:1px solid #dfe3ea;border-radius:11px;color:#344054;background:#fff;font-size:11px;font-weight:800;box-shadow:0 2px 7px rgba(16,24,40,.035);outline:0;cursor:pointer;transition:.18s ease}
.passkey-verify-button:hover:not(:disabled){border-color:#b8c8e7;color:#2563eb;background:#f8fbff;transform:translateY(-1px);box-shadow:0 8px 18px rgba(16,24,40,.07)}
.passkey-verify-button:focus,.passkey-verify-button:focus-visible{outline:0;box-shadow:0 0 0 3px rgba(37,99,235,.1)}
.passkey-verify-button:disabled{opacity:.65;cursor:not-allowed}
.passkey-verify-error{display:flex;align-items:flex-start;justify-content:center;gap:7px;margin:10px 0 0;padding:9px 11px;border:1px solid #f0e1bd;border-radius:10px;color:#8a6414;background:#fffaf0;font-size:10px;line-height:1.45;text-align:left}
.passkey-verify-error i{flex:0 0 auto;margin-top:1px;color:#c58a17;font-size:11px}
.passkey-separator{display:flex;align-items:center;gap:11px;margin:18px 0;color:#98a2b3;font-size:9px;font-weight:700}
.passkey-separator:before,.passkey-separator:after{content:"";height:1px;flex:1;background:#eaecf0}
.passkey-separator span{white-space:nowrap}
</style>
