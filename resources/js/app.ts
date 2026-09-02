import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import '../css/app.css';
import '../css/theme.css';
import '../css/fixes.css';
import { createInertiaApp } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import ClientLayout from '@/layouts/ClientLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

const appName = import.meta.env.VITE_APP_NAME || 'TechStore';

// Bootstrap's JavaScript touches `document` during module evaluation.
// Load it only in the browser so Inertia SSR can evaluate the app safely.
if (typeof window !== 'undefined') {
    void import('bootstrap');
}

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        if (name.startsWith('admin/')) return AdminLayout;
        if (name.startsWith('auth/')) return AuthLayout;
        if (name.startsWith('settings/') || name.startsWith('teams/')) return [ClientLayout, SettingsLayout];
        return ClientLayout;
    },
    progress: { color: '#2563eb' },
});
