<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const currentUrl = computed(() => page.url.split('?')[0]);

const items = [
    { href: '/settings/profile', label: 'Thông tin cá nhân', icon: 'bi-person-vcard' },
    { href: '/settings/security', label: 'Bảo mật', icon: 'bi-shield-lock' },
    { href: '/settings/appearance', label: 'Giao diện', icon: 'bi-palette' },
    { href: '/settings/teams', label: 'Nhóm', icon: 'bi-people' },
];
</script>

<template>
    <nav class="settings-nav" aria-label="Cài đặt tài khoản">
        <div class="settings-nav-heading">
            <div class="settings-nav-icon"><i class="bi bi-sliders2" /></div>
            <div><strong>Cài đặt</strong><small>Quản lý tài khoản</small></div>
        </div>
        <div class="settings-nav-links">
            <Link v-for="item in items" :key="item.href" :href="item.href" class="settings-nav-link" :class="{ active: currentUrl === item.href || (item.href === '/settings/teams' && currentUrl.startsWith('/settings/teams/')) }">
                <i :class="['bi', item.icon]" />
                <span>{{ item.label }}</span>
                <i class="bi bi-chevron-right ms-auto settings-nav-arrow" />
            </Link>
        </div>
    </nav>
</template>
