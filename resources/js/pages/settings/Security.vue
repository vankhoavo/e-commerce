<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/security';
import type { Props as ManagePasskeysProps } from '@/components/ManagePasskeys.vue';
import ManagePasskeys from '@/components/ManagePasskeys.vue';
import type { Props as ManageTwoFactorProps } from '@/components/ManageTwoFactor.vue';
import ManageTwoFactor from '@/components/ManageTwoFactor.vue';
import { inject, type Ref } from 'vue';

// oxfmt-ignore
type Props = {
    passwordRules: string;
} & ManagePasskeysProps &
    ManageTwoFactorProps;

type SettingsSection = 'profile' | 'security' | 'appearance' | 'orders';

const props = defineProps<Props>();
const activeSection = inject<Ref<SettingsSection>>('techstore-settings-section');
if (activeSection) activeSection.value = 'security';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Security settings',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Security settings" />

    <h1 class="sr-only">Security settings</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Update password"
            description="Ensure your account is using a long, random password to stay secure"
        />

        <Form
            v-bind="SecurityController.update.form()"
            :options="{
                preserveScroll: true,
            }"
            reset-on-success
            :reset-on-error="[
                'password',
                'password_confirmation',
                'current_password',
            ]"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="current_password">Current password</Label>
                <PasswordInput
                    id="current_password"
                    name="current_password"
                    class="mt-1 block w-full"
                    autocomplete="current-password"
                    placeholder="Current password"
                />
                <InputError :message="errors.current_password" />
            </div>

            <div class="grid gap-2">
                <Label for="password">New password</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    placeholder="New password"
                    :passwordrules="props.passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    placeholder="Confirm password"
                    :passwordrules="props.passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <div class="flex items-center gap-4">
                <Button
                    :disabled="processing"
                    data-test="update-password-button"
                >
                    Save
                </Button>
            </div>
        </Form>
    </div>

    <div class="security-two-factor-wrap">
        <ManageTwoFactor
            :canManageTwoFactor="canManageTwoFactor"
            :requiresConfirmation="requiresConfirmation"
            :twoFactorEnabled="twoFactorEnabled"
        />
        <Link href="/forgot-password" class="security-recovery-link">
            <span class="security-recovery-icon"><i class="bi bi-envelope-lock-fill" /></span>
            <span class="security-recovery-copy"><strong>Quên mật khẩu?</strong><small>Khôi phục tài khoản bằng Email OTP.</small></span>
            <i class="bi bi-arrow-right security-recovery-arrow" />
        </Link>
    </div>

    <ManagePasskeys
        :canManagePasskeys="canManagePasskeys"
        :passkeys="passkeys"
    />
</template>

<style scoped>
.security-two-factor-wrap{width:100%;margin-top:24px}
.security-recovery-link{display:flex;align-items:center;gap:11px;width:100%;margin-top:12px;padding:11px 12px;border:1px solid #e4e7ec;border-radius:11px;color:#344054;background:#fff;text-decoration:none;transition:.18s ease}
.security-recovery-link:hover{border-color:#bfd3f8;color:#1d4ed8;background:#f8fbff;box-shadow:0 5px 14px rgba(37,99,235,.07);text-decoration:none}
.security-recovery-link:focus,.security-recovery-link:focus-visible{outline:0;box-shadow:0 0 0 3px rgba(37,99,235,.10);text-decoration:none}
.security-recovery-icon{display:grid;width:32px;height:32px;flex:0 0 32px;place-items:center;border-radius:9px;color:#2563eb;background:#eff6ff;font-size:14px}
.security-recovery-copy{display:flex;min-width:0;flex:1;flex-direction:column;gap:2px}
.security-recovery-copy strong{font-size:10px;font-weight:850}
.security-recovery-copy small{color:#98a2b3;font-size:8px;line-height:1.4}
.security-recovery-arrow{color:#98a2b3;font-size:11px}
.security-recovery-link:hover .security-recovery-arrow{color:#2563eb}
</style>
