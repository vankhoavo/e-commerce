<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { categories, formatPrice, products } from '@/data/products';

type Props = { category?: string; search?: string };
const props = defineProps<Props>();
const query = ref(props.search ?? '');
const activeCategory = ref(props.category || 'all');
const sort = ref('featured');

const activeCategoryData = computed(() => categories.find((category) => category.slug === activeCategory.value));
const pageTitle = computed(() => activeCategoryData.value?.name ?? 'Khám phá sản phẩm');
const pageDescription = computed(() => activeCategoryData.value
    ? `Khám phá ${activeCategoryData.value.name.toLowerCase()} chính hãng, chọn lọc kỹ cho học tập, làm việc và giải trí.`
    : 'Laptop, linh kiện PC, màn hình và phụ kiện chính hãng — lựa chọn dễ dàng, giá tốt và giao hàng tận nơi.');

const filteredProducts = computed(() => {
    let result = products.filter((p) => activeCategory.value === 'all' || p.categorySlug === activeCategory.value);
    const q = query.value.trim().toLowerCase();
    if (q) result = result.filter((p) => `${p.name} ${p.brand} ${p.category}`.toLowerCase().includes(q));
    if (sort.value === 'price-low') result = [...result].sort((a, b) => a.price - b.price);
    if (sort.value === 'price-high') result = [...result].sort((a, b) => b.price - a.price);
    if (sort.value === 'rating') result = [...result].sort((a, b) => b.rating - a.rating);
    return result;
});

function addToCart(product: any) {
    const cart = JSON.parse(localStorage.getItem('techstore_cart') ?? '[]');
    const existing = cart.find((item: any) => item.id === product.id);
    if (existing) existing.quantity += 1;
    else cart.push({ id: product.id, name: product.name, price: product.price, image: product.image, quantity: 1 });
    localStorage.setItem('techstore_cart', JSON.stringify(cart));
    window.dispatchEvent(new Event('techstore-cart-updated'));
}
</script>

<template>
    <Head :title="pageTitle" />
    <div class="catalog-page">
        <div class="shop-breadcrumb mb-3"><Link href="/">Trang chủ</Link><i class="bi bi-chevron-right" /><span>{{ pageTitle }}</span></div>

        <section class="catalog-hero mb-4">
            <div>
                <span class="section-kicker">TECHSTORE · CỬA HÀNG CÔNG NGHỆ</span>
                <h1>{{ pageTitle }}</h1>
                <p>{{ pageDescription }}</p>
                <div class="catalog-category-pills mt-4">
                    <Link href="/products" :class="['catalog-pill', { active: activeCategory === 'all' }]">Tất cả</Link>
                    <Link v-for="category in categories" :key="category.slug" :href="`/products?category=${category.slug}`" :class="['catalog-pill', { active: activeCategory === category.slug }]">
                        <i :class="['bi', category.icon]" /> {{ category.name }}
                    </Link>
                </div>
            </div>
            <div class="catalog-count"><strong>{{ filteredProducts.length }}</strong><span>sản phẩm</span></div>
        </section>

        <div class="row g-4">
            <aside class="col-lg-3">
                <div class="catalog-filter-card sticky-lg-top">
                    <div class="catalog-filter-heading"><span>Bộ lọc sản phẩm</span><small>{{ filteredProducts.length }} kết quả</small></div>
                    <div class="catalog-filter-label">Danh mục</div>
                    <Link href="/products" :class="['category-filter', { active: activeCategory === 'all' }]">
                        <i class="bi bi-grid-3x3-gap" /><span>Tất cả sản phẩm</span><b>{{ products.length }}</b>
                    </Link>
                    <Link v-for="category in categories" :key="category.slug" :href="`/products?category=${category.slug}`" :class="['category-filter', { active: activeCategory === category.slug }]">
                        <i :class="['bi', category.icon]" /><span>{{ category.name }}</span><b>{{ category.count }}+</b>
                    </Link>
                    <div class="catalog-filter-tip"><i class="bi bi-lightning-charge-fill" /><span>Mẹo: dùng ô tìm kiếm để tìm nhanh theo tên, hãng hoặc danh mục.</span></div>
                </div>
            </aside>

            <section class="col-lg-9">
                <div class="catalog-toolbar mb-4">
                    <div class="search-box flex-grow-1"><i class="bi bi-search" /><input v-model="query" type="search" placeholder="Tìm laptop, SSD, RAM, màn hình..." /></div>
                    <select v-model="sort" class="form-select catalog-sort">
                        <option value="featured">Sắp xếp: Nổi bật</option>
                        <option value="rating">Đánh giá cao</option>
                        <option value="price-low">Giá thấp đến cao</option>
                        <option value="price-high">Giá cao đến thấp</option>
                    </select>
                </div>

                <div v-if="filteredProducts.length" class="row g-3 g-xl-4">
                    <div v-for="product in filteredProducts" :key="product.id" class="col-sm-6 col-xl-4">
                        <article class="catalog-product-card">
                            <div class="catalog-product-image">
                                <img :src="product.image" :alt="product.name" loading="lazy" />
                                <span v-if="product.badge" class="catalog-product-badge">{{ product.badge }}</span>
                                <span class="catalog-stock">Còn {{ product.stock }}</span>
                            </div>
                            <div class="catalog-product-body">
                                <div class="catalog-product-brand">{{ product.brand }}</div>
                                <h2 class="catalog-product-title">{{ product.name }}</h2>
                                <div class="catalog-product-meta"><span><i class="bi bi-star-fill" /> {{ product.rating }}</span><span>Đã bán {{ product.sold }}</span></div>
                                <div><span class="catalog-product-price">{{ formatPrice(product.price) }}</span><span v-if="product.oldPrice" class="catalog-product-old">{{ formatPrice(product.oldPrice) }}</span></div>
                                <div class="catalog-product-actions">
                                    <Link :href="`/products/${product.slug}`" class="btn btn-outline-primary flex-grow-1">Xem chi tiết <i class="bi bi-arrow-right ms-1" /></Link>
                                    <button type="button" class="btn btn-primary catalog-cart-btn" title="Thêm vào giỏ hàng" @click="addToCart(product)"><i class="bi bi-cart-plus" /></button>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
                <div v-else class="empty-catalog"><i class="bi bi-search" /><h3>Không tìm thấy sản phẩm</h3><p>Thử từ khóa khác hoặc chọn lại danh mục.</p><Link href="/products" class="btn btn-primary mt-3">Xem tất cả sản phẩm</Link></div>
            </section>
        </div>
    </div>
