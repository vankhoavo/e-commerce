<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import TeamInvitationAlert from '@/components/TeamInvitationAlert.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import type { TeamInvitationContext } from '@/types';

defineOptions({
    layout: {
        title: 'Đăng nhập tài khoản',
        description: 'Đăng nhập bằng email và mật khẩu của bạn',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
    teamInvitation?: TeamInvitationContext | null;
}>();
</script>

<template>
    <Head title="Đăng nhập" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <TeamInvitationAlert
        v-if="teamInvitation"
        :invitation="teamInvitation"
        action="Đăng nhập"
    />

    <PasskeyVerify />

    <a
        href="/auth/google"
        class="mb-5 flex h-10 w-full items-center justify-center gap-2 rounded-md border border-input bg-background px-4 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground"
        data-test="google-login-button"
    >
        <svg viewBox="0 0 24 24" class="size-4" aria-hidden="true">
            <path fill="#4285F4" d="M21.35 12.23c0-.79-.07-1.55-.23-2.27H12v4.3h5.22a4.46 4.46 0 0 1-1.94 2.93v2.43h3.14c1.84-1.7 2.93-4.2 2.93-7.39Z"/>
            <path fill="#34A853" d="M12 21.96c2.63 0 4.84-.87 6.45-2.34l-3.14-2.43c-.87.58-1.98.92-3.31.92-2.54 0-4.69-1.72-5.46-4.03H3.3v2.51A9.74 9.74 0 0 0 12 21.96Z"/>
            <path fill="#FBBC05" d="M6.54 14.08A5.85 5.85 0 0 1 6.23 12c0-.72.12-1.42.31-2.08V7.41H3.3A9.99 9.99 0 0 0 2.25 12c0 1.66.4 3.23 1.05 4.59l3.24-2.51Z"/>
            <path fill="#EA4335" d="M12 5.89c1.43 0 2.71.49 3.72 1.45l2.79-2.79C16.84 2.98 14.63 2.04 12 2.04a9.74 9.74 0 0 0-8.7 5.37l3.24 2.51C7.31 7.61 9.46 5.89 12 5.89Z"/>
        </svg>
        Tiếp tục với Google
    </a>

    <div class="relative mb-5">
        <div class="absolute inset-0 flex items-center"><span class="w-full border-t" /></div>
        <div class="relative flex justify-center text-xs uppercase"><span class="bg-background px-2 text-muted-foreground">Hoặc</span></div>
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password">Mật khẩu</Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-sm"
                        :tabindex="5"
                    >
                        Quên mật khẩu?
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    placeholder="Mật khẩu"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <Label for="remember" class="flex items-center space-x-3">
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <span>Ghi nhớ đăng nhập</span>
                </Label>
            </div>

            <Button
                type="submit"
                class="mt-4 w-full"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                Đăng nhập
            </Button>
        </div>

        <div class="text-muted-foreground text-center text-sm">
            Chưa có tài khoản?
            <TextLink
                :href="register({ query: { invitation: teamInvitation?.code } })"
                :tabindex="5"
                data-test="register-link"
            >
                Đăng ký
            </TextLink>
        </div>
    </Form>
</template>
