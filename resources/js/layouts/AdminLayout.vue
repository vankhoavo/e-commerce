<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const currentPath = computed(() => page.url.split('?')[0]);
</script>

<template>
    <div class="admin-shell">
        <aside class="admin-sidebar d-none d-lg-block py-4">
            <div class="px-4 mb-4">
                <Link href="/" class="shop-brand text-white"><span class="shop-brand-mark"><i class="bi bi-cpu-fill" /></span><span>TechStore</span></Link>
                <div class="small text-secondary mt-2">Khu vực quản trị</div>
            </div>
            <div class="px-2">
                <div class="text-uppercase small text-secondary fw-bold px-3 mb-2">Tổng quan</div>
                <Link href="/admin" class="admin-sidebar-link" :class="{ active: currentPath === '/admin' }"><i class="bi bi-grid-1x2-fill" /> Dashboard</Link>
                <div class="text-uppercase small text-secondary fw-bold px-3 mt-4 mb-2">Kinh doanh</div>
                <Link href="/admin/categories" class="admin-sidebar-link"><i class="bi bi-tags" /> Danh mục</Link>
                <Link href="/admin/products" class="admin-sidebar-link"><i class="bi bi-box-seam" /> Sản phẩm</Link>
                <Link href="/admin/orders" class="admin-sidebar-link"><i class="bi bi-receipt" /> Đơn hàng</Link>
                <Link href="/admin/coupons" class="admin-sidebar-link"><i class="bi bi-ticket-perforated" /> Mã giảm giá</Link>
                <Link href="/admin/shipping" class="admin-sidebar-link"><i class="bi bi-truck" /> Phí vận chuyển</Link>
                <div class="text-uppercase small text-secondary fw-bold px-3 mt-4 mb-2">Hệ thống</div>
                <Link href="/admin/customers" class="admin-sidebar-link"><i class="bi bi-people" /> Khách hàng</Link>
                <Link href="/admin/employees" class="admin-sidebar-link"><i class="bi bi-person-badge" /> Nhân viên</Link>
            </div>
        </aside>

        <div class="d-lg-none bg-dark p-3">
            <div class="d-flex justify-content-between align-items-center">
                <Link href="/" class="shop-brand text-white"><span class="shop-brand-mark"><i class="bi bi-cpu-fill" /></span><span>TechStore Admin</span></Link>
                <button class="btn btn-outline-light" data-bs-toggle="offcanvas" data-bs-target="#adminMenu" aria-label="Mở menu"><i class="bi bi-list fs-4" /></button>
            </div>
            <div id="adminMenu" class="offcanvas offcanvas-start text-bg-dark">
                <div class="offcanvas-header"><h5 class="offcanvas-title">Quản trị</h5><button class="btn-close btn-close-white" data-bs-dismiss="offcanvas" /></div>
                <div class="offcanvas-body d-grid align-content-start">
                    <Link href="/admin" class="admin-sidebar-link">Dashboard</Link>
                    <Link href="/admin/categories" class="admin-sidebar-link">Danh mục</Link>
                    <Link href="/admin/products" class="admin-sidebar-link">Sản phẩm</Link>
                    <Link href="/admin/orders" class="admin-sidebar-link">Đơn hàng</Link>
                    <Link href="/admin/coupons" class="admin-sidebar-link">Mã giảm giá</Link>
                    <Link href="/admin/shipping" class="admin-sidebar-link">Phí vận chuyển</Link>
                    <Link href="/admin/customers" class="admin-sidebar-link">Khách hàng</Link>
                    <Link href="/admin/employees" class="admin-sidebar-link">Nhân viên</Link>
                </div>
            </div>
        </div>

        <main class="admin-main">
            <header class="bg-white border-bottom px-3 px-lg-4 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div><div class="fw-bold">Bảng điều khiển</div><div class="small text-secondary">Quản lý cửa hàng TechStore</div></div>
                    <Link href="/" class="btn btn-outline-secondary btn-sm"><i class="bi bi-shop me-1" /> Xem cửa hàng</Link>
                </div>
            </header>
            <div class="p-3 p-lg-4"><slot /></div>
        </main>
    </div>
</template>
