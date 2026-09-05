<script setup lang="ts">
import { Settings, UserRound, ShieldCheck, Palette, ReceiptText } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, provide, readonly, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Orders from '@/components/settings/OrdersDatabase.vue';

export type SettingsSection = 'profile' | 'security' | 'appearance' | 'orders';
const settingsNavItems: { key: SettingsSection; title: string; description: string; icon: typeof UserRound }[] = [
    { key: 'profile', title: 'Thông tin cá nhân', description: 'Hồ sơ', icon: UserRound },
    { key: 'security', title: 'Bảo mật', description: 'Mật khẩu & bảo vệ', icon: ShieldCheck },
    { key: 'appearance', title: 'Giao diện', description: 'Tùy chỉnh hiển thị', icon: Palette },
    { key: 'orders', title: 'Đơn hàng', description: 'Lịch sử & hóa đơn', icon: ReceiptText },
];

function isSettingsSection(value: string | null): value is SettingsSection {
    return value !== null && settingsNavItems.some((item) => item.key === value);
}

function getSectionFromUrl(): SettingsSection {
    if (typeof window === 'undefined') return 'profile';
    const value = new URLSearchParams(window.location.search).get('section');
    return isSettingsSection(value) ? value : 'profile';
}

// Giữ trạng thái ban đầu giống nhau giữa SSR và client để tránh hydration mismatch.
// Section trong URL được khôi phục ngay sau khi Vue mount.
const activeSection = ref<SettingsSection>('profile');
provide('techstore-settings-section', readonly(activeSection));

const page = usePage();
const ordersKey = computed(() => {
    const orders = ((page.props as any).orders ?? []) as Array<{ id?: number; status?: string; vatInvoice?: { requested?: boolean } }>;
    return orders.map((order) => `${order.id ?? ''}:${order.status ?? ''}:${order.vatInvoice?.requested ? 1 : 0}`).join('|');
});

function updateUrl(section: SettingsSection, replace = false): void {
    if (typeof window === 'undefined') return;
    const url = new URL(window.location.href);
    url.pathname = '/settings/profile';
    if (section === 'profile') url.searchParams.delete('section');
    else url.searchParams.set('section', section);

    const state = { ...(window.history.state ?? {}), settingsSection: section };
    const target = `${url.pathname}${url.search}${url.hash}`;
    (replace ? window.history.replaceState : window.history.pushState).call(window.history, state, '', target);
}

function selectSection(section: SettingsSection): void {
    if (activeSection.value === section) return;

    // Orders được tải lại từ server khi mở section để trạng thái đơn hàng và
    // điều kiện hiển thị hóa đơn luôn phản ánh dữ liệu mới nhất.
    if (section === 'orders') {
        activeSection.value = section;
        updateUrl(section, true);
        router.reload({ only: ['orders'], preserveScroll: true, preserveState: false });
        return;
    }

    activeSection.value = section;
    updateUrl(section);
}

function syncFromUrl(): void {
    activeSection.value = getSectionFromUrl();
}

onMounted(() => {
    syncFromUrl();
    updateUrl(activeSection.value, true);
    window.addEventListener('popstate', syncFromUrl);
});

onBeforeUnmount(() => {
    window.removeEventListener('popstate', syncFromUrl);
});
</script>

<template>
    <div class="settings-page-shell">
        <header class="settings-hero">
            <div class="settings-hero-icon"><Settings :size="22" /></div>
            <div><span class="settings-eyebrow">QUẢN LÝ TÀI KHOẢN</span><h1>Cài đặt</h1><p>Quản lý hồ sơ, bảo mật, giao diện và lịch sử mua hàng của bạn.</p></div>
        </header>

        <nav class="settings-nav settings-nav-modern" aria-label="Cài đặt tài khoản">
            <div class="settings-nav-links">
                <button v-for="item in settingsNavItems" :key="item.key" type="button" class="settings-nav-link-modern" :class="{ 'is-active': activeSection === item.key }" :aria-current="activeSection === item.key ? 'page' : undefined" @click="selectSection(item.key)">
                    <span class="settings-nav-link-icon"><component :is="item.icon" :size="18" /></span>
                    <span class="settings-nav-link-copy"><strong>{{ item.title }}</strong><small>{{ item.description }}</small></span>
                </button>
            </div>
        </nav>

        <main class="settings-page-content">
            <div v-show="activeSection !== 'orders'" class="settings-slot-view"><slot /></div>
            <div v-if="activeSection === 'orders'" class="settings-orders-view"><Orders :key="ordersKey" /></div>
        </main>
    </div>
