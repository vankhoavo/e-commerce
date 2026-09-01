<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';
defineProps<{ title?: string; description?: string }>();
</script>
<template>
    <div class="auth-page"><div class="auth-glow auth-glow-one"/><div class="auth-glow auth-glow-two"/><div class="container position-relative py-5"><div class="row justify-content-center"><div class="col-12 col-md-10 col-lg-9 col-xl-8"><div class="row g-0 auth-card overflow-hidden"><div class="col-lg-5 d-none d-lg-flex auth-showcase"><div><Link :href="home()" class="shop-brand text-white mb-5"><span class="shop-brand-mark"><i class="bi bi-cpu-fill"/></span><span>TechStore</span></Link><h2>Công nghệ tốt.<br/><span>Trải nghiệm tốt hơn.</span></h2><p>Mua sắm laptop và linh kiện chính hãng với trải nghiệm đơn giản, nhanh chóng và an tâm.</p><div class="auth-points"><span><i class="bi bi-shield-check"/> Thanh toán an toàn</span><span><i class="bi bi-patch-check"/> Sản phẩm chính hãng</span><span><i class="bi bi-truck"/> Giao hàng toàn quốc</span></div></div></div><div class="col-lg-7 bg-white"><div class="auth-form-panel"><div class="d-lg-none text-center mb-4"><Link :href="home()" class="shop-brand justify-content-center"><span class="shop-brand-mark"><i class="bi bi-cpu-fill"/></span><span>TechStore</span></Link></div><div class="mb-4"><h1 class="auth-title">{{ title }}</h1><p class="auth-description">{{ description }}</p></div><slot/></div></div></div></div></div></div></div>
</template>
