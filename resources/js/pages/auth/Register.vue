<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TeamInvitationAlert from '@/components/TeamInvitationAlert.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';
import type { TeamInvitationContext } from '@/types';

defineProps<{ passwordRules: string; teamInvitation?: TeamInvitationContext | null }>();
defineOptions({
    layout: {
        title: 'Tạo tài khoản',
        description: 'Đăng ký tài khoản để bắt đầu mua sắm tại TechStore.',
    },
});
</script>

<template>
    <Head title="Đăng ký" />

    <TeamInvitationAlert
        v-if="teamInvitation"
        :invitation="teamInvitation"
        action="Đăng ký"
    />

    <div class="register-benefit">
        <div class="register-benefit-icon">
            <i class="bi bi-gift-fill" aria-hidden="true" />
        </div>
        <div class="register-benefit-content">
            <div class="register-benefit-title">Ưu đãi thành viên mới</div>
            <div class="register-benefit-text">
                Đăng ký hôm nay để nhận thông tin khuyến mãi sớm nhất.
            </div>
        </div>
        <span class="register-benefit-arrow" aria-hidden="true">
            <i class="bi bi-chevron-right" />
        </span>
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="register-form"
    >
        <div class="register-field">
            <label for="name" class="register-label">Họ và tên</label>
            <div class="register-input-wrap">
                <i class="bi bi-person" aria-hidden="true" />
                <input
                    id="name"
                    type="text"
                    name="name"
                    class="register-input"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Nguyễn Văn A"
                />
            </div>
            <InputError :message="errors.name" />
        </div>

        <div class="register-field">
            <label for="email" class="register-label">Email</label>
            <div class="register-input-wrap">
                <i class="bi bi-envelope" aria-hidden="true" />
                <input
                    id="email"
                    type="email"
                    name="email"
                    class="register-input"
                    required
                    autocomplete="email"
                    placeholder="email@example.com"
                />
            </div>
            <InputError :message="errors.email" />
        </div>

        <div class="register-field">
            <label for="password" class="register-label">Mật khẩu</label>
            <div class="register-input-wrap">
                <i class="bi bi-lock" aria-hidden="true" />
                <input
                    id="password"
                    type="password"
                    name="password"
                    class="register-input"
                    required
                    autocomplete="new-password"
                    placeholder="Nhập mật khẩu"
                    :passwordrules="passwordRules"
                />
            </div>
            <InputError :message="errors.password" />
        </div>

        <div class="register-field">
            <label for="password_confirmation" class="register-label">Xác nhận mật khẩu</label>
            <div class="register-input-wrap">
                <i class="bi bi-shield-check" aria-hidden="true" />
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    class="register-input"
                    required
                    autocomplete="new-password"
                    placeholder="Nhập lại mật khẩu"
                    :passwordrules="passwordRules"
                />
            </div>
            <InputError :message="errors.password_confirmation" />
        </div>

        <div class="register-password-hint">
            <i class="bi bi-shield-check" aria-hidden="true" />
            <span>Sử dụng mật khẩu mạnh, có chữ hoa, chữ thường và số.</span>
        </div>

        <button
            type="submit"
            class="register-submit"
            :disabled="processing"
        >
            <span v-if="processing" class="spinner-border spinner-border-sm" aria-hidden="true" />
            <i v-else class="bi bi-person-plus-fill" aria-hidden="true" />
            {{ processing ? 'Đang tạo tài khoản...' : 'Tạo tài khoản' }}
            <i v-if="!processing" class="bi bi-arrow-right" aria-hidden="true" />
        </button>
    </Form>

    <div class="register-terms">
        Bằng việc đăng ký, bạn đồng ý với các điều khoản sử dụng của TechStore.
    </div>

    <div class="register-bottom">
        <span>Đã có tài khoản?</span>
        <Link
            :href="teamInvitation ? login.url({ query: { invitation: teamInvitation.code } }) : login()"
        >
            Đăng nhập ngay
            <i class="bi bi-arrow-right" aria-hidden="true" />
        </Link>
    </div>
</template>

<style scoped>
.register-benefit {
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 68px;
    margin: 0 0 22px;
    padding: 12px 14px;
    border: 1px solid #dbe7ff;
    border-radius: 15px;
    background: linear-gradient(135deg, #f7faff 0%, #eff6ff 100%);
}

.register-benefit-icon {
    display: grid;
    flex: 0 0 40px;
    width: 40px;
    height: 40px;
    place-items: center;
    border-radius: 11px;
    color: #2563eb;
    background: #fff;
    box-shadow: 0 5px 14px rgba(37, 99, 235, .1);
    font-size: 16px;
}

.register-benefit-content {
    min-width: 0;
    flex: 1;
}

.register-benefit-title {
    color: #172033;
    font-size: 13px;
    font-weight: 850;
    line-height: 1.25;
}

.register-benefit-text {
    margin-top: 3px;
    color: #7b8799;
    font-size: 11px;
    line-height: 1.45;
}

.register-benefit-arrow {
    color: #9ab3e8;
    font-size: 12px;
}

.register-form {
    display: grid;
    gap: 15px;
}

.register-field {
    min-width: 0;
}

.register-label {
    display: block;
    margin: 0 0 7px;
    color: #344054;
    font-size: 12px;
    font-weight: 800;
}

.register-input-wrap {
    display: flex;
    align-items: center;
    height: 47px;
    padding: 0 13px;
    border: 1px solid #dfe4ec;
    border-radius: 12px;
    background: #fff;
    transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
}

.register-input-wrap > i {
    flex: 0 0 auto;
    margin-right: 9px;
    color: #98a2b3;
    font-size: 14px;
}

.register-input-wrap:focus-within {
    border-color: #86aaf5;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, .08);
}

.register-input-wrap:focus-within > i {
    color: #2563eb;
}

.register-input {
    width: 100%;
    min-width: 0;
    height: 100%;
    padding: 0;
    border: 0;
    outline: 0;
    color: #172033;
    background: transparent;
    font-size: 13px;
}

.register-input::placeholder {
    color: #a0a8b5;
}

.register-password-hint {
    display: flex;
    align-items: center;
    gap: 7px;
    margin-top: -3px;
    color: #8a95a5;
    font-size: 10px;
    line-height: 1.45;
}

.register-password-hint i {
    color: #2563eb;
    font-size: 12px;
}

.register-submit {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    min-height: 47px;
    margin-top: 2px;
    padding: 0 16px;
    border: 0;
    border-radius: 12px;
    color: #fff;
    background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
    box-shadow: 0 9px 22px rgba(37, 99, 235, .2);
    font-size: 13px;
    font-weight: 800;
    transition: transform .18s ease, box-shadow .18s ease, opacity .18s ease;
}

.register-submit:not(:disabled):hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 26px rgba(37, 99, 235, .27);
}

.register-submit:disabled {
    cursor: not-allowed;
    opacity: .68;
}

.register-terms {
    margin: 13px 8px 0;
    color: #98a2b3;
    font-size: 10px;
    line-height: 1.55;
    text-align: center;
}

.register-bottom {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    margin-top: 18px;
    padding-top: 17px;
    border-top: 1px solid #edf0f4;
    color: #7b8799;
    font-size: 12px;
}

.register-bottom a {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    color: #2563eb;
    font-weight: 800;
}

.register-bottom a:hover {
    color: #1d4ed8;
}

@media (max-width: 575.98px) {
    .register-benefit {
        padding: 11px;
    }

    .register-benefit-text {
        font-size: 10px;
    }
}
</style>