</template>

<style>
.settings-page-shell{width:min(100% - 32px,1220px);margin:0 auto;padding:30px 0 56px}.settings-hero{display:flex;align-items:center;gap:16px;margin-bottom:18px;padding:4px 2px}.settings-hero-icon{display:grid;width:52px;height:52px;flex:0 0 52px;place-items:center;border:1px solid #dbeafe;border-radius:16px;color:#2563eb;background:linear-gradient(145deg,#eff6ff,#f5f3ff);box-shadow:0 10px 24px rgba(37,99,235,.10)}.settings-eyebrow{display:block;margin-bottom:4px;color:#2563eb;font-size:11px;font-weight:900;letter-spacing:.14em}.settings-hero h1{margin:0;color:#101828;font-size:clamp(1.65rem,3vw,2.15rem);font-weight:850;letter-spacing:-.04em}.settings-hero p{margin:5px 0 0;color:#667085;font-size:.84rem}.settings-nav-modern{margin:0 0 28px;padding:7px;border:1px solid #e4e7ec;border-radius:18px;background:rgba(255,255,255,.96);box-shadow:0 12px 32px rgba(16,24,40,.055)}.settings-nav-links{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:6px}.settings-nav-link-modern{position:relative;display:flex;align-items:center;min-width:0;width:100%;gap:10px;padding:11px 12px;border:1px solid transparent;border-radius:13px;color:#475467;background:transparent;text-align:left;cursor:pointer;transition:.18s ease}.settings-nav-link-modern:not(.is-active){color:#667085!important;background:#fff!important;background-image:none!important;border-color:transparent!important;box-shadow:none!important}.settings-nav-link-modern:not(.is-active):hover{color:#475467!important;border-color:#eaecf0!important;background:#fcfcfd!important;background-image:none!important;box-shadow:none!important;transform:none!important}.settings-nav-link-modern:not(.is-active):focus,.settings-nav-link-modern:not(.is-active):focus-visible,.settings-nav-link-modern:not(.is-active):active{color:#667085!important;background:#fff!important;background-image:none!important;border-color:transparent!important;box-shadow:none!important;outline:none!important;transform:none!important}.settings-nav-link-modern.is-active{color:#1d4ed8!important;border-color:#bfdbfe!important;background:linear-gradient(135deg,#eff6ff,#f5f3ff)!important;background-image:linear-gradient(135deg,#eff6ff,#f5f3ff)!important;box-shadow:0 7px 18px rgba(37,99,235,.08)!important}.settings-nav-link-modern.is-active:focus-visible{outline:2px solid #93bdf0;outline-offset:2px}.settings-nav-link-icon{display:grid;width:38px;height:38px;flex:0 0 38px;place-items:center;border-radius:11px;color:#667085;background:#f8fafc}.settings-nav-link-modern.is-active .settings-nav-link-icon{color:#2563eb;background:#fff}.settings-nav-link-modern:not(.is-active):hover .settings-nav-link-icon{color:#667085;background:#f8fafc}.settings-nav-link-modern:not(.is-active):focus .settings-nav-link-icon,.settings-nav-link-modern:not(.is-active):focus-visible .settings-nav-link-icon,.settings-nav-link-modern:not(.is-active):active .settings-nav-link-icon{color:#667085!important;background:#f8fafc!important}.settings-nav-link-copy{display:block;min-width:0;flex:1}.settings-nav-link-copy strong,.settings-nav-link-copy small{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.settings-nav-link-copy strong{font-size:.78rem;font-weight:800}.settings-nav-link-copy small{margin-top:2px;color:#98a2b3;font-size:.64rem;font-weight:600}.settings-nav-link-modern:not(.is-active) .settings-nav-link-copy small{color:#98a2b3}.settings-page-content{position:relative;min-width:0}.settings-slot-view,.settings-orders-view{width:100%}@media(max-width:900px){.settings-nav-links{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:575px){.settings-page-shell{width:min(100% - 20px,1220px);padding-top:18px}.settings-hero{align-items:flex-start}.settings-hero-icon{width:46px;height:46px;flex-basis:46px}.settings-nav-links{grid-template-columns:1fr}}
</style>