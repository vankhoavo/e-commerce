<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { getCartStorageKey, migrateLegacyCart, readCart } from '@/lib/cart';

const search = ref('');
const cartCount = ref(0);
const mobileMenuOpen = ref(false);
const page = usePage();
const authUser = computed(() => (page.props as any).auth?.user ?? null);
const userId = computed<number | null>(() => authUser.value?.id ? Number(authUser.value.id) : null);
const submitSearch = () => { const value = search.value.trim(); router.visit(value ? `/products?search=${encodeURIComponent(value)}` : '/products'); mobileMenuOpen.value = false; };

function syncAccountCart() {
    if (!userId.value) {
        localStorage.removeItem('techstore_cart');
        cartCount.value = 0;
        return;
    }

    migrateLegacyCart(userId.value);
    const cart = readCart(userId.value);
    cartCount.value = cart.reduce((total: number, item: any) => total + Number(item.quantity ?? 1), 0);
}

function readCartCount() {
    syncAccountCart();
}

function guardGuestCart(event: MouseEvent) {
    if (authUser.value) return;
    const target = event.target as HTMLElement | null;
    const button = target?.closest<HTMLElement>('.product-cart-action, .catalog-cart-btn, .add-cart-btn');
    if (!button) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    const returnTo = `${window.location.pathname}${window.location.search}`;
    router.visit(`/login?redirect=${encodeURIComponent(returnTo)}`);
}

function handleCartUpdated() {
    syncAccountCart();
}

watch(userId, () => syncAccountCart());

onMounted(() => {
    syncAccountCart();
    window.addEventListener('storage', readCartCount);
    window.addEventListener('techstore-cart-updated', handleCartUpdated);
    window.addEventListener('click', guardGuestCart, true);
});
onUnmounted(() => {
    window.removeEventListener('storage', readCartCount);
    window.removeEventListener('techstore-cart-updated', handleCartUpdated);
    window.removeEventListener('click', guardGuestCart, true);
});
</script>

