import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import '../css/app.css';
import '../css/theme.css';
import '../css/fixes.css';
import '../css/birth-date-modal.css';
import '../css/checkout-vat.css';
import { createInertiaApp, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import ClientLayout from '@/layouts/ClientLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initFlexibleBirthDatePicker } from '@/lib/flexible-birth-date';
import { initAvatarFallback } from '@/lib/avatar-fallback';

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

function initBirthDatePickerSoon(): void {
    if (typeof window === 'undefined') return;
    initFlexibleBirthDatePicker();
    window.setTimeout(() => initFlexibleBirthDatePicker(), 50);
    window.setTimeout(() => initFlexibleBirthDatePicker(), 250);
    window.setTimeout(() => initFlexibleBirthDatePicker(), 750);
    window.setTimeout(() => initFlexibleBirthDatePicker(), 1500);
}

router.on('start', () => setPageLoading(true));
router.on('finish', () => {
    setPageLoading(false);
    initBirthDatePickerSoon();
});
router.on('error', () => setPageLoading(false));
router.on('invalid', () => setPageLoading(false));

if (typeof window !== 'undefined') {
    void import('bootstrap');
    initAvatarFallback();
    window.addEventListener('load', initBirthDatePickerSoon, { once: false });
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
}).then(() => {
    revealApp();
    initBirthDatePickerSoon();
});
