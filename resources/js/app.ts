import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import '../css/app.css';
import '../css/theme.css';
import '../css/fixes.css';
import { createInertiaApp, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import ClientLayout from '@/layouts/ClientLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

const appName = import.meta.env.VITE_APP_NAME || 'TechStore';

function setPageLoading(loading: boolean) {
    if (typeof document === 'undefined') return;
    document.body.classList.toggle('techstore-page-loading', loading);
    document.documentElement.classList.toggle('techstore-page-loading', loading);
}

function revealApp() {
    if (typeof document === 'undefined') return;
    document.body.classList.remove('techstore-booting');
    document.documentElement.classList.remove('techstore-booting');
}

router.on('start', () => setPageLoading(true));
router.on('finish', () => setPageLoading(false));
router.on('error', () => setPageLoading(false));
router.on('invalid', () => setPageLoading(false));

if (typeof window !== 'undefined') {
    void import('bootstrap');
}

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        if (name.startsWith('admin/')) return AdminLayout;
        if (name.startsWith('auth/')) return AuthLayout;
        if (name.startsWith('settings/')) return [ClientLayout, SettingsLayout];
        return ClientLayout;
    },
    progress: { color: '#2563eb' },
}).then(revealApp);
