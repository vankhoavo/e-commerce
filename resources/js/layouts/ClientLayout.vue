<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const search = ref('');
const cartCount = ref(0);
const mobileMenuOpen = ref(false);
const page = usePage();
const authUser = computed(() => (page.props as any).auth?.user ?? null);

const submitSearch = () => {
    const value = search.value.trim();
    router.visit(value ? `/products?search=${encodeURIComponent(value)}` : '/products');
};

function readCartCount() {
    try {
        const cart = JSON.parse(localStorage.getItem('techstore_cart') ?? '[]');
        cartCount.value = Array.isArray(cart) ? cart.reduce((total: number, item: any) => total + Number(item.quantity ?? 1), 0) : 0;
    } catch {
        cartCount.value = 0;
    }
}

onMounted(() => {
    readCartCount();
    window.addEventListener('storage', readCartCount);
    window.addEventListener('techstore-cart-updated', readCartCount);
});

onUnmounted(() => {
    window.removeEventListener('storage', readCartCount);
    window.removeEventListener('techstore-cart-updated', readCartCount);
});
</script>

<template>
<div class="min-vh-100 d-flex flex-column client-shell">
    <div class="top-strip d-none d-md-block"><div class="container d-flex justify-content-between align-items-center small"><span><i class="bi bi-lightning-charge-fill me-1"/>Ưu đãi công nghệ mỗi ngày</span><span><i class="bi bi-truck me-1"/>Giao hàng toàn quốc • Hỗ trợ 24/7</span></div></div>
    <nav class="navbar navbar-expand-lg shop-navbar sticky-top">
        <div class="container py-2">
            <Link href="/" class="shop-brand me-lg-4"><span class="shop-brand-mark"><i class="bi bi-cpu-fill"/></span><span>TechStore</span></Link>
            <div class="d-flex align-items-center gap-2 ms-auto d-lg-none">
                <Link href="/cart" class="nav-icon-btn position-relative" aria-label="Giỏ hàng"><i class="bi bi-bag fs-5"/><span v-if="cartCount" class="cart-badge">{{ cartCount > 99 ? '99+' : cartCount }}</span></Link>
                <button class="navbar-toggler border-0 shadow-none p-2" type="button" @click="mobileMenuOpen = !mobileMenuOpen"><i :class="['bi', mobileMenuOpen ? 'bi-x-lg' : 'bi-list', 'fs-4']"/></button>
            </div>
            <div :class="['navbar-collapse','d-lg-flex',mobileMenuOpen ? 'show' : '']">
                <form class="nav-search d-none d-xl-flex mx-auto" @submit.prevent="submitSearch"><i class="bi bi-search"/><input v-model="search" placeholder="Tìm kiếm laptop, linh kiện, phụ kiện..." aria-label="Tìm sản phẩm"/><button type="submit">Tìm kiếm</button></form>
                <div class="navbar-nav align-items-lg-center gap-lg-1 ms-lg-3">
                    <Link href="/" class="nav-link shop-nav-link">Trang chủ</Link><Link href="/products?category=laptop" class="nav-link shop-nav-link">Laptop</Link><Link href="/products?category=components" class="nav-link shop-nav-link">Linh kiện</Link><Link href="/products?category=monitor" class="nav-link shop-nav-link">Màn hình</Link><Link href="/products?category=accessories" class="nav-link shop-nav-link">Phụ kiện</Link>
                </div>
                <div class="d-flex align-items-center gap-2 ms-lg-3 mt-3 mt-lg-0">
                    <Link href="/cart" class="nav-icon-btn position-relative d-none d-lg-inline-flex" aria-label="Giỏ hàng"><i class="bi bi-bag fs-5"/><span v-if="cartCount" class="cart-badge">{{ cartCount > 99 ? '99+' : cartCount }}</span></Link>
                    <template v-if="!authUser"><Link href="/login" class="btn nav-login-btn">Đăng nhập</Link><Link href="/register" class="btn nav-register-btn"><i class="bi bi-person-plus me-1"/>Đăng ký</Link></template>
                    <Link v-else href="/settings/profile" class="account-chip"><span class="account-avatar"><i class="bi bi-person-fill"/></span><span class="d-none d-xl-inline">{{ authUser.name }}</span></Link>
                </div>
                <div class="d-lg-none mt-3 pt-3 border-top">
                    <form class="client-search-mobile mb-3" @submit.prevent="submitSearch"><i class="bi bi-search"/><input v-model="search" placeholder="Tìm kiếm sản phẩm..." aria-label="Tìm sản phẩm"/></form>
                    <div v-if="!authUser" class="d-grid gap-2"><Link href="/login" class="btn nav-login-btn">Đăng nhập</Link><Link href="/register" class="btn nav-register-btn">Đăng ký tài khoản</Link></div>
                </div>
            </div>
        </div>
    </nav>
    <main class="flex-grow-1"><slot/></main>
    <footer class="shop-footer text-white mt-5"><div class="container py-5"><div class="row g-4"><div class="col-lg-5"><div class="shop-brand text-white mb-3"><span class="shop-brand-mark"><i class="bi bi-cpu-fill"/></span><span>TechStore</span></div><p class="text-white-50 mb-0">Laptop, linh kiện và phụ kiện công nghệ chính hãng với trải nghiệm mua sắm nhanh chóng, an toàn.</p></div><div class="col-6 col-lg-2"><h6 class="fw-bold">Mua sắm</h6><div class="footer-links"><Link href="/products">Sản phẩm</Link><Link href="/cart">Giỏ hàng</Link><Link href="/orders">Đơn hàng</Link></div></div><div class="col-6 col-lg-2"><h6 class="fw-bold">Tài khoản</h6><div class="footer-links"><Link href="/login">Đăng nhập</Link><Link href="/register">Đăng ký</Link><Link href="/settings/profile">Hồ sơ</Link></div></div><div class="col-lg-3"><h6 class="fw-bold">Hỗ trợ</h6><p class="text-white-50 small mb-2">Hotline: 1900 0000</p><p class="text-white-50 small mb-0">Email: support@techstore.local</p></div></div><hr class="border-secondary my-4"/><div class="small text-white-50">© {{ new Date().getFullYear() }} TechStore. All rights reserved.</div></div></footer>
</div>
</template>