<template>
<div class="min-vh-100 d-flex flex-column client-shell">
    <div class="top-strip d-none d-md-block"><div class="container d-flex justify-content-between align-items-center small"><span><i class="bi bi-lightning-charge-fill me-1"/> Ưu đãi công nghệ mỗi ngày</span><span><i class="bi bi-truck me-1"/> Giao hàng toàn quốc <b>•</b> Hỗ trợ 24/7</span></div></div>
    <nav class="shop-navbar navbar navbar-expand-lg sticky-top"><div class="container nav-container">
        <Link href="/" class="shop-brand"><span class="shop-brand-mark"><i class="bi bi-cpu-fill"/></span><span class="shop-brand-copy"><strong>TechStore</strong><small>CÔNG NGHỆ &amp; LINH KIỆN</small></span></Link>
        <div class="nav-mobile-actions d-lg-none"><Link href="/cart" class="nav-icon-btn cart-nav-btn position-relative" aria-label="Giỏ hàng"><i class="bi bi-bag"/><span v-if="cartCount" class="cart-badge">{{ cartCount > 99 ? '99+' : cartCount }}</span></Link><button class="nav-menu-btn" type="button" @click="mobileMenuOpen = !mobileMenuOpen" aria-label="Mở menu"><i :class="['bi', mobileMenuOpen ? 'bi-x-lg' : 'bi-list']"/></button></div>
        <div :class="['navbar-collapse', 'client-navbar-collapse', mobileMenuOpen ? 'show' : '']">
            <div class="nav-primary-links"><Link href="/" class="shop-nav-link">Trang chủ</Link><Link href="/products?category=laptop" class="shop-nav-link">Laptop</Link><Link href="/products?category=laptop-components" class="shop-nav-link">Linh kiện laptop</Link><Link href="/products?category=laptop-accessories" class="shop-nav-link">Phụ kiện laptop</Link></div>
            <form class="nav-search" @submit.prevent="submitSearch"><i class="bi bi-search nav-search-icon"/><input v-model="search" type="search" placeholder="Tìm laptop, linh kiện, phụ kiện..." aria-label="Tìm sản phẩm"/><button type="submit" class="nav-search-submit"><i class="bi bi-arrow-right"/><span>Tìm</span></button></form>
            <div class="nav-actions">
                <Link href="/cart" class="nav-cart d-none d-lg-flex position-relative" aria-label="Giỏ hàng"><span class="nav-cart-icon"><i class="bi bi-bag"/></span><span class="nav-cart-label"><strong>Giỏ hàng</strong><small>{{ cartCount }} sản phẩm</small></span><span v-if="cartCount" class="cart-badge nav-cart-badge" style="top:-8px;right:-8px;z-index:5;">{{ cartCount > 99 ? '99+' : cartCount }}</span></Link>
                <template v-if="!authUser"><Link href="/login" class="nav-login-btn"><i class="bi bi-person me-1"/><span>Đăng nhập</span></Link><Link href="/register" class="nav-register-btn"><i class="bi bi-person-plus me-1"/><span>Đăng ký</span></Link></template>
                <Link v-else href="/settings/profile" class="account-chip"><span class="account-avatar"><i class="bi bi-cpu-fill account-avatar-fallback"/><img v-if="authUser.avatar" :src="authUser.avatar" alt="" referrerpolicy="no-referrer" @error="($event.currentTarget as HTMLImageElement).style.display = 'none'"/></span><span class="account-info"><strong>{{ authUser.name }}</strong><small>Tài khoản</small></span><i class="bi bi-chevron-down account-chevron"/></Link>
            </div>
            <div class="d-lg-none mobile-nav-panel"><form class="client-search-mobile" @submit.prevent="submitSearch"><i class="bi bi-search"/><input v-model="search" type="search" placeholder="Tìm laptop, linh kiện, phụ kiện..." aria-label="Tìm sản phẩm"/><button type="submit">Tìm</button></form><div class="mobile-nav-links"><Link href="/" @click="mobileMenuOpen = false"><i class="bi bi-house"/> Trang chủ</Link><Link href="/products?category=laptop" @click="mobileMenuOpen = false"><i class="bi bi-laptop"/> Laptop</Link><Link href="/products?category=laptop-components" @click="mobileMenuOpen = false"><i class="bi bi-cpu"/> Linh kiện laptop</Link><Link href="/products?category=laptop-accessories" @click="mobileMenuOpen = false"><i class="bi bi-keyboard"/> Phụ kiện laptop</Link></div><div v-if="!authUser" class="mobile-auth-actions"><Link href="/login" class="nav-login-btn">Đăng nhập</Link><Link href="/register" class="nav-register-btn">Đăng ký tài khoản</Link></div></div>
        </div>
    </div></nav>
    <main class="flex-grow-1"><slot/></main>
    <footer class="shop-footer text-white mt-5"><div class="container py-5"><div class="row g-4">
        <div class="col-lg-4"><div class="shop-brand text-white mb-3"><span class="shop-brand-mark"><i class="bi bi-cpu-fill"/></span><span class="shop-brand-copy"><strong>TechStore</strong><small>CÔNG NGHỆ &amp; LINH KIỆN</small></span></div><p class="text-white-50 mb-3">Laptop, linh kiện laptop và phụ kiện chính hãng với trải nghiệm mua sắm nhanh chóng, an toàn.</p><div class="footer-trust"><span><i class="bi bi-shield-check"/> Thanh toán an toàn</span><span><i class="bi bi-truck"/> Giao hàng toàn quốc</span></div></div>
        <div class="col-6 col-lg-2"><h6 class="fw-bold">Mua sắm</h6><div class="footer-links"><Link href="/products">Sản phẩm</Link><Link href="/products?category=laptop">Laptop</Link><Link href="/products?category=laptop-components">Linh kiện</Link><Link href="/products?category=laptop-accessories">Phụ kiện</Link><Link href="/cart">Giỏ hàng</Link></div></div>
        <div class="col-6 col-lg-2"><h6 class="fw-bold">Tài khoản</h6><div class="footer-links"><Link href="/login">Đăng nhập</Link><Link href="/register">Đăng ký</Link><Link href="/settings/profile">Hồ sơ</Link><Link href="/orders">Đơn hàng</Link></div></div>
        <div class="col-lg-4"><h6 class="fw-bold">Thanh toán &amp; liên hệ</h6><div class="footer-payment"><span class="payment-icon paypal"><i class="bi bi-paypal"/></span><div><strong>Thanh toán</strong><small>Hỗ trợ thanh toán an toàn</small></div></div><div class="footer-contact"><span class="contact-icon"><i class="bi bi-headset"/></span><div><strong>Thông tin liên hệ</strong><small>1900 0000 · support@techstore.local</small></div></div></div>
    </div><hr class="border-secondary my-4"/><div class="d-flex flex-column flex-md-row justify-content-between gap-2 small text-white-50"><span>© {{ new Date().getFullYear() }} TechStore. Bảo lưu mọi quyền.</span><span>Chính sách bảo mật · Điều khoản sử dụng</span></div></div></footer>
</div>
</template>

<style scoped>
.footer-trust{display:flex;flex-wrap:wrap;gap:8px}.footer-trust span{display:inline-flex;align-items:center;gap:6px;padding:7px 9px;border:1px solid rgba(255,255,255,.1);border-radius:9px;color:rgba(255,255,255,.68);font-size:9px}.footer-trust i{color:#8fb8ff}.footer-payment,.footer-contact{display:flex;align-items:center;gap:11px;padding:10px 0}.footer-contact{border-top:1px solid rgba(255,255,255,.1)}.payment-icon,.contact-icon{display:grid;flex:0 0 38px;width:38px;height:38px;place-items:center;border-radius:10px;font-size:18px}.payment-icon{color:#0f62c9;background:#fff}.contact-icon{color:#8fb8ff;background:rgba(255,255,255,.08)}.footer-payment strong,.footer-contact strong{display:block;color:#fff;font-size:11px}.footer-payment small,.footer-contact small{display:block;margin-top:2px;color:rgba(255,255,255,.55);font-size:9px}.account-avatar{position:relative}.account-avatar-fallback{display:grid;width:100%;height:100%;place-items:center;color:var(--shop-primary);background:#eff6ff;border-radius:inherit}.account-avatar img{position:absolute;inset:0;z-index:2;width:100%;height:100%;object-fit:cover;border-radius:inherit;background:#eff6ff}
</style>
