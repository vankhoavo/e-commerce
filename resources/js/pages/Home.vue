<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const categories = [
    { name: 'Laptop', icon: 'bi-laptop', count: '120+ sản phẩm', tone: 'primary' },
    { name: 'Linh kiện PC', icon: 'bi-pc-display', count: '350+ sản phẩm', tone: 'purple' },
    { name: 'Màn hình', icon: 'bi-display', count: '80+ sản phẩm', tone: 'cyan' },
    { name: 'Phụ kiện', icon: 'bi-keyboard', count: '200+ sản phẩm', tone: 'orange' },
];

const products = [
    { name: 'Laptop Gaming Pro 16', price: '29.990.000đ', oldPrice: '32.990.000đ', image: 'https://placehold.co/800x600/e9eef7/172033?text=Laptop+Gaming', badge: 'Giảm 9%', rating: '4.9', sold: '120' },
    { name: 'Laptop Văn phòng 14', price: '16.490.000đ', oldPrice: '18.990.000đ', image: 'https://placehold.co/800x600/e8f4fb/172033?text=Laptop+Office', badge: 'Bán chạy', rating: '4.8', sold: '96' },
    { name: 'RTX Graphics 12GB', price: '12.990.000đ', oldPrice: '', image: 'https://placehold.co/800x600/f0ebfa/172033?text=GPU', badge: 'Mới', rating: '4.9', sold: '58' },
    { name: 'SSD NVMe 1TB Gen4', price: '1.890.000đ', oldPrice: '2.190.000đ', image: 'https://placehold.co/800x600/e8f6ee/172033?text=SSD+NVMe', badge: 'Giảm 14%', rating: '4.7', sold: '184' },
];
</script>

<template>
    <section class="shop-hero">
        <div class="shop-hero-grid" />
        <div class="container position-relative py-5">
            <div class="row align-items-center g-5 py-lg-4">
                <div class="col-lg-7">
                    <div class="hero-eyebrow mb-4"><span class="pulse-dot" /> TECHSTORE — CÔNG NGHỆ CHO MỌI HÀNH TRÌNH</div>
                    <h1 class="hero-title mb-4">Nâng cấp công nghệ.<br /><span>Khơi nguồn trải nghiệm.</span></h1>
                    <p class="hero-copy mb-4">Laptop, linh kiện PC và phụ kiện chính hãng — lựa chọn kỹ, giá tốt, giao hàng tận nơi.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <Link href="/products" class="btn btn-primary btn-lg hero-button">Khám phá sản phẩm <i class="bi bi-arrow-right ms-2" /></Link>
                        <Link href="/products?category=laptop" class="btn btn-light btn-lg hero-secondary">Xem Laptop</Link>
                    </div>
                    <div class="hero-trust mt-4 d-flex flex-wrap gap-4">
                        <span><i class="bi bi-shield-check" /> Chính hãng</span>
                        <span><i class="bi bi-truck" /> Giao nhanh</span>
                        <span><i class="bi bi-arrow-repeat" /> Đổi trả dễ dàng</span>
                    </div>
                </div>
                <div class="col-lg-5 d-none d-lg-block">
                    <div class="hero-offer-card">
                        <div class="offer-top"><span>ƯU ĐÃI HÔM NAY</span><i class="bi bi-lightning-charge-fill" /></div>
                        <div class="offer-percent">30<span>%</span></div>
                        <div class="offer-title">Giảm đến 30% cho sản phẩm nổi bật</div>
                        <div class="offer-note">Số lượng có hạn · Áp dụng theo từng sản phẩm</div>
                        <div class="offer-progress"><span style="width: 72%" /></div>
                        <div class="d-flex justify-content-between small text-white-50 mt-2"><span>Đã sử dụng</span><strong class="text-white">72%</strong></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="container py-5">
        <div class="section-heading mb-4">
            <div><span class="section-kicker">DANH MỤC</span><h2>Khám phá theo nhu cầu</h2><p>Chọn nhanh nhóm sản phẩm bạn đang tìm kiếm.</p></div>
            <Link href="/products" class="section-link">Tất cả danh mục <i class="bi bi-arrow-right" /></Link>
        </div>
        <div class="row g-3 g-lg-4">
            <div v-for="category in categories" :key="category.name" class="col-6 col-lg-3">
                <Link href="/products" class="category-card-modern d-flex align-items-center gap-3 p-3 p-lg-4 h-100">
                    <span :class="['category-icon', `category-${category.tone}`]"><i :class="['bi', category.icon]" /></span>
                    <span><strong>{{ category.name }}</strong><small>{{ category.count }}</small></span><i class="bi bi-chevron-right category-arrow ms-auto" />
                </Link>
            </div>
        </div>
    </section>

    <section class="container pb-5">
        <div class="section-heading mb-4">
            <div><span class="section-kicker">ĐƯỢC QUAN TÂM</span><h2>Sản phẩm nổi bật</h2><p>Những lựa chọn được khách hàng TechStore yêu thích.</p></div>
            <Link href="/products" class="section-link">Xem tất cả <i class="bi bi-arrow-right" /></Link>
        </div>
        <div class="row g-4">
            <div v-for="product in products" :key="product.name" class="col-12 col-sm-6 col-lg-3">
                <article class="product-card-modern h-100">
                    <div class="product-image-modern position-relative"><img :src="product.image" :alt="product.name" loading="lazy" /><span class="product-badge">{{ product.badge }}</span><button class="wishlist-btn" type="button" aria-label="Yêu thích"><i class="bi bi-heart" /></button></div>
                    <div class="p-3 p-lg-4">
                        <div class="product-brand">TECHSTORE</div><h3>{{ product.name }}</h3>
                        <div class="product-meta mb-2"><span><i class="bi bi-star-fill" /> {{ product.rating }}</span><span>Đã bán {{ product.sold }}</span></div>
                        <div class="d-flex align-items-baseline gap-2"><span class="product-price-modern">{{ product.price }}</span><span v-if="product.oldPrice" class="product-old-modern">{{ product.oldPrice }}</span></div>
                        <Link href="/products" class="btn btn-outline-primary w-100 mt-3 product-action">Xem sản phẩm <i class="bi bi-arrow-right ms-1" /></Link>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="container pb-5">
        <div class="service-strip row g-0">
            <div class="col-md-4 service-item"><span><i class="bi bi-patch-check-fill" /></span><div><strong>Hàng chính hãng</strong><small>Nguồn hàng rõ ràng, bảo hành đầy đủ.</small></div></div>
            <div class="col-md-4 service-item"><span><i class="bi bi-truck-front-fill" /></span><div><strong>Giao hàng toàn quốc</strong><small>Đóng gói cẩn thận, theo dõi đơn dễ dàng.</small></div></div>
            <div class="col-md-4 service-item"><span><i class="bi bi-headset" /></span><div><strong>Hỗ trợ tận tâm</strong><small>Tư vấn trước và sau khi mua hàng.</small></div></div>
        </div>
    </section>
</template>
