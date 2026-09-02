<script setup lang="ts">
import { computed, ref } from 'vue';
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
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const showPassword = ref(false);
const showConfirmation = ref(false);
const emailChecking = ref(false);
const googleLinked = ref(false);
const emailChecked = ref(false);

const passwordChecks = computed(() => ({
    length: password.value.length >= 8,
    uppercase: /[A-ZÀ-Ỵ]/.test(password.value),
    lowercase: /[a-zà-ỹ]/.test(password.value),
    number: /\d/.test(password.value),
}));
const passwordScore = computed(() => Object.values(passwordChecks.value).filter(Boolean).length);
const passwordReady = computed(() => passwordScore.value === 4);
const passwordsMatch = computed(() => passwordConfirmation.value.length > 0 && password.value === passwordConfirmation.value);
const passwordsMismatch = computed(() => passwordConfirmation.value.length > 0 && password.value !== passwordConfirmation.value);
const emailValid = computed(() => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim()));

const passwordStrengthLabel = computed(() => {
    if (!password.value) return 'Nhập mật khẩu để kiểm tra độ mạnh';
    if (passwordScore.value <= 1) return 'Mật khẩu còn yếu';
    if (passwordScore.value <= 3) return 'Mật khẩu khá tốt';
    return 'Mật khẩu đạt yêu cầu';
});

const closeBenefits = () => { showBenefits.value = false; };

async function checkGoogleEmail() {
    googleLinked.value = false;
    emailChecked.value = false;
    const value = email.value.trim().toLowerCase();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) return;

    emailChecking.value = true;
    try {
        const response = await fetch(`/auth/google/check-email?email=${encodeURIComponent(value)}`, {
            headers: { Accept: 'application/json' },
        });
        if (response.ok) {
            const data = await response.json();
            googleLinked.value = Boolean(data.google_linked);
            emailChecked.value = true;
        }
    } catch {
        emailChecked.value = false;
    } finally {
        emailChecking.value = false;
    }
}
</script>

