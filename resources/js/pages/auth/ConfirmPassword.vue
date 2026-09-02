<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { store } from '@/routes/password/confirm';
import { index as confirmOptions, store as confirmStore } from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import PasskeyVerify from '@/components/PasskeyVerify.vue';

defineOptions({
    layout: {
        title: 'Xác nhận mật khẩu',
        description: 'Đây là khu vực bảo mật. Vui lòng xác nhận mật khẩu trước khi tiếp tục.',
    },
});
</script>

<template>
    <Head title="Xác nhận mật khẩu" />

    <div class="confirm-password-page">
        <div class="confirm-security-badge"><i class="bi bi-shield-lock-fill" /></div>
        <span class="confirm-eyebrow">TECHSTORE SECURITY</span>
        <h2>Xác nhận mật khẩu</h2>
        <p class="confirm-description">Vui lòng xác nhận danh tính để tiếp tục vào khu vực bảo mật của tài khoản.</p>

        <div class="confirm-passkey-wrap">
            <PasskeyVerify
                :routes="{ options: confirmOptions(), submit: confirmStore() }"
                label="Xác nhận bằng Passkey"
                loading-label="Đang xác nhận..."
                separator="Hoặc xác nhận bằng mật khẩu"
            />
        </div>

        <Form v-bind="store.form()" reset-on-success v-slot="{ errors, processing }" class="confirm-form">
            <div class="confirm-field">
                <label for="password">Mật khẩu</label>
                <PasswordInput id="password" name="password" required autocomplete="current-password" autofocus />
                <InputError :message="errors.password" />
            </div>

            <button type="submit" class="confirm-submit" :disabled="processing">
                <span v-if="processing" class="spinner-border spinner-border-sm" />
                <i v-else class="bi bi-shield-check" />
                {{ processing ? 'Đang xác nhận...' : 'Xác nhận mật khẩu' }}
            </button>
        </Form>

        <div class="confirm-security-note"><i class="bi bi-shield-check" /> Thông tin xác nhận của bạn được bảo vệ an toàn.</div>
    </div>
</template>

<style scoped>
.confirm-password-page{color:#101828}.confirm-security-badge{display:grid;width:48px;height:48px;place-items:center;margin-bottom:10px;border:1px solid #cfe0ff;border-radius:14px;color:#2563eb;background:#eff6ff;box-shadow:0 7px 18px rgba(37,99,235,.09)}.confirm-eyebrow{display:block;color:#2563eb;font-size:9px;font-weight:900;letter-spacing:.16em}.confirm-password-page h2{margin:6px 0 5px;color:#101828;font-size:25px;font-weight:900;letter-spacing:-.04em}.confirm-description{margin:0;color:#667085;font-size:11px;line-height:1.6}.confirm-passkey-wrap{margin-top:22px}.confirm-passkey-wrap :deep(button){outline:0!important;border-radius:10px!important;font-size:11px!important;font-weight:800!important;box-shadow:none!important}.confirm-passkey-wrap :deep(button:hover){border-color:#a9c4f7!important;color:#2563eb!important;background:#f8fbff!important}.confirm-passkey-wrap :deep(button:focus),.confirm-passkey-wrap :deep(button:focus-visible){outline:0!important;box-shadow:0 0 0 3px rgba(37,99,235,.1)!important}.confirm-passkey-wrap :deep(.relative.my-6){margin:17px 0!important}.confirm-passkey-wrap :deep(.uppercase){text-transform:none!important}.confirm-passkey-wrap :deep(.text-xs){font-size:10px!important}.confirm-form{display:grid;gap:13px;margin-top:3px}.confirm-field label{display:block;margin-bottom:7px;color:#344054;font-size:11px;font-weight:800}.confirm-field :deep(.password-input-field){height:45px!important;min-height:45px!important;border:1px solid #dfe3ea!important;border-radius:11px!important;background:#fff!important;color:#101828!important;box-shadow:none!important}.confirm-field :deep(.password-input-field:focus){border-color:#8fb0f4!important;box-shadow:0 0 0 4px rgba(37,99,235,.08)!important}.confirm-submit{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;height:45px;margin-top:2px;border:1px solid #2563eb;border-radius:11px;color:#fff;background:#2563eb;font-size:11px;font-weight:850;box-shadow:0 8px 18px rgba(37,99,235,.16);cursor:pointer;transition:.18s ease}.confirm-submit:hover:not(:disabled){background:#1d4ed8;transform:translateY(-1px)}.confirm-submit:focus,.confirm-submit:focus-visible{outline:0;box-shadow:0 0 0 3px rgba(37,99,235,.13),0 8px 18px rgba(37,99,235,.16)}.confirm-submit:disabled{opacity:.65;cursor:not-allowed}.confirm-security-note{display:flex;align-items:center;justify-content:center;gap:6px;margin-top:22px;color:#98a2b3;font-size:9px}.confirm-security-note i{color:#2563eb}@media(max-width:575px){.confirm-password-page h2{font-size:22px}}
</style>
