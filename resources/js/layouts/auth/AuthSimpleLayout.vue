<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';

defineProps<{ title?: string; description?: string }>();
</script>

<template>
    <main class="auth-page">
        <div class="auth-background auth-background-one" aria-hidden="true" />
        <div class="auth-background auth-background-two" aria-hidden="true" />

        <div class="container position-relative py-4 py-lg-5">
            <div class="auth-shell">
                <section class="auth-showcase d-none d-lg-flex">
                    <Link :href="home()" class="auth-brand">
                        <span class="auth-brand-mark"><i class="bi bi-cpu-fill" /></span>
                        <span>
                            <strong>TechStore</strong>
                            <small>CÔNG NGHỆ &amp; LINH KIỆN</small>
                        </span>
                    </Link>

                    <div class="auth-showcase-content">
                        <span class="auth-eyebrow"><i class="bi bi-stars" /> TRẢI NGHIỆM MUA SẮM HIỆN ĐẠI</span>
                        <h2>Công nghệ tốt.<br /><span>Trải nghiệm tốt hơn.</span></h2>
                        <p>Mua laptop và linh kiện chính hãng với trải nghiệm đơn giản, nhanh chóng và an tâm.</p>

                        <div class="auth-benefits">
                            <div><span><i class="bi bi-shield-check" /></span><div><strong>An tâm mua sắm</strong><small>Bảo mật thông tin tài khoản</small></div></div>
                            <div><span><i class="bi bi-patch-check" /></span><div><strong>Sản phẩm chính hãng</strong><small>Thông tin rõ ràng, minh bạch</small></div></div>
                            <div><span><i class="bi bi-truck" /></span><div><strong>Giao hàng toàn quốc</strong><small>Theo dõi đơn hàng dễ dàng</small></div></div>
                        </div>
                    </div>

                    <div class="auth-showcase-footer">© {{ new Date().getFullYear() }} TechStore</div>
                </section>

                <section class="auth-form-panel">
                    <div class="d-lg-none text-center auth-mobile-brand">
                        <Link :href="home()" class="auth-brand auth-brand-mobile">
                            <span class="auth-brand-mark"><i class="bi bi-cpu-fill" /></span>
                            <span><strong>TechStore</strong><small>CÔNG NGHỆ &amp; LINH KIỆN</small></span>
                        </Link>
                    </div>

                    <div class="auth-heading">
                        <span class="auth-heading-kicker">TECHSTORE ACCOUNT</span>
                        <h1>{{ title }}</h1>
                        <p>{{ description }}</p>
                    </div>

                    <div class="auth-content">
                        <slot />
                    </div>

                    <div class="auth-footer-note">Bảo mật tài khoản của bạn là ưu tiên của TechStore.</div>
                </section>
            </div>
        </div>
    </main>
</template>

