<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Settings, UserRound, ShieldCheck, UsersRound, Palette, ChevronRight } from '@lucide/vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import { index as teams } from '@/routes/teams';

const settingsNavItems = [
    { title: 'Thông tin cá nhân', description: 'Hồ sơ', href: editProfile(), icon: UserRound },
    { title: 'Bảo mật', description: 'Mật khẩu & bảo vệ', href: editSecurity(), icon: ShieldCheck },
    { title: 'Nhóm', description: 'Quản lý nhóm', href: teams(), icon: UsersRound },
    { title: 'Giao diện', description: 'Tùy chỉnh hiển thị', href: editAppearance(), icon: Palette },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="settings-page-shell">
        <header class="settings-hero">
            <div class="settings-hero-icon"><Settings :size="22" /></div>
            <div>
                <span class="settings-eyebrow">QUẢN LÝ TÀI KHOẢN</span>
                <h1>Cài đặt</h1>
                <p>Quản lý hồ sơ, bảo mật và các tùy chọn tài khoản của bạn.</p>
            </div>
        </header>

        <nav class="settings-nav settings-nav-modern" aria-label="Cài đặt tài khoản">
            <div class="settings-nav-links">
                <Link
                    v-for="item in settingsNavItems"
                    :key="toUrl(item.href)"
                    :href="item.href"
                    class="settings-nav-link-modern"
                    :class="{ active: isCurrentOrParentUrl(item.href) }"
                >
                    <span class="settings-nav-link-icon"><component :is="item.icon" :size="18" /></span>
                    <span class="settings-nav-link-copy">
                        <strong>{{ item.title }}</strong>
                        <small>{{ item.description }}</small>
                    </span>
                    <ChevronRight class="settings-nav-chevron" :size="16" />
                </Link>
            </div>
        </nav>

        <main class="settings-page-content">
            <slot />
        </main>
    </div>
</template>

<style>
.settings-page-shell {
    width: min(100% - 32px, 1220px);
    margin: 0 auto;
    padding: 30px 0 56px;
}
.settings-hero {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 18px;
    padding: 4px 2px;
}
.settings-hero-icon {
    display: grid;
    width: 52px;
    height: 52px;
    flex: 0 0 52px;
    place-items: center;
    border: 1px solid #dbeafe;
    border-radius: 16px;
    color: #2563eb;
    background: linear-gradient(145deg, #eff6ff, #f5f3ff);
    box-shadow: 0 10px 24px rgba(37, 99, 235, .10);
}
.settings-eyebrow {
    display: block;
    margin-bottom: 4px;
    color: #2563eb;
    font-size: 11px;
    font-weight: 900;
    letter-spacing: .14em;
}
.settings-hero h1 {
    margin: 0;
    color: #101828;
    font-size: clamp(1.65rem, 3vw, 2.15rem);
    font-weight: 850;
    letter-spacing: -.04em;
}
.settings-hero p {
    margin: 5px 0 0;
    color: #667085;
    font-size: .84rem;
}
.settings-nav-modern {
    margin: 0 0 28px;
    padding: 7px;
    border: 1px solid #e4e7ec;
    border-radius: 18px;
    background: rgba(255,255,255,.94);
    box-shadow: 0 12px 32px rgba(16,24,40,.055);
}
.settings-nav-modern .settings-nav-links {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 6px;
}
.settings-nav-link-modern {
    position: relative;
    display: flex;
    align-items: center;
    min-width: 0;
    gap: 10px;
    padding: 11px 12px;
    border: 1px solid transparent;
    border-radius: 13px;
    color: #475467;
    background: transparent;
    transition: transform .18s ease, border-color .18s ease, background .18s ease, box-shadow .18s ease;
}
.settings-nav-link-modern:hover {
    color: #1d4ed8;
    border-color: #dbeafe;
    background: #f8fbff;
    transform: translateY(-1px);
}
.settings-nav-link-modern.active {
    color: #1d4ed8;
    border-color: #bfdbfe;
    background: linear-gradient(135deg, #eff6ff, #f5f3ff);
    box-shadow: 0 7px 18px rgba(37,99,235,.08);
}
.settings-nav-link-icon {
    display: grid;
    width: 38px;
    height: 38px;
    flex: 0 0 38px;
    place-items: center;
    border-radius: 11px;
    color: #667085;
    background: #f8fafc;
    transition: .18s ease;
}
.settings-nav-link-modern.active .settings-nav-link-icon,
.settings-nav-link-modern:hover .settings-nav-link-icon {
    color: #2563eb;
    background: #fff;
}
.settings-nav-link-copy {
    display: block;
    min-width: 0;
    flex: 1;
}
.settings-nav-link-copy strong,
.settings-nav-link-copy small {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.settings-nav-link-copy strong {
    font-size: .78rem;
    font-weight: 800;
}
.settings-nav-link-copy small {
    margin-top: 2px;
    color: #98a2b3;
    font-size: .64rem;
    font-weight: 600;
}
.settings-nav-chevron {
    flex: 0 0 auto;
    color: #c0c7d1;
}
.settings-nav-link-modern.active .settings-nav-chevron {
    color: #60a5fa;
}
.settings-page-content {
    min-width: 0;
}
.profile-page {
    max-width: none !important;
}
.profile-header {
    margin-bottom: 22px !important;
    align-items: center !important;
}
.profile-header h1 {
    font-size: clamp(1.65rem, 3vw, 2rem) !important;
}
.profile-card {
    border-color: #e4e7ec !important;
    box-shadow: 0 12px 34px rgba(16,24,40,.055) !important;
}
.profile-summary {
    padding: 30px 24px !important;
}
.profile-summary-list > div {
    border: 1px solid #edf0f4;
    background: #fafbfc !important;
}
@media (max-width: 900px) {
    .settings-nav-modern .settings-nav-links { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 575px) {
    .settings-page-shell { width: min(100% - 20px, 1220px); padding-top: 18px; }
    .settings-hero { align-items: flex-start; }
    .settings-hero-icon { width: 46px; height: 46px; flex-basis: 46px; }
    .settings-nav-modern .settings-nav-links { grid-template-columns: 1fr; }
    .settings-nav-link-modern { padding: 10px; }
    .profile-header { align-items: flex-start !important; }
}
</style>