<template>
    <Head title="Đăng ký" />
    <TeamInvitationAlert v-if="teamInvitation" :invitation="teamInvitation" action="Đăng ký" />

    <button type="button" class="register-benefit" aria-haspopup="dialog" :aria-expanded="showBenefits" @click="showBenefits = true">
        <span class="register-benefit-icon"><i class="bi bi-gift-fill" aria-hidden="true" /></span>
        <span class="register-benefit-content">
            <span class="register-benefit-title">Ưu đãi thành viên mới</span>
            <span class="register-benefit-text">Đăng ký hôm nay để nhận thông tin khuyến mãi sớm nhất.</span>
        </span>
        <span class="register-benefit-arrow"><i class="bi bi-chevron-right" /></span>
    </button>

    <Teleport to="body">
        <Transition name="benefit-modal">
            <div v-if="showBenefits" class="benefit-modal-backdrop" @click.self="closeBenefits">
                <section class="benefit-modal" role="dialog" aria-modal="true" aria-labelledby="benefit-modal-title">
                    <button type="button" class="benefit-modal-close" aria-label="Đóng ưu đãi" @click="closeBenefits"><i class="bi bi-x-lg" /></button>
                    <div class="benefit-modal-hero">
                        <div class="benefit-modal-gift"><i class="bi bi-gift-fill" /></div>
                        <div><span class="benefit-modal-eyebrow">TECHSTORE MEMBER</span><h2 id="benefit-modal-title">Đặc quyền thành viên</h2><p>Đăng ký tài khoản để mở khóa trải nghiệm mua sắm đầy đủ hơn tại TechStore.</p></div>
                    </div>
                    <div class="benefit-list">
                        <article class="benefit-item"><span class="benefit-item-icon blue"><i class="bi bi-ticket-perforated-fill" /></span><div><strong>Ưu đãi & mã giảm giá</strong><p>Nhận thông tin các chương trình ưu đãi, mã giảm giá và khuyến mãi dành cho thành viên.</p></div></article>
                        <article class="benefit-item"><span class="benefit-item-icon purple"><i class="bi bi-bell-fill" /></span><div><strong>Cập nhật sản phẩm mới</strong><p>Dễ dàng theo dõi sản phẩm mới, chương trình nổi bật và các thông tin từ TechStore.</p></div></article>
                        <article class="benefit-item"><span class="benefit-item-icon orange"><i class="bi bi-receipt-cutoff" /></span><div><strong>Quản lý đơn hàng</strong><p>Xem lịch sử mua hàng, trạng thái giao hàng và thông tin hóa đơn ngay trong tài khoản.</p></div></article>
                        <article class="benefit-item"><span class="benefit-item-icon green"><i class="bi bi-shield-check" /></span><div><strong>Tài khoản an toàn</strong><p>Bảo vệ tài khoản với xác thực Email, đổi mật khẩu và các tùy chọn bảo mật.</p></div></article>
                    </div>
                    <div class="benefit-modal-footer"><span><i class="bi bi-stars" /> Nhiều tiện ích hơn khi trở thành thành viên</span><button type="button" class="benefit-modal-button" @click="closeBenefits">Đã hiểu <i class="bi bi-arrow-right" /></button></div>
                </section>
            </div>
        </Transition>
    </Teleport>

    <Form v-bind="store.form()" :reset-on-success="['password', 'password_confirmation']" v-slot="{ errors, processing }" class="register-form">
        <div class="register-field">
            <label for="name" class="register-label">Họ và tên</label>
            <div class="register-input-wrap"><i class="bi bi-person" /><input id="name" type="text" name="name" class="register-input" required autofocus autocomplete="name" placeholder="Nguyễn Văn A" /></div>
            <InputError :message="errors.name" />
        </div>

        <div class="register-field">
            <label for="email" class="register-label">Email</label>
            <div :class="['register-input-wrap', { 'is-valid': emailChecked && !googleLinked, 'is-info': googleLinked }]">
                <i class="bi bi-envelope" />
                <input id="email" v-model="email" type="email" name="email" class="register-input" required autocomplete="email" placeholder="email@example.com" @blur="checkGoogleEmail" />
                <span v-if="emailChecking" class="spinner-border spinner-border-sm text-primary" aria-label="Đang kiểm tra" />
                <i v-else-if="emailChecked && googleLinked" class="bi bi-google field-status google" />
                <i v-else-if="emailChecked" class="bi bi-check-circle-fill field-status success" />
            </div>
            <div v-if="googleLinked" class="google-account-notice">
                <div class="google-notice-icon"><i class="bi bi-google" /></div>
                <div><strong>Email này đã liên kết với Google</strong><p>Bạn có thể <a href="/auth/google">đăng nhập bằng Google</a>. Nếu muốn dùng Email + Mật khẩu, hãy tiếp tục hoàn tất biểu mẫu này. Mật khẩu mới sẽ được dùng để đăng nhập sau khi đăng ký.</p></div>
            </div>
            <InputError :message="errors.email" />
        </div>

        <div class="register-field">
            <label for="password" class="register-label">Mật khẩu</label>
            <div :class="['register-input-wrap', { 'is-valid': passwordReady, 'is-invalid': password.value && !passwordReady }]">
                <i class="bi bi-lock" />
                <input id="password" v-model="password" :type="showPassword ? 'text' : 'password'" name="password" class="register-input" required autocomplete="new-password" placeholder="Nhập mật khẩu" :passwordrules="passwordRules" />
                <button type="button" class="password-toggle" :aria-label="showPassword ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'" @click="showPassword = !showPassword"><i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'" /></button>
            </div>
            <div v-if="password" class="password-strength">
                <div class="strength-head"><span>{{ passwordStrengthLabel }}</span><b>{{ passwordScore }}/4</b></div>
                <div class="strength-bars"><span v-for="bar in 4" :key="bar" :class="{ filled: bar <= passwordScore }" /></div>
                <div class="password-rules">
                    <span :class="{ passed: passwordChecks.length }"><i :class="passwordChecks.length ? 'bi bi-check-circle-fill' : 'bi bi-circle'" /> Ít nhất 8 ký tự</span>
                    <span :class="{ passed: passwordChecks.uppercase }"><i :class="passwordChecks.uppercase ? 'bi bi-check-circle-fill' : 'bi bi-circle'" /> Có chữ hoa</span>
                    <span :class="{ passed: passwordChecks.lowercase }"><i :class="passwordChecks.lowercase ? 'bi bi-check-circle-fill' : 'bi bi-circle'" /> Có chữ thường</span>
                    <span :class="{ passed: passwordChecks.number }"><i :class="passwordChecks.number ? 'bi bi-check-circle-fill' : 'bi bi-circle'" /> Có số</span>
                </div>
            </div>
            <InputError :message="errors.password" />
        </div>

        <div class="register-field">
            <label for="password_confirmation" class="register-label">Xác nhận mật khẩu</label>
            <div :class="['register-input-wrap', { 'is-valid': passwordsMatch, 'is-invalid': passwordsMismatch }]">
                <i class="bi bi-shield-check" />
                <input id="password_confirmation" v-model="passwordConfirmation" :type="showConfirmation ? 'text' : 'password'" name="password_confirmation" class="register-input" required autocomplete="new-password" placeholder="Nhập lại mật khẩu" :passwordrules="passwordRules" />
                <button type="button" class="password-toggle" :aria-label="showConfirmation ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'" @click="showConfirmation = !showConfirmation"><i :class="showConfirmation ? 'bi bi-eye-slash' : 'bi bi-eye'" /></button>
                <i v-if="passwordsMatch" class="bi bi-check-circle-fill field-status success" />
                <i v-else-if="passwordsMismatch" class="bi bi-x-circle-fill field-status danger" />
            </div>
            <div v-if="passwordsMatch" class="match-message success-text"><i class="bi bi-check-circle-fill" /> Mật khẩu trùng khớp</div>
            <div v-else-if="passwordsMismatch" class="match-message danger-text"><i class="bi bi-exclamation-circle-fill" /> Mật khẩu nhập lại không trùng khớp</div>
            <InputError :message="errors.password_confirmation" />
        </div>

        <div class="register-password-hint"><i class="bi bi-shield-check" /><span>Hãy tạo mật khẩu đủ mạnh và đảm bảo hai mật khẩu trùng khớp.</span></div>
        <button type="submit" class="register-submit" :disabled="processing || !passwordReady || !passwordsMatch || !emailValid">
            <span v-if="processing" class="spinner-border spinner-border-sm" /><i v-else class="bi bi-person-plus-fill" />
            {{ processing ? 'Đang tạo tài khoản...' : 'Tạo tài khoản' }} <i v-if="!processing" class="bi bi-arrow-right" />
        </button>
    </Form>

    <div class="register-terms">Bằng việc đăng ký, bạn đồng ý với các điều khoản sử dụng của TechStore.</div>
    <div class="register-bottom"><span>Đã có tài khoản?</span><Link :href="teamInvitation ? login.url({ query: { invitation: teamInvitation.code } }) : login()">Đăng nhập ngay <i class="bi bi-arrow-right" /></Link></div>
