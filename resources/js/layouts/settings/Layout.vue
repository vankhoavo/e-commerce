<script setup lang="ts">
import { Settings, UserRound, ShieldCheck, UsersRound, ReceiptText } from '@lucide/vue';
import { usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, provide, readonly, ref, watch } from 'vue';
import Orders from '@/components/settings/OrdersDatabase.vue';

export type SettingsSection = 'profile' | 'security' | 'member' | 'orders';
const SETTINGS_STORAGE_KEY = 'techstore.settings.section';
const settingsNavItems: { key: SettingsSection; title: string; description: string; icon: typeof UserRound }[] = [
    { key: 'profile', title: 'Thông tin cá nhân', description: 'Hồ sơ', icon: UserRound },
    { key: 'security', title: 'Bảo mật', description: 'Mật khẩu & bảo vệ', icon: ShieldCheck },
    { key: 'member', title: 'Thành viên', description: 'Hạng & mã giảm giá', icon: UsersRound },
    { key: 'orders', title: 'Đơn hàng', description: 'Lịch sử & hóa đơn', icon: ReceiptText },
];
const page = usePage();
function isSettingsSection(value: string | null): value is SettingsSection { return value !== null && settingsNavItems.some((item) => item.key === value); }
function readStoredSection(): SettingsSection | null { if (typeof window === 'undefined') return null; try { const storedValue=window.sessionStorage.getItem(SETTINGS_STORAGE_KEY); return isSettingsSection(storedValue)?storedValue:null; } catch { return null; } }
function readUrlSection(): SettingsSection | null { if (typeof window === 'undefined') return null; const urlValue=new URLSearchParams(window.location.search).get('section'); return isSettingsSection(urlValue)?urlValue:null; }
function readInitialSection(): SettingsSection { return readUrlSection() ?? readStoredSection() ?? 'profile'; }
const activeSection=ref<SettingsSection>(readInitialSection());
provide('techstore-settings-section',readonly(activeSection));
function persistSection(section:SettingsSection):void { if(typeof document!=='undefined')document.body.dataset.techstoreSettingsSection=section; if(typeof window!=='undefined'){try{window.sessionStorage.setItem(SETTINGS_STORAGE_KEY,section);}catch{}} }
function navigationSection():SettingsSection|null { return readUrlSection() ?? (typeof window!=='undefined' && typeof window.history.state?.settingsSection==='string' && isSettingsSection(window.history.state.settingsSection)?window.history.state.settingsSection:null); }
function syncSectionFromNavigation():void { const section=navigationSection()??readStoredSection()??'profile'; if(activeSection.value!==section)activeSection.value=section; persistSection(section); }
function selectSection(section:SettingsSection):void { activeSection.value=section; persistSection(section); if(typeof window!=='undefined'){const url=new URL(window.location.href);url.pathname='/settings/profile';if(section==='profile')url.searchParams.delete('section');else url.searchParams.set('section',section);window.history.pushState({...window.history.state,settingsSection:section},'',`${url.pathname}${url.search}${url.hash}`);}}
watch(activeSection,(section)=>{const requested=navigationSection()??readStoredSection();if(requested&&requested!==section){activeSection.value=requested;persistSection(requested);}});
watch(()=>page.url,()=>syncSectionFromNavigation());
onMounted(()=>{syncSectionFromNavigation();window.addEventListener('popstate',syncSectionFromNavigation);window.setTimeout(syncSectionFromNavigation,0);});
onBeforeUnmount(()=>window.removeEventListener('popstate',syncSectionFromNavigation));
</script>
<template>
<div class="settings-page-shell">
<header class="settings-hero"><div class="settings-hero-icon"><Settings :size="22" /></div><div><span class="settings-eyebrow">QUẢN LÝ TÀI KHOẢN</span><h1>Tài khoản</h1><p>Quản lý hồ sơ, bảo mật, thành viên và lịch sử mua hàng.</p></div></header>
<nav class="settings-nav settings-nav-modern" aria-label="Tài khoản"><div class="settings-nav-links"><button v-for="item in settingsNavItems" :key="item.key" type="button" class="settings-nav-link-modern" :class="{ 'is-active': activeSection === item.key }" :aria-current="activeSection === item.key ? 'page' : undefined" @click="selectSection(item.key)"><span class="settings-nav-link-icon"><component :is="item.icon" :size="18" /></span><span class="settings-nav-link-copy"><strong>{{ item.title }}</strong><small>{{ item.description }}</small></span></button></div></nav>
<main class="settings-page-content"><div v-show="activeSection !== 'orders' && activeSection !== 'member'" class="settings-slot-view"><slot /></div><div v-show="activeSection === 'orders'" class="settings-orders-view"><Orders /></div><div v-show="activeSection === 'member'" class="settings-member-view"><slot name="member" /></div></main>
</div>
</template>
<style>
.settings-page-shell{width:min(100% - 32px,1220px);margin:0 auto;padding:30px 0 56px}.settings-hero{display:flex;align-items:center;gap:16px;margin-bottom:18px;padding:4px 2px}.settings-hero-icon{display:grid;width:52px;height:52px;flex:0 0 52px;place-items:center;border:1px solid #dbeafe;border-radius:16px;color:#2563eb;background:linear-gradient(145deg,#eff6ff,#f5f3ff);box-shadow:0 10px 24px rgba(37,99,235,.10)}.settings-eyebrow{display:block;margin-bottom:4px;color:#2563eb;font-size:11px;font-weight:900;letter-spacing:.14em}.settings-hero h1{margin:0;color:#101828;font-size:clamp(1.65rem,3vw,2.15rem);font-weight:850;letter-spacing:-.04em}.settings-hero p{margin:5px 0 0;color:#667085;font-size:.84rem}.settings-nav-modern{margin:0 0 28px;padding:7px;border:1px solid #e4e7ec;border-radius:18px;background:rgba(255,255,255,.96);box-shadow:0 12px 32px rgba(16,24,40,.055)}.settings-nav-links{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:6px}.settings-nav-link-modern{position:relative;display:flex;align-items:center;min-width:0;width:100%;gap:10px;padding:11px 12px;border:1px solid transparent;border-radius:13px;color:#667085;background:#fff;text-align:left;cursor:pointer}.settings-nav-link-modern.is-active{color:#1d4ed8!important;border-color:#bfdbfe!important;background:linear-gradient(135deg,#eff6ff,#f5f3ff)!important;box-shadow:0 7px 18px rgba(37,99,235,.08)}.settings-nav-link-icon{display:grid;width:38px;height:38px;flex:0 0 38px;place-items:center;border-radius:11px;color:#667085;background:#f8fafc}.settings-nav-link-modern.is-active .settings-nav-link-icon{color:#2563eb;background:#fff}.settings-nav-link-copy{display:block;min-width:0;flex:1}.settings-nav-link-copy strong,.settings-nav-link-copy small{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.settings-nav-link-copy strong{font-size:.78rem;font-weight:800}.settings-nav-link-copy small{margin-top:2px;color:#98a2b3;font-size:.64rem;font-weight:600}.settings-page-content{position:relative;min-width:0}.settings-slot-view,.settings-orders-view,.settings-member-view{width:100%}@media(max-width:900px){.settings-nav-links{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:575px){.settings-page-shell{width:min(100% - 20px,1220px);padding-top:18px}.settings-hero{align-items:flex-start}.settings-hero-icon{width:46px;height:46px;flex-basis:46px}.settings-nav-links{grid-template-columns:1fr}}
</style>