</template>

<style scoped>
.catalog-category-pills{display:flex;flex-wrap:wrap;gap:7px}.catalog-pill{display:inline-flex;align-items:center;gap:6px;padding:7px 11px;border:1px solid #e1e7f0;border-radius:999px;color:#667085;background:#fff;font-size:.68rem;font-weight:800;transition:.18s ease}.catalog-pill:hover{border-color:#b9cdf3;color:#2563eb;background:#f8fbff}.catalog-pill.active{border-color:#b8cffd;color:#2563eb;background:#edf4ff}.catalog-filter-heading{display:flex;align-items:end;justify-content:space-between;padding:2px 6px 14px;border-bottom:1px solid #edf0f4}.catalog-filter-heading span{color:#101828;font-size:.9rem;font-weight:850}.catalog-filter-heading small{color:#98a2b3;font-size:.62rem;font-weight:700}.catalog-filter-label{margin:15px 6px 6px;color:#98a2b3;font-size:.62rem;font-weight:850;text-transform:uppercase;letter-spacing:.1em}.catalog-filter-tip{display:flex;gap:7px;margin:14px 2px 2px;padding:10px;border-radius:11px;color:#667085;background:#f8fafc;font-size:.62rem;line-height:1.45}.catalog-filter-tip i{flex:0 0 auto;color:#f59e0b}.catalog-product-card{height:100%;overflow:hidden;border:1px solid #e5e9f0;border-radius:18px;background:#fff;box-shadow:0 7px 25px rgba(16,24,40,.045);transition:transform .22s ease,box-shadow .22s ease,border-color .22s ease}.catalog-product-card:hover{transform:translateY(-4px);border-color:#cbd9f3;box-shadow:0 17px 38px rgba(16,24,40,.1)}.catalog-product-image{position:relative;aspect-ratio:1.05;display:grid;place-items:center;overflow:hidden;background:#f5f7fa}.catalog-product-image img{width:100%;height:100%;object-fit:cover;transition:transform .3s ease}.catalog-product-card:hover .catalog-product-image img{transform:scale(1.045)}.catalog-product-badge{position:absolute;top:10px;left:10px;padding:5px 8px;border-radius:7px;color:#fff;background:#101828;font-size:.62rem;font-weight:800}.catalog-stock{position:absolute;right:10px;bottom:10px;padding:5px 8px;border:1px solid rgba(255,255,255,.8);border-radius:8px;color:#166534;background:rgba(240,253,244,.95);font-size:.61rem;font-weight:800}.catalog-product-body{padding:14px}.catalog-product-brand{margin-bottom:5px;color:#98a2b3;font-size:.61rem;font-weight:850;letter-spacing:.12em}.catalog-product-title{height:44px;margin:0 0 7px;color:#172033;font-size:.9rem;font-weight:800;line-height:1.4}.catalog-product-meta{display:flex;justify-content:space-between;color:#98a2b3;font-size:.65rem}.catalog-product-meta i{color:#f59e0b}.catalog-product-price{margin-top:8px;color:#dc2626;font-size:1rem;font-weight:900}.catalog-product-old{margin-left:6px;color:#98a2b3;font-size:.67rem;text-decoration:line-through}.catalog-product-actions{display:flex;gap:7px;margin-top:12px}.catalog-product-actions .btn{height:37px;border-radius:9px;font-size:.7rem;font-weight:800}.catalog-cart-btn{width:40px;flex:0 0 40px!important;padding:0!important}
@media(max-width:991.98px){.catalog-hero{padding:24px}.catalog-count{width:90px;height:90px;flex-basis:90px}.catalog-filter-card{position:static!important}.catalog-toolbar{flex-wrap:wrap}.catalog-toolbar .search-box{flex:1 1 100%}.catalog-sort{width:100%}}@media(max-width:767.98px){.catalog-page{padding:18px 12px 45px}.catalog-hero{align-items:flex-start;flex-direction:column;padding:20px}.catalog-count{width:82px;height:82px}.catalog-count strong{font-size:1.55rem}.catalog-product-title{height:auto;min-height:44px}}
</style>
