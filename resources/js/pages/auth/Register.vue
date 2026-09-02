<script setup lang="ts">
import { ref } from 'vue';
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

const showBenefits = ref(false);

const closeBenefits = () => {
    showBenefits.value = false;
};
</script>

<template>
    <Head title="Đăng ký" />

    <TeamInvitationAlert
        v-if="teamInvitation"
        :invitation="teamInvitation"
        action="Đăng ký"
    />

    <button
        type="button"
        class="register-benefit"
        aria-haspopup="dialog"
        :aria-expanded="showBenefits"
        @click="showBenefits = true"
    >
        <span class="register-benefit-icon">
            <i class="bi bi-gift-fill" aria-hidden="true" />
        </span>
        <span class="register-benefit-content">
            <span class="register-benefit-title">Ưu đãi thành viên mới</span>
            <span class="register-benefit-text">
                Đăng ký hôm nay để nhận thông tin khuyến mãi sớm nhất.
            </span>
        </span>
        <span class="register-benefit-arrow" aria-hidden="true">
            <i class="bi bi-chevron-right" />
        </span>
    </button>

    <Teleport to="body">
        <Transition name="benefit-modal">
            <div
                v-if="showBenefits"
                class="benefit-modal-backdrop"
                role="presentation"
                @click.self="closeBenefits"
            >
                <section
                    class="benefit-modal"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="benefit-modal-title"
                >
                    <button
                        type="button"
                        class="benefit-modal-close"
                        aria-label="Đóng ưu đãi"
                        @click="closeBenefits"
                    >
                        <i class="bi bi-x-lg" aria-hidden="true" />
                    </button>

                    <div class="benefit-modal-hero">
                        <div class="benefit-modal-gift">
                            <i class="bi bi-gift-fill" aria-hidden="true" />
                        </div>
                        <div>
                            <span class="benefit-modal-eyebrow">TECHSTORE MEMBER</span>
                            <h2 id="benefit-modal-title">Đặc quyền thành viên</h2>
                            <p>Đăng ký tài khoản để mở khóa trải nghiệm mua sắm đầy đủ hơn tại TechStore.</p>
                        </div>
                    </div>

                    <div class="benefit-list">
                        <article class="benefit-item">
                            <span class="benefit-item-icon blue"><i class="bi bi-ticket-perforated-fill" /></span>
                            <div>
                                <strong>Ưu đãi & mã giảm giá</strong>
                                <p>Nhận thông tin các chương trình ưu đãi, mã giảm giá và khuyến mãi dành cho thành viên.</p>
                            </div>
                        </article>

                        <article class="benefit-item">
                            <span class="benefit-item-icon purple"><i class="bi bi-bell-fill" /></span>
                            <div>
                                <strong>Cập nhật sản phẩm mới</strong>
                                <p>Dễ dàng theo dõi sản phẩm mới, chương trình nổi bật và các thông tin từ TechStore.</p>
                            </div>
                        </article>

                        <article class="benefit-item">
                            <span class="benefit-item-icon orange"><i class="bi bi-receipt-cutoff" /></span>
                            <div>
                                <strong>Quản lý đơn hàng</strong>
                                <p>Xem lịch sử mua hàng, trạng thái giao hàng và thông tin hóa đơn ngay trong tài khoản.</p>
                            </div>
                        </article>

                        <article class="benefit-item">
                            <span class="benefit-item-icon green"><i class="bi bi-shield-check" /></span>
                            <div>
                                <strong>Tài khoản an toàn</strong>
                                <p>Bảo vệ tài khoản với xác thực Email, đổi mật khẩu và các tùy chọn bảo mật.</p>
                            </div>
                        </article>
                    </div>

                    <div class="benefit-modal-footer">
                        <span><i class="bi bi-stars" /> Nhiều tiện ích hơn khi trở thành thành viên</span>
                        <button type="button" class="benefit-modal-button" @click="closeBenefits">
                            Đã hiểu
                            <i class="bi bi-arrow-right" />
                        </button>
                    </div>
                </section>
            </div>
        </Transition>
    </Teleport>

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
                <input id="name" type="text" name="name" class="register-input" required autofocus autocomplete="name" placeholder="Nguyễn Văn A" />
            </div>
            <InputError :message="errors.name" />
        </div>

        <div class="register-field">
            <label for="email" class="register-label">Email</label>
            <div class="register-input-wrap">
                <i class="bi bi-envelope" aria-hidden="true" />
                <input id="email" type="email" name="email" class="register-input" required autocomplete="email" placeholder="email@example.com" />
            </div>
            <InputError :message="errors.email" />
        </div>

        <div class="register-field">
            <label for="password" class="register-label">Mật khẩu</label>
            <div class="register-input-wrap">
                <i class="bi bi-lock" aria-hidden="true" />
                <input id="password" type="password" name="password" class="register-input" required autocomplete="new-password" placeholder="Nhập mật khẩu" :passwordrules="passwordRules" />
            </div>
            <InputError :message="errors.password" />
        </div>

        <div class="register-field">
            <label for="password_confirmation" class="register-label">Xác nhận mật khẩu</label>
            <div class="register-input-wrap">
                <i class="bi bi-shield-check" aria-hidden="true" />
                <input id="password_confirmation" type="password" name="password_confirmation" class="register-input" required autocomplete="new-password" placeholder="Nhập lại mật khẩu" :passwordrules="passwordRules" />
            </div>
            <InputError :message="errors.password_confirmation" />
        </div>

        <div class="register-password-hint">
            <i class="bi bi-shield-check" aria-hidden="true" />
            <span>Sử dụng mật khẩu mạnh, có chữ hoa, chữ thường và số.</span>
        </div>

        <button type="submit" class="register-submit" :disabled="processing">
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
        <Link :href="teamInvitation ? login.url({ query: { invitation: teamInvitation.code } }) : login()">
            Đăng nhập ngay
            <i class="bi bi-arrow-right" aria-hidden="true" />
        </Link>
    </div>
