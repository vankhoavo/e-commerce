<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const currentPath = computed(() => page.url.split('?')[0]);

const nav = [
    { label: 'Tổng quan', items: [['/admin', 'Dashboard', 'bi-grid-1x2-fill']] },
    { label: 'Kinh doanh', items: [['/admin/categories', 'Danh mục', 'bi-tags'], ['/admin/products', 'Sản phẩm', 'bi-box-seam'], ['/admin/inventory', 'Kho hàng', 'bi-boxes'], ['/admin/orders', 'Đơn hàng', 'bi-receipt'], ['/admin/coupons', 'Mã giảm giá', 'bi-ticket-perforated'], ['/admin/shipping', 'Phí vận chuyển', 'bi-truck']] },
    { label: 'Hệ thống', items: [['/admin/customers', 'Khách hàng', 'bi-people'], ['/admin/employees', 'Nhân viên', 'bi-person-badge'], ['/admin/administrators', 'Quản trị viên', 'bi-shield-lock-fill']] },
];

const logoutAdmin = () => router.post('/logout', {}, { preserveScroll: false, onSuccess: () => window.location.assign('/') });
</script>

<template>
    <div class="admin-shell">
        <aside class="admin-sidebar d-none d-lg-flex">
            <div class="admin-brand">
                <Link href="/admin" class="shop-brand text-white">
                    <span class="shop-brand-mark"><i class="bi bi-cpu-fill"/></span>
                    <span>TechStore</span>
                </Link>
                <span class="admin-badge">ADMIN</span>
            </div>

            <div class="admin-nav-scroll">
                <template v-for="group in nav" :key="group.label">
                    <div class="admin-nav-title">{{ group.label }}</div>
                    <Link v-for="item in group.items" :key="item[0]" :href="item[0]" class="admin-sidebar-link" :class="{ active: currentPath === item[0] }">
                        <i :class="['bi', item[2]]"/><span>{{ item[1] }}</span>
                    </Link>
                </template>
            </div>

            <div class="admin-sidebar-bottom">
                <button class="admin-store-exit" type="button" @click="logoutAdmin">
                    <span class="admin-store-icon"><i class="bi bi-box-arrow-right"/></span>
                    <span class="admin-store-copy"><strong>Đăng xuất</strong><small>Kết thúc phiên quản trị</small></span>
                    <i class="bi bi-arrow-up-right admin-store-arrow"/>
                </button>
            </div>
        </aside>

        <div class="d-lg-none admin-mobile-top">
            <div class="d-flex justify-content-between align-items-center">
                <Link href="/admin" class="shop-brand text-white">
                    <span class="shop-brand-mark"><i class="bi bi-cpu-fill"/></span>
                    <span>TechStore Admin</span>
                </Link>
                <button class="btn btn-outline-light" data-bs-toggle="offcanvas" data-bs-target="#adminMenu">
                    <i class="bi bi-list fs-4"/>
                </button>
            </div>
            <div id="adminMenu" class="offcanvas offcanvas-start text-bg-dark">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title">Quản trị TechStore</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="offcanvas"/>
                </div>
                <div class="offcanvas-body">
                    <template v-for="group in nav" :key="group.label">
                        <div class="admin-nav-title">{{ group.label }}</div>
                        <Link v-for="item in group.items" :key="item[0]" :href="item[0]" class="admin-sidebar-link" :class="{ active: currentPath === item[0] }">
                            <i :class="['bi', item[2]]"/><span>{{ item[1] }}</span>
                        </Link>
                    </template>
                    <button class="admin-store-exit admin-store-exit-mobile mt-3" type="button" @click="logoutAdmin">
                        <span class="admin-store-icon"><i class="bi bi-box-arrow-right"/></span>
                        <span class="admin-store-copy"><strong>Đăng xuất</strong><small>Kết thúc phiên quản trị</small></span>
                        <i class="bi bi-arrow-up-right admin-store-arrow"/>
                    </button>
                </div>
            </div>
        </div>

        <main class="admin-main">
            <header class="admin-topbar">
                <div>
                    <div class="admin-top-title">Trung tâm quản trị</div>
                    <div class="admin-top-sub">Vận hành cửa hàng • {{ new Date().toLocaleDateString('vi-VN', { weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' }) }}</div>
                </div>
                <div class="admin-top-actions">
                    <span class="admin-live"><i/> Hệ thống hoạt động</span>
                    <button class="admin-top-store" type="button" @click="logoutAdmin">
                        <span class="admin-top-store-icon"><i class="bi bi-box-arrow-right"/></span>
                        <span>Đăng xuất</span>
                        <i class="bi bi-arrow-right-short"/>
                    </button>
                </div>
            </header>
            <div class="admin-content"><slot/></div>
        </main>
    </div>
</template>

<style>
.admin-shell{min-height:100vh;background:#f5f7fb;color:#101828}.admin-sidebar{position:fixed;z-index:20;inset:0 auto 0 0;width:248px;flex-direction:column;background:linear-gradient(180deg,#0b1220 0%,#101a2d 100%);box-shadow:14px 0 40px rgba(15,23,42,.08)}.admin-brand{display:flex;align-items:center;gap:10px;padding:25px 20px 20px}.shop-brand{display:inline-flex;align-items:center;gap:10px;text-decoration:none;font-weight:900;letter-spacing:-.02em}.shop-brand-mark{display:grid;width:36px;height:36px;place-items:center;border-radius:11px;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff}.admin-badge{padding:4px 7px;border:1px solid rgba(255,255,255,.12);border-radius:999px;color:#93c5fd;font-size:8px;font-weight:900;letter-spacing:.12em}.admin-nav-scroll{flex:1;overflow:auto;padding:4px 12px}.admin-nav-title{margin:20px 10px 8px;color:#64748b;font-size:9px;font-weight:900;letter-spacing:.14em;text-transform:uppercase}.admin-sidebar-link{display:flex;align-items:center;gap:11px;width:100%;min-height:42px;margin:2px 0;padding:0 12px;border:0;border-radius:11px;background:transparent;color:#94a3b8;font-size:12px;font-weight:700;text-decoration:none;transition:.18s ease}.admin-sidebar-link i{width:18px;text-align:center;font-size:14px}.admin-sidebar-link:hover{color:#fff;background:rgba(255,255,255,.06)}.admin-sidebar-link.active{color:#fff;background:linear-gradient(90deg,rgba(37,99,235,.95),rgba(79,70,229,.9));box-shadow:0 9px 22px rgba(37,99,235,.22)}.admin-sidebar-bottom{padding:12px;border-top:1px solid rgba(255,255,255,.07)}.admin-store-exit{position:relative;display:flex;align-items:center;gap:11px;width:100%;min-height:58px;padding:8px 10px;border:1px solid rgba(96,165,250,.14);border-radius:15px;background:linear-gradient(135deg,rgba(30,41,59,.96),rgba(15,23,42,.98));color:#e2e8f0;text-align:left;cursor:pointer;box-shadow:0 8px 24px rgba(0,0,0,.12);transition:transform .2s ease,border-color .2s ease,background .2s ease,box-shadow .2s ease}.admin-store-exit::before{content:"";position:absolute;inset:0;border-radius:15px;background:linear-gradient(135deg,rgba(59,130,246,.12),rgba(124,58,237,.08));opacity:0;transition:opacity .2s ease}.admin-store-exit:hover{transform:translateY(-2px);border-color:rgba(96,165,250,.38);background:linear-gradient(135deg,#18243a,#111c31);box-shadow:0 13px 30px rgba(15,23,42,.28)}.admin-store-exit:hover::before{opacity:1}.admin-store-icon{position:relative;z-index:1;display:grid;flex:0 0 36px;width:36px;height:36px;place-items:center;border-radius:11px;background:linear-gradient(135deg,#2563eb,#4f46e5);color:#fff;box-shadow:0 7px 16px rgba(37,99,235,.28)}.admin-store-icon i{font-size:16px}.admin-store-copy{position:relative;z-index:1;display:flex;min-width:0;flex:1;flex-direction:column;gap:2px}.admin-store-copy strong{font-size:11px;font-weight:850;line-height:1.2}.admin-store-copy small{color:#7f8da3;font-size:8px;font-weight:600;line-height:1.2}.admin-store-arrow{position:relative;z-index:1;color:#64748b;font-size:14px;transition:transform .2s ease,color .2s ease}.admin-store-exit:hover .admin-store-arrow{color:#93c5fd;transform:translate(2px,-2px)}.admin-store-exit-mobile{max-width:360px}.admin-main{min-height:100vh;margin-left:248px}.admin-topbar{position:sticky;top:0;z-index:10;display:flex;justify-content:space-between;align-items:center;gap:20px;min-height:76px;padding:14px 30px;border-bottom:1px solid #e8ecf3;background:rgba(255,255,255,.92);backdrop-filter:blur(16px)}.admin-top-title{font-size:14px;font-weight:900}.admin-top-sub{margin-top:2px;color:#98a2b3;font-size:10px}.admin-top-actions{display:flex;align-items:center;gap:12px}.admin-live{display:inline-flex;align-items:center;gap:6px;color:#15803d;font-size:9px;font-weight:800}.admin-live i{width:7px;height:7px;border-radius:50%;background:#22c55e;box-shadow:0 0 0 4px #dcfce7}.admin-top-store{display:inline-flex;align-items:center;gap:8px;min-height:40px;padding:0 13px;border:1px solid #e5e7eb;border-radius:12px;background:linear-gradient(180deg,#fff,#f8fafc);color:#344054;font-size:11px;font-weight:800;cursor:pointer;box-shadow:0 4px 12px rgba(15,23,42,.05);transition:transform .18s ease,border-color .18s ease,box-shadow .18s ease,color .18s ease}.admin-top-store:hover{transform:translateY(-1px);border-color:#bfdbfe;color:#1d4ed8;box-shadow:0 8px 20px rgba(37,99,235,.12)}.admin-top-store-icon{display:grid;width:25px;height:25px;place-items:center;border-radius:8px;background:#eff6ff;color:#2563eb}.admin-top-store-icon i{font-size:13px}.admin-top-store>i{color:#98a2b3;font-size:16px;transition:transform .18s ease}.admin-top-store:hover>i{transform:translateX(2px);color:#2563eb}.admin-content{padding:28px 30px}.admin-page{max-width:1500px;margin:0 auto}.admin-panel,.stat-card,.inventory-stat{transition:transform .18s ease,box-shadow .18s ease}.admin-panel:hover,.stat-card:hover,.inventory-stat:hover{box-shadow:0 16px 38px rgba(15,23,42,.08)!important}.admin-page-head h1{letter-spacing:-.04em}.admin-page-head p{font-size:.8rem}.admin-table>thead>tr>th{background:#f8fafc}.admin-table tbody tr{transition:background .15s}.admin-table tbody tr:hover{background:#fbfdff}.admin-modal-backdrop{backdrop-filter:blur(3px)}.admin-mobile-top{padding:12px 15px;background:#0b1220}.offcanvas .admin-sidebar-link{color:#cbd5e1}.offcanvas .admin-sidebar-link.active{color:#fff}@media(max-width:991px){.admin-main{margin-left:0}.admin-topbar{min-height:66px;padding:12px 16px}.admin-content{padding:18px 14px}.admin-top-actions .admin-live{display:none}}@media(max-width:575px){.admin-top-title{font-size:12px}.admin-top-sub{font-size:8px}.admin-top-store{min-height:36px;padding:0 9px;font-size:10px}.admin-top-store-icon{width:23px;height:23px}}
</style>
