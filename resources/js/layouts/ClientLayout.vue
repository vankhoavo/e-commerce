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
    mobileMenuOpen.value = false;
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
    <div class="top-strip d-none d-md-block"><div class="container d-flex justify-content-between align-items-center small"><span><i class="bi bi-lightning-charge-fill me-1"/> Ưu đãi công nghệ mỗi ngày</span><span><i class="bi bi-truck me-1"/> Giao hàng toàn quốc <b>•</b> Hỗ trợ 24/7</span></div></div>
    <nav class="shop-navbar navbar navbar-expand-lg sticky-top">
        <div class="container nav-container">
            <Link href="/" class="shop-brand"><span class="shop-brand-mark"><i class="bi bi-cpu-fill"/></span><span class="shop-brand-copy"><strong>TechStore</strong><small>CÔNG NGHỆ &amp; LINH KIỆN</small></span></Link>
            <div class="nav-mobile-actions d-lg-none"><Link href="/cart" class="nav-icon-btn cart-nav-btn position-relative" aria-label="Giỏ hàng"><i class="bi bi-bag"/><span v-if="cartCount" class="cart-badge">{{ cartCount > 99 ? '99+' : cartCount }}</span></Link><button class="nav-menu-btn" type="button" @click="mobileMenuOpen = !mobileMenuOpen" aria-label="Mở menu"><i :class="['bi', mobileMenuOpen ? 'bi-x-lg' : 'bi-list']"/></button></div>
            <div :class="['navbar-collapse', 'client-navbar-collapse', mobileMenuOpen ? 'show' : '']">
                <div class="nav-primary-links"><Link href="/" class="shop-nav-link">Trang chủ</Link><Link href="/products?category=laptop" class="shop-nav-link">Laptop</Link><Link href="/products?category=laptop-components" class="shop-nav-link">Linh kiện laptop</Link><Link href="/products?category=laptop-accessories" class="shop-nav-link">Phụ kiện laptop</Link></div>
                <form class="nav-search" @submit.prevent="submitSearch"><i class="bi bi-search nav-search-icon"/><input v-model="search" type="search" placeholder="Tìm laptop, linh kiện, phụ kiện..." aria-label="Tìm sản phẩm"/><button type="submit" class="nav-search-submit"><i class="bi bi-arrow-right"/><span>Tìm</span></button></form>
                <div class="nav-actions">
                    <Link href="/cart" class="nav-cart d-none d-lg-flex position-relative" aria-label="Giỏ hàng"><span class="nav-cart-icon"><i class="bi bi-bag"/></span><span class="nav-cart-label"><strong>Giỏ hàng</strong><small>{{ cartCount }} sản phẩm</small></span><span v-if="cartCount" class="cart-badge nav-cart-badge" style="top:-8px;right:-8px;z-index:5;">{{ cartCount > 99 ? '99+' : cartCount }}</span></Link>
                    <template v-if="!authUser"><Link href="/login" class="nav-login-btn"><i class="bi bi-person me-1"/><span>Đăng nhập</span></Link><Link href="/register" class="nav-register-btn"><i class="bi bi-person-plus me-1"/><span>Đăng ký</span></Link></template>
                    <Link v-else href="/settings/profile" class="account-chip"><span class="account-avatar"><i class="bi bi-person-fill"/></span><span class="account-info"><strong>{{ authUser.name }}</strong><small>Tài khoản</small></span><i class="bi bi-chevron-down account-chevron"/></Link>
                </div>
                <div class="d-lg-none mobile-nav-panel"><form class="client-search-mobile" @submit.prevent="submitSearch"><i class="bi bi-search"/><input v-model="search" type="search" placeholder="Tìm laptop, linh kiện, phụ kiện..." aria-label="Tìm sản phẩm"/><button type="submit">Tìm</button></form><div class="mobile-nav-links"><Link href="/" @click="mobileMenuOpen = false"><i class="bi bi-house"/> Trang chủ</Link><Link href="/products?category=laptop" @click="mobileMenuOpen = false"><i class="bi bi-laptop"/> Laptop</Link><Link href="/products?category=laptop-components" @click="mobileMenuOpen = false"><i class="bi bi-cpu"/> Linh kiện laptop</Link><Link href="/products?category=laptop-accessories" @click="mobileMenuOpen = false"><i class="bi bi-keyboard"/> Phụ kiện laptop</Link></div><div v-if="!authUser" class="mobile-auth-actions"><Link href="/login" class="nav-login-btn">Đăng nhập</Link><Link href="/register" class="nav-register-btn">Đăng ký tài khoản</Link></div></div>
            </div>
        </div>
    </nav>
    <main class="flex-grow-1"><slot/></main>
    <footer class="shop-footer text-white mt-5"><div class="container py-5"><div class="row g-4"><div class="col-lg-5"><div class="shop-brand text-white mb-3"><span class="shop-brand-mark"><i class="bi bi-cpu-fill"/></span><span class="shop-brand-copy"><strong>TechStore</strong><small>CÔNG NGHỆ &amp; LINH KIỆN</small></span></div><p class="text-white-50 mb-0">Laptop, linh kiện laptop và phụ kiện chính hãng với trải nghiệm mua sắm nhanh chóng, an toàn.</p></div><div class="col-6 col-lg-2"><h6 class="fw-bold">Mua sắm</h6><div class="footer-links"><Link href="/products">Sản phẩm</Link><Link href="/products?category=laptop">Laptop</Link><Link href="/cart">Giỏ hàng</Link><Link href="/orders">Đơn hàng</Link></div></div><div class="col-6 col-lg-2"><h6 class="fw-bold">Tài khoản</h6><div class="footer-links"><Link href="/login">Đăng nhập</Link><Link href="/register">Đăng ký</Link><Link href="/settings/profile">Hồ sơ</Link></div></div><div class="col-lg-3"><h6 class="fw-bold">Hỗ trợ</h6><p class="text-white-50 small mb-2">Hotline: 1900 0000</p><p class="text-white-50 small mb-0">Email: support@techstore.local</p></div></div><hr class="border-secondary my-4"/><div class="small text-white-50">© {{ new Date().getFullYear() }} TechStore. All rights reserved.</div></div></footer>
</div>
</template>
