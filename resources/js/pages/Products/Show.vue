<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { formatPrice, getProduct } from '@/data/products';
const props = defineProps<{ slug: string }>();
const product = computed(() => getProduct(props.slug));
const selectedImage = ref(0);
const quantity = ref(1);
</script>
<template>
    <Head :title="product?.name || 'Sản phẩm'" />
    <div v-if="product" class="container py-4 py-lg-5">
        <div class="shop-breadcrumb mb-4"><Link href="/">Trang chủ</Link><i class="bi bi-chevron-right"/><Link href="/products">Sản phẩm</Link><i class="bi bi-chevron-right"/><span>{{ product.name }}</span></div>
        <div class="product-detail-card"><div class="row g-4 g-lg-5">
            <div class="col-lg-6"><div class="detail-main-image"><img :src="product.gallery[selectedImage]" :alt="product.name"/></div><div class="detail-thumbs mt-3"><button v-for="(image,index) in product.gallery" :key="image" :class="['detail-thumb',{active:selectedImage===index}]" @click="selectedImage=index"><img :src="image" :alt="`${product.name} ${index+1}`"/></button></div></div>
            <div class="col-lg-6"><span class="detail-brand">{{ product.brand }} · {{ product.category }}</span><h1 class="detail-title">{{ product.name }}</h1><div class="detail-rating"><span><i class="bi bi-star-fill"/> {{ product.rating }}</span><span>{{ product.sold }} đã bán</span><span>Mã SP: TS-{{ String(product.id).padStart(4,'0') }}</span></div><p class="detail-description">{{ product.shortDescription }}</p><div class="detail-price"><strong>{{ formatPrice(product.price) }}</strong><del v-if="product.oldPrice">{{ formatPrice(product.oldPrice) }}</del><span v-if="product.oldPrice" class="discount-pill">-{{ Math.round((1-product.price/product.oldPrice)*100) }}%</span></div><div class="stock-line"><i class="bi bi-check-circle-fill"/> Còn hàng · {{ product.stock }} sản phẩm</div><hr class="my-4"/><div class="d-flex align-items-center gap-3 flex-wrap"><div class="quantity-control"><button @click="quantity=Math.max(1,quantity-1)">−</button><span>{{ quantity }}</span><button @click="quantity++">+</button></div><button class="btn btn-primary btn-lg px-4 flex-grow-1"><i class="bi bi-cart-plus me-2"/>Thêm vào giỏ hàng</button></div><div class="detail-benefits mt-4"><div><i class="bi bi-shield-check"/><span><b>Chính hãng</b><small>Bảo hành theo nhà sản xuất</small></span></div><div><i class="bi bi-truck"/><span><b>Giao hàng toàn quốc</b><small>Đóng gói an toàn, theo dõi đơn</small></span></div></div></div>
        </div></div>
        <div class="row g-4 mt-1 mt-lg-4"><div class="col-lg-8"><div class="detail-section"><h2>Thông tin sản phẩm</h2><p>{{ product.description }}</p><div class="spec-table"><div v-for="(value,key) in product.specs" :key="key" class="spec-row"><span>{{ key }}</span><strong>{{ value }}</strong></div></div></div></div><div class="col-lg-4"><div class="detail-section"><h2>Cam kết TechStore</h2><ul class="commit-list"><li><i class="bi bi-patch-check"/> Sản phẩm chính hãng</li><li><i class="bi bi-box-seam"/> Đóng gói chống sốc</li><li><i class="bi bi-arrow-repeat"/> Hỗ trợ đổi trả</li><li><i class="bi bi-headset"/> Tư vấn kỹ thuật</li></ul></div></div></div>
    </div>
    <div v-else class="container py-5"><div class="empty-catalog"><i class="bi bi-box-seam"/><h3>Không tìm thấy sản phẩm</h3><Link href="/products" class="btn btn-primary mt-2">Quay lại sản phẩm</Link></div></div>
</template>
