<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { categories, formatPrice, products } from '@/data/products';

type Props = { category?: string; search?: string };
const props = defineProps<Props>();
const query = ref(props.search ?? '');
const activeCategory = ref(props.category || 'all');
const sort = ref('featured');
const filteredProducts = computed(() => {
    let result = products.filter((p) => activeCategory.value === 'all' || p.categorySlug === activeCategory.value);
    const q = query.value.trim().toLowerCase();
    if (q) result = result.filter((p) => `${p.name} ${p.brand} ${p.category}`.toLowerCase().includes(q));
    if (sort.value === 'price-low') result = [...result].sort((a, b) => a.price - b.price);
    if (sort.value === 'price-high') result = [...result].sort((a, b) => b.price - a.price);
    if (sort.value === 'rating') result = [...result].sort((a, b) => b.rating - a.rating);
    return result;
});
</script>
<template>
    <Head title="Sản phẩm" />
    <div class="container py-4 py-lg-5">
        <div class="shop-breadcrumb mb-4"><Link href="/">Trang chủ</Link><i class="bi bi-chevron-right" /><span>Sản phẩm</span></div>
        <div class="catalog-hero mb-4 mb-lg-5"><div><span class="section-kicker">TECHSTORE CATALOG</span><h1>Khám phá sản phẩm</h1><p>Laptop, linh kiện PC, màn hình và phụ kiện được chọn lọc cho học tập, làm việc và giải trí.</p></div><div class="catalog-count"><strong>{{ filteredProducts.length }}</strong><span>sản phẩm</span></div></div>
        <div class="row g-4">
            <aside class="col-lg-3"><div class="catalog-filter-card sticky-lg-top"><div class="fw-bold mb-3">Danh mục</div><button :class="['category-filter',{active:activeCategory==='all'}]" @click="activeCategory='all'"><i class="bi bi-grid"/><span>Tất cả sản phẩm</span><b>{{ products.length }}</b></button><button v-for="category in categories" :key="category.slug" :class="['category-filter',{active:activeCategory===category.slug}]" @click="activeCategory=category.slug"><i :class="['bi',category.icon]"/><span>{{ category.name }}</span><b>{{ category.count }}+</b></button></div></aside>
            <section class="col-lg-9"><div class="catalog-toolbar mb-4"><div class="search-box flex-grow-1"><i class="bi bi-search"/><input v-model="query" type="search" placeholder="Tìm laptop, SSD, card đồ họa..."/></div><select v-model="sort" class="form-select catalog-sort"><option value="featured">Nổi bật</option><option value="rating">Đánh giá cao</option><option value="price-low">Giá thấp đến cao</option><option value="price-high">Giá cao đến thấp</option></select></div>
                <div v-if="filteredProducts.length" class="row g-4"><div v-for="product in filteredProducts" :key="product.id" class="col-sm-6 col-xl-4"><article class="product-card-modern h-100"><div class="product-image-modern position-relative"><img :src="product.image" :alt="product.name" loading="lazy"/><span v-if="product.badge" class="product-badge">{{ product.badge }}</span><span class="product-stock">Còn {{ product.stock }}</span></div><div class="p-3 p-lg-4"><div class="product-brand">{{ product.brand }}</div><h3>{{ product.name }}</h3><div class="product-meta mb-2"><span><i class="bi bi-star-fill"/> {{ product.rating }}</span><span>Đã bán {{ product.sold }}</span></div><div><span class="product-price-modern">{{ formatPrice(product.price) }}</span><span v-if="product.oldPrice" class="product-old-modern ms-2">{{ formatPrice(product.oldPrice) }}</span></div><Link :href="`/products/${product.slug}`" class="btn btn-primary w-100 mt-3 product-action">Xem chi tiết <i class="bi bi-arrow-right ms-1"/></Link></div></article></div></div>
                <div v-else class="empty-catalog"><i class="bi bi-search"/><h3>Không tìm thấy sản phẩm</h3><p>Thử từ khóa khác hoặc chọn lại danh mục.</p></div>
            </section>
        </div>
    </div>
</template>