<style scoped>
.auth-page{position:relative;min-height:100vh;display:flex;align-items:center;overflow:hidden;background:#f5f7fb;color:#101828}
.auth-background{position:absolute;border-radius:50%;filter:blur(2px);pointer-events:none}.auth-background-one{width:480px;height:480px;top:-230px;left:-160px;background:rgba(37,99,235,.10)}.auth-background-two{width:420px;height:420px;right:-160px;bottom:-190px;background:rgba(124,58,237,.08)}
.auth-shell{position:relative;display:grid;grid-template-columns:43% 57%;width:min(100%,1040px);min-height:640px;margin:0 auto;overflow:hidden;border:1px solid #e5e9f0;border-radius:30px;background:#fff;box-shadow:0 30px 80px rgba(16,24,40,.12)}
.auth-showcase{position:relative;flex-direction:column;padding:42px;color:#fff;background:linear-gradient(150deg,#0b1830 0%,#102b68 58%,#34205e 100%);overflow:hidden}.auth-showcase:after{content:"";position:absolute;width:390px;height:390px;right:-210px;top:-130px;border:1px solid rgba(255,255,255,.11);border-radius:50%;box-shadow:0 0 0 60px rgba(255,255,255,.025),0 0 0 120px rgba(255,255,255,.018)}
.auth-brand{position:relative;z-index:1;display:inline-flex;align-items:center;gap:11px;width:max-content;color:inherit;text-decoration:none}.auth-brand-mark{display:grid;width:44px;height:44px;place-items:center;border-radius:13px;color:#fff;background:linear-gradient(135deg,#2563eb,#7c3aed);box-shadow:0 9px 25px rgba(37,99,235,.28)}.auth-brand strong{display:block;font-size:1.18rem;line-height:1;font-weight:900;letter-spacing:-.04em}.auth-brand small{display:block;margin-top:5px;color:#aebbd0;font-size:.45rem;font-weight:850;letter-spacing:.14em}
.auth-showcase-content{position:relative;z-index:1;margin:auto 0}.auth-eyebrow{display:inline-flex;align-items:center;gap:7px;color:#93c5fd;font-size:.64rem;font-weight:850;letter-spacing:.12em}.auth-showcase h2{margin:18px 0 14px;font-size:clamp(2.2rem,4vw,3.25rem);line-height:1.06;font-weight:900;letter-spacing:-.055em}.auth-showcase h2 span{color:#93c5fd}.auth-showcase-content>p{max-width:380px;margin:0;color:#c7d2e4;font-size:.86rem;line-height:1.7}.auth-benefits{display:grid;gap:13px;margin-top:30px}.auth-benefits>div{display:flex;align-items:center;gap:12px}.auth-benefits>div>span{display:grid;width:36px;height:36px;flex:0 0 36px;place-items:center;border:1px solid rgba(255,255,255,.13);border-radius:11px;color:#93c5fd;background:rgba(255,255,255,.07)}.auth-benefits strong,.auth-benefits small{display:block}.auth-benefits strong{font-size:.76rem}.auth-benefits small{margin-top:3px;color:#9eabc0;font-size:.65rem}.auth-showcase-footer{position:relative;z-index:1;color:#7788a4;font-size:.62rem}
.auth-form-panel{display:flex;flex-direction:column;padding:48px 54px 30px;background:#fff}.auth-mobile-brand{margin-bottom:28px}.auth-brand-mobile{color:#101828}.auth-heading-kicker{display:block;margin-bottom:9px;color:#2563eb;font-size:.62rem;font-weight:900;letter-spacing:.13em}.auth-heading h1{margin:0;color:#101828;font-size:1.85rem;font-weight:900;letter-spacing:-.045em}.auth-heading p{margin:8px 0 0;color:#667085;font-size:.78rem;line-height:1.6}.auth-content{margin-top:28px}.auth-footer-note{margin-top:auto;padding-top:25px;color:#98a2b3;text-align:center;font-size:.62rem}
:deep(.auth-content .alert){border-radius:13px!important;font-size:.75rem;padding:.8rem .95rem}.auth-content :deep(.auth-google-btn){height:48px!important;display:flex;align-items:center;justify-content:center;gap:10px;border:1px solid #d9dee8!important;border-radius:12px!important;color:#344054!important;background:#fff!important;font-size:.8rem!important;font-weight:800!important;box-shadow:0 2px 7px rgba(16,24,40,.035)!important;transition:.2s ease}.auth-content :deep(.auth-google-btn:hover){border-color:#b8c8e7!important;background:#f8fbff!important;transform:translateY(-1px);box-shadow:0 8px 18px rgba(16,24,40,.07)!important}.auth-content :deep(.auth-google-btn svg){width:19px;height:19px;flex:0 0 19px}.auth-content :deep(.auth-divider){display:flex;align-items:center;gap:11px;margin:19px 0;color:#98a2b3;font-size:.65rem;font-weight:650}.auth-content :deep(.auth-divider:before),.auth-content :deep(.auth-divider:after){content:"";height:1px;flex:1;background:#eaecf0}.auth-content :deep(.form-label){margin-bottom:7px;color:#344054;font-size:.72rem;font-weight:800}.auth-content :deep(.form-control){height:46px;border:1px solid #dfe3ea;border-radius:11px;padding:.65rem .85rem;color:#101828;background:#fff;font-size:.78rem;box-shadow:none}.auth-content :deep(.form-control:focus){border-color:#8fb0f4;box-shadow:0 0 0 4px rgba(37,99,235,.08)}.auth-content :deep(.form-check-label){color:#667085;font-size:.7rem}.auth-content :deep(.form-check-input){border-color:#cbd2dc}.auth-content :deep(.btn-primary){height:47px;border:0!important;border-radius:11px!important;font-size:.78rem!important;font-weight:850!important;box-shadow:0 8px 18px rgba(37,99,235,.2)}.auth-content :deep(.auth-bottom){margin-top:21px;color:#98a2b3;font-size:.7rem}.auth-content :deep(.auth-bottom a){color:#2563eb;font-weight:800}.auth-content :deep(.text-primary){color:#2563eb!important}
@media(max-width:991.98px){.auth-page{align-items:flex-start}.auth-shell{display:block;width:min(100%,560px);min-height:auto;margin:20px auto;border-radius:24px}.auth-form-panel{padding:34px 28px 26px}.auth-footer-note{margin-top:28px}.auth-heading h1{font-size:1.7rem}}
@media(max-width:575.98px){.auth-shell{margin:8px auto;border-radius:20px}.auth-form-panel{padding:28px 20px 22px}.auth-content{margin-top:23px}}
</style>
