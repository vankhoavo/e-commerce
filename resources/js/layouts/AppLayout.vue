<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import SettingsNav from '@/components/SettingsNav.vue';
import type { BreadcrumbItem } from '@/types';

const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div v-if="$page.url.startsWith('/settings')" class="settings-page-shell">
            <SettingsNav />
            <main class="settings-page-content"><slot /></main>
        </div>
        <slot v-else />
    </AppLayout>
</template>