</template>

<style scoped>
.register-benefit{width:100%;display:flex;align-items:center;gap:12px;min-height:68px;margin:0 0 22px;padding:12px 14px;border:1px solid #dbe7ff;border-radius:15px;background:linear-gradient(135deg,#f7faff 0%,#eff6ff 100%);text-align:left;cursor:pointer;transition:.18s ease}.register-benefit:hover{border-color:#bfd3ff;box-shadow:0 8px 22px rgba(37,99,235,.09);transform:translateY(-1px)}.register-benefit-icon{display:grid;flex:0 0 40px;width:40px;height:40px;place-items:center;border-radius:11px;color:#2563eb;background:#fff;box-shadow:0 5px 14px rgba(37,99,235,.1);font-size:16px}.register-benefit-content{min-width:0;flex:1}.register-benefit-title{display:block;color:#172033;font-size:13px;font-weight:850;line-height:1.25}.register-benefit-text{display:block;margin-top:3px;color:#7b8799;font-size:11px;line-height:1.45}.register-benefit-arrow{color:#9ab3e8;font-size:12px}
.benefit-modal-backdrop{position:fixed;inset:0;z-index:2000;display:grid;place-items:center;padding:20px;background:rgba(15,23,42,.52);backdrop-filter:blur(7px)}.benefit-modal{position:relative;width:min(620px,100%);max-height:min(760px,calc(100vh - 40px));overflow:auto;padding:28px;border-radius:24px;background:#fff;box-shadow:0 28px 80px rgba(15,23,42,.24)}.benefit-modal-close{position:absolute;top:18px;right:18px;display:grid;width:34px;height:34px;place-items:center;border:1px solid #e8ecf2;border-radius:10px;color:#667085;background:#f8fafc;cursor:pointer}.benefit-modal-hero{display:flex;align-items:center;gap:16px;padding-right:38px}.benefit-modal-gift{display:grid;flex:0 0 56px;width:56px;height:56px;place-items:center;border-radius:16px;color:#2563eb;background:#eef5ff;font-size:23px}.benefit-modal-eyebrow{display:block;margin-bottom:5px;color:#2563eb;font-size:9px;font-weight:900;letter-spacing:.15em}.benefit-modal h2{margin:0;color:#101828;font-size:23px;font-weight:850}.benefit-modal-hero p{margin:5px 0 0;color:#7b8799;font-size:11px;line-height:1.55}.benefit-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:22px}.benefit-item{display:flex;gap:11px;padding:14px;border:1px solid #edf0f4;border-radius:15px;background:#fbfcfe}.benefit-item-icon{display:grid;flex:0 0 34px;width:34px;height:34px;place-items:center;border-radius:10px;font-size:13px}.benefit-item-icon.blue{color:#2563eb;background:#eaf2ff}.benefit-item-icon.purple{color:#7c3aed;background:#f2eaff}.benefit-item-icon.orange{color:#ea580c;background:#fff0e8}.benefit-item-icon.green{color:#059669;background:#e8faf3}.benefit-item strong{display:block;color:#1d2939;font-size:12px;font-weight:850}.benefit-item p{margin:4px 0 0;color:#8a95a5;font-size:10px;line-height:1.55}.benefit-modal-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:20px;padding-top:17px;border-top:1px solid #edf0f4}.benefit-modal-footer>span{color:#8a95a5;font-size:10px}.benefit-modal-button{display:inline-flex;align-items:center;gap:6px;padding:9px 14px;border:0;border-radius:10px;color:#fff;background:#2563eb;font-size:11px;font-weight:800;cursor:pointer}.benefit-modal-enter-active,.benefit-modal-leave-active{transition:opacity .2s ease}.benefit-modal-enter-from,.benefit-modal-leave-to{opacity:0}
.register-form{display:grid;gap:15px}.register-field{min-width:0}.register-label{display:block;margin:0 0 7px;color:#344054;font-size:12px;font-weight:800}.register-input-wrap{display:flex;align-items:center;height:47px;padding:0 13px;border:1px solid #dfe4ec;border-radius:12px;background:#fff;transition:border-color .18s ease,box-shadow .18s ease,background .18s ease}.register-input-wrap:focus-within{border-color:#8fb2f4;box-shadow:0 0 0 4px rgba(37,99,235,.08)}.register-input-wrap.is-valid{border-color:#63c995;background:#fbfffd}.register-input-wrap.is-info{border-color:#9dbaf2;background:#f8fbff}.register-input-wrap.is-invalid{border-color:#f08b8b;background:#fffafa}.register-input-wrap>i{flex:0 0 auto;margin-right:9px;color:#98a2b3;font-size:14px}.register-input{width:100%;min-width:0;border:0;outline:0;background:transparent;color:#344054;font-size:12px}.field-status{margin-left:8px!important;margin-right:0!important}.field-status.success{color:#16a34a}.field-status.google{color:#4285f4}.field-status.danger{color:#dc2626}.password-toggle{display:grid;flex:0 0 28px;width:28px;height:28px;place-items:center;border:0;background:transparent;color:#98a2b3;cursor:pointer}.password-toggle:hover{color:#2563eb}.google-account-notice{display:flex;gap:10px;margin-top:8px;padding:10px 11px;border:1px solid #d8e5fb;border-radius:11px;background:#f7faff;color:#51617a;animation:notice-in .22s ease}.google-notice-icon{display:grid;flex:0 0 30px;width:30px;height:30px;place-items:center;border-radius:9px;background:#fff;color:#4285f4;box-shadow:0 3px 10px rgba(66,133,244,.1)}.google-account-notice strong{display:block;color:#254a85;font-size:10px;font-weight:850}.google-account-notice p{margin:3px 0 0;font-size:10px;line-height:1.5}.google-account-notice a{color:#2563eb;font-weight:800;text-decoration:none}.password-strength{margin-top:8px;padding:10px 11px;border:1px solid #edf0f4;border-radius:11px;background:#fafbfd}.strength-head{display:flex;justify-content:space-between;color:#667085;font-size:10px;font-weight:750}.strength-head b{color:#2563eb}.strength-bars{display:grid;grid-template-columns:repeat(4,1fr);gap:4px;margin-top:7px}.strength-bars span{height:4px;border-radius:99px;background:#e7ebf1;transition:.2s ease}.strength-bars span.filled{background:#2563eb}.password-rules{display:grid;grid-template-columns:1fr 1fr;gap:5px 10px;margin-top:8px}.password-rules span{color:#98a2b3;font-size:9px}.password-rules span.passed{color:#15915c}.password-rules i{margin-right:4px}.match-message{display:flex;align-items:center;gap:5px;margin-top:6px;font-size:10px;font-weight:750}.success-text{color:#15915c}.danger-text{color:#dc2626}.register-password-hint{display:flex;gap:7px;align-items:center;color:#7b8799;font-size:10px;line-height:1.45}.register-password-hint i{color:#2563eb}.register-submit{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;height:47px;border:0;border-radius:12px;color:#fff;background:linear-gradient(135deg,#2563eb,#4f46e5);box-shadow:0 10px 22px rgba(37,99,235,.2);font-size:12px;font-weight:850;cursor:pointer;transition:.18s ease}.register-submit:hover:not(:disabled){transform:translateY(-1px);box-shadow:0 14px 28px rgba(37,99,235,.24)}.register-submit:disabled{opacity:.55;cursor:not-allowed;box-shadow:none}.register-terms{margin-top:12px;color:#98a2b3;text-align:center;font-size:9px;line-height:1.5}.register-bottom{display:flex;justify-content:center;gap:4px;margin-top:16px;padding-top:15px;border-top:1px solid #edf0f4;color:#8a95a5;font-size:10px}.register-bottom a{color:#2563eb;font-weight:850;text-decoration:none}@keyframes notice-in{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:none}}@media(max-width:560px){.benefit-list{grid-template-columns:1fr}.benefit-modal{padding:22px}.password-rules{grid-template-columns:1fr}.register-benefit{margin-bottom:17px}}
</style>