</template>

<style scoped>
.register-benefit {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 68px;
    margin: 0 0 22px;
    padding: 12px 14px;
    border: 1px solid #dbe7ff;
    border-radius: 15px;
    background: linear-gradient(135deg, #f7faff 0%, #eff6ff 100%);
    text-align: left;
    cursor: pointer;
    transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
}

.register-benefit:hover {
    border-color: #bfd3ff;
    box-shadow: 0 8px 22px rgba(37, 99, 235, .09);
    transform: translateY(-1px);
}

.register-benefit:focus-visible {
    outline: 0;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, .12);
}

.register-benefit-icon { display: grid; flex: 0 0 40px; width: 40px; height: 40px; place-items: center; border-radius: 11px; color: #2563eb; background: #fff; box-shadow: 0 5px 14px rgba(37, 99, 235, .1); font-size: 16px; }
.register-benefit-content { min-width: 0; flex: 1; }
.register-benefit-title { display: block; color: #172033; font-size: 13px; font-weight: 850; line-height: 1.25; }
.register-benefit-text { display: block; margin-top: 3px; color: #7b8799; font-size: 11px; line-height: 1.45; }
.register-benefit-arrow { color: #9ab3e8; font-size: 12px; }

.benefit-modal-backdrop { position: fixed; inset: 0; z-index: 2000; display: grid; place-items: center; padding: 20px; background: rgba(15, 23, 42, .52); backdrop-filter: blur(7px); }
.benefit-modal { position: relative; width: min(620px, 100%); max-height: min(760px, calc(100vh - 40px)); overflow: auto; padding: 28px; border: 1px solid rgba(255,255,255,.7); border-radius: 24px; background: #fff; box-shadow: 0 28px 80px rgba(15, 23, 42, .24); }
.benefit-modal-close { position: absolute; top: 18px; right: 18px; display: grid; width: 34px; height: 34px; place-items: center; border: 1px solid #e8ecf2; border-radius: 10px; color: #667085; background: #f8fafc; cursor: pointer; transition: .18s ease; }
.benefit-modal-close:hover { color: #1d2939; background: #eef2f7; transform: rotate(4deg); }
.benefit-modal-hero { display: flex; align-items: center; gap: 16px; padding-right: 38px; }
.benefit-modal-gift { display: grid; flex: 0 0 56px; width: 56px; height: 56px; place-items: center; border-radius: 16px; color: #2563eb; background: #eef5ff; font-size: 23px; }
.benefit-modal-eyebrow { display: block; margin-bottom: 5px; color: #2563eb; font-size: 9px; font-weight: 900; letter-spacing: .15em; }
.benefit-modal h2 { margin: 0; color: #101828; font-size: 23px; font-weight: 850; letter-spacing: -.02em; }
.benefit-modal-hero p { margin: 5px 0 0; color: #7b8799; font-size: 11px; line-height: 1.55; }
.benefit-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin-top: 22px; }
.benefit-item { display: flex; gap: 11px; padding: 14px; border: 1px solid #edf0f4; border-radius: 15px; background: #fbfcfe; }
.benefit-item-icon { display: grid; flex: 0 0 34px; width: 34px; height: 34px; place-items: center; border-radius: 10px; font-size: 13px; }
.benefit-item-icon.blue { color: #2563eb; background: #eaf2ff; }
.benefit-item-icon.purple { color: #7c3aed; background: #f2eaff; }
.benefit-item-icon.orange { color: #ea580c; background: #fff0e8; }
.benefit-item-icon.green { color: #059669; background: #e8faf3; }
.benefit-item strong { display: block; color: #1d2939; font-size: 12px; font-weight: 850; }
.benefit-item p { margin: 4px 0 0; color: #8a95a5; font-size: 10px; line-height: 1.55; }
.benefit-modal-footer { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 20px; padding-top: 17px; border-top: 1px solid #edf0f4; }
.benefit-modal-footer > span { color: #8a95a5; font-size: 10px; }
.benefit-modal-footer > span i { margin-right: 4px; color: #f59e0b; }
.benefit-modal-button { display: inline-flex; align-items: center; gap: 6px; padding: 9px 14px; border: 0; border-radius: 10px; color: #fff; background: #2563eb; font-size: 11px; font-weight: 800; cursor: pointer; }
.benefit-modal-button:hover { background: #1d4ed8; }
.benefit-modal-enter-active, .benefit-modal-leave-active { transition: opacity .2s ease; }
.benefit-modal-enter-active .benefit-modal, .benefit-modal-leave-active .benefit-modal { transition: transform .2s ease, opacity .2s ease; }
.benefit-modal-enter-from, .benefit-modal-leave-to { opacity: 0; }
.benefit-modal-enter-from .benefit-modal, .benefit-modal-leave-to .benefit-modal { opacity: 0; transform: translateY(12px) scale(.98); }

.register-form { display: grid; gap: 15px; }
.register-field { min-width: 0; }
.register-label { display: block; margin: 0 0 7px; color: #344054; font-size: 12px; font-weight: 800; }
.register-input-wrap { display: flex; align-items: center; height: 47px; padding: 0 13px; border: 1px solid #dfe4ec; border-radius: 12px; background: #fff; transition: border-color .18s ease, box-shadow .18s ease, background .18s ease; }
.register-input-wrap > i { flex: 0 0 auto; margin-right: 9px; color: #98a2b3; font-size: 14px; }
.register-input-wrap:focus-within { border-color: #86aaf5; background: #fff; box-shadow: 0 0 0 4px rgba(37, 99, 235, .08); }
.register-input-wrap:focus-within > i { color: #2563eb; }
.register-input { width: 100%; min-width: 0; height: 100%; padding: 0; border: 0; outline: 0; color: #172033; background: transparent; font-size: 13px; }
.register-input::placeholder { color: #a0a8b5; }
.register-password-hint { display: flex; align-items: center; gap: 7px; margin-top: -3px; color: #8a95a5; font-size: 10px; line-height: 1.45; }
.register-password-hint i { color: #2563eb; font-size: 12px; }
.register-submit { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; min-height: 47px; margin-top: 2px; padding: 0 16px; border: 0; border-radius: 12px; color: #fff; background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%); box-shadow: 0 9px 22px rgba(37, 99, 235, .2); font-size: 13px; font-weight: 800; transition: transform .18s ease, box-shadow .18s ease, opacity .18s ease; }
.register-submit:not(:disabled):hover { transform: translateY(-1px); box-shadow: 0 12px 26px rgba(37, 99, 235, .27); }
.register-submit:disabled { cursor: not-allowed; opacity: .68; }
.register-terms { margin: 13px 8px 0; color: #98a2b3; font-size: 10px; line-height: 1.55; text-align: center; }
.register-bottom { display: flex; align-items: center; justify-content: center; gap: 5px; margin-top: 18px; padding-top: 17px; border-top: 1px solid #edf0f4; color: #7b8799; font-size: 12px; }
.register-bottom a { display: inline-flex; align-items: center; gap: 4px; color: #2563eb; font-weight: 800; }
.register-bottom a:hover { color: #1d4ed8; }

@media (max-width: 650px) {
    .benefit-list { grid-template-columns: 1fr; }
    .benefit-modal { padding: 22px; border-radius: 20px; }
    .benefit-modal-footer { align-items: flex-start; flex-direction: column; }
    .benefit-modal-button { width: 100%; justify-content: center; }
}

@media (max-width: 575.98px) {
    .register-benefit { padding: 11px; }
    .register-benefit-text { font-size: 10px; }
}
</style>
