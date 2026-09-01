<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const categories = [
    { name: 'Laptop', icon: 'bi-laptop', count: '120+ sản phẩm' },
    { name: 'Linh kiện PC', icon: 'bi-pc-display', count: '350+ sản phẩm' },
    { name: 'Màn hình', icon: 'bi-display', count: '80+ sản phẩm' },
    { name: 'Phụ kiện', icon: 'bi-keyboard', count: '200+ sản phẩm' },
];

const products = [
    { name: 'Laptop Gaming Pro 16', price: '29.990.000đ', oldPrice: '32.990.000đ', image: 'https://placehold.co/800x600/e2e8f0/0f172a?text=Laptop+Gaming', badge: 'Giảm 9%' },
    { name: 'Laptop Văn phòng 14', price: '16.490.000đ', oldPrice: '18.990.000đ', image: 'https://placehold.co/800x600/e0f2fe/0f172a?text=Laptop+Office', badge: 'Bán chạy' },
    { name: 'RTX Graphics 12GB', price: '12.990.000đ', oldPrice: '', image: 'https://placehold.co/800x600/ede9fe/0f172a?text=GPU', badge: 'Mới' },
    { name: 'SSD NVMe 1TB Gen4', price: '1.890.000đ', oldPrice: '2.190.000đ', image: 'https://placehold.co/800x600/dcfce7/0f172a?text=SSD+NVMe', badge: 'Giảm 14%' },
];
</script>

<template>
    <section class="shop-hero text-white">
        <div class="container shop-hero-content py-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <span class="badge rounded-pill text-bg-light text-primary px-3 py-2 mb-3">TECHSTORE • CÔNG NGHỆ MỖI NGÀY</span>
                    <h1 class="display-4 fw-bold mb-3">Nâng cấp công nghệ.<br />Nâng tầm trải nghiệm.</h1>
                    <p class="lead text-white-50 mb-4">Laptop, linh kiện và phụ kiện chính hãng với mức giá cạnh tranh.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <Link href="/products" class="btn btn-primary btn-lg px-4">Khám phá sản phẩm <i class="bi bi-arrow-right ms-2" /></Link>
                        <Link href="/products?category=laptop" class="btn btn-outline-light btn-lg px-4">Xem Laptop</Link>
                    </div>
                </div>
                <div class="col-lg-5 d-none d-lg-block">
                    <div class="bg-white bg-opacity-10 rounded-4 p-4 border border-white border-opacity-25">
                        <div class="d-flex justify-content-between mb-3"><span>Ưu đãi hôm nay</span><i class="bi bi-lightning-charge-fill text-warning fs-4" /></div>
                        <div class="display-6 fw-bold">-30%</div>
                        <div class="text-white-50 mb-3">Cho một số sản phẩm công nghệ nổi bật</div>
                        <div class="progress" role="progressbar" aria-label="Ưu đãi" aria-valuenow="72" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar bg-warning" style="width: 72%" /></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="container py-5">
        <div class="mb-4"><div class="text-primary fw-bold small text-uppercase">Khám phá</div><h2 class="fw-bold mb-1">Danh mục nổi bật</h2><p class="text-secondary mb-0">Chọn nhanh nhóm sản phẩm bạn cần.</p></div>
        <div class="row g-3">
            <div v-for="category in categories" :key="category.name" class="col-6 col-lg-3">
                <Link href="/products" class="category-card d-block p-4 h-100">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-inline-flex p-3 mb-3"><i :class="['bi', category.icon, 'fs-3']" /></div>
                    <h5 class="fw-bold mb-1">{{ category.name }}</h5>
                    <div class="small text-secondary">{{ category.count }}</div>
                </Link>
            </div>
        </div>
    </section>

    <section class="container pb-5">
        <div class="d-flex justify-content-between align-items-end mb-4"><div><div class="text-primary fw-bold small text-uppercase">Gợi ý cho bạn</div><h2 class="fw-bold mb-1">Sản phẩm nổi bật</h2></div><Link href="/products" class="btn btn-link text-primary fw-semibold">Xem tất cả <i class="bi bi-arrow-right" /></Link></div>
        <div class="row g-4">
            <div v-for="product in products" :key="product.name" class="col-12 col-sm-6 col-lg-3">
                <article class="product-card h-100 overflow-hidden">
                    <div class="position-relative product-image"><img :src="product.image" :alt="product.name" loading="lazy" /><span class="position-absolute top-0 start-0 m-3 badge text-bg-dark">{{ product.badge }}</span></div>
                    <div class="p-3"><div class="small text-secondary mb-1">TechStore</div><h6 class="fw-bold mb-2">{{ product.name }}</h6><div class="d-flex align-items-baseline gap-2"><span class="product-price">{{ product.price }}</span><span v-if="product.oldPrice" class="product-old-price">{{ product.oldPrice }}</span></div><Link href="/products" class="btn btn-outline-primary w-100 mt-3">Xem chi tiết</Link></div>
                </article>
            </div>
        </div>
    </section>

    <section class="container pb-5">
        <div class="row g-3">
            <div class="col-md-4"><div class="bg-white border rounded-4 p-4 h-100"><i class="bi bi-shield-check text-success fs-2" /><h5 class="fw-bold mt-3">Sản phẩm chính hãng</h5><p class="text-secondary mb-0">Nguồn hàng rõ ràng, thông tin minh bạch.</p></div></div>
            <div class="col-md-4"><div class="bg-white border rounded-4 p-4 h-100"><i class="bi bi-truck text-primary fs-2" /><h5 class="fw-bold mt-3">Giao hàng toàn quốc</h5><p class="text-secondary mb-0">Phí vận chuyển được tính rõ ràng khi thanh toán.</p></div></div>
            <div class="col-md-4"><div class="bg-white border rounded-4 p-4 h-100"><i class="bi bi-headset text-warning fs-2" /><h5 class="fw-bold mt-3">Hỗ trợ tận tâm</h5><p class="text-secondary mb-0">Tư vấn trước và sau khi mua hàng.</p></div></div>
        </div>
    </section>
</template>
