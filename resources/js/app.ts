import { createInertiaApp } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import ClientLayout from '@/layouts/ClientLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

const appName = import.meta.env.VITE_APP_NAME || 'TechStore';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        if (name.startsWith('admin/')) {
            return AdminLayout;
        }

        if (name.startsWith('auth/')) {
            return AuthLayout;
        }

        if (name.startsWith('settings/') || name.startsWith('teams/')) {
            return [ClientLayout, SettingsLayout];
        }

        return ClientLayout;
    },
    progress: {
        color: '#2563eb',
    },
});
