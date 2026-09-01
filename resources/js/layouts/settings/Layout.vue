<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Settings, UserRound, ShieldCheck, UsersRound, Palette, ChevronRight } from 'lucide-vue-next';
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
