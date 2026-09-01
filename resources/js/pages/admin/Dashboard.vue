<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const stats = [
    { label: 'Tổng đơn hàng', value: '1,248', icon: 'bi-receipt', note: '+12.5% tháng này' },
    { label: 'Tổng doanh thu', value: '2,84 tỷ', icon: 'bi-cash-stack', note: '+8.2% tháng này' },
    { label: 'Khách hàng', value: '8,642', icon: 'bi-people', note: '+5.4% tháng này' },
    { label: 'Sản phẩm', value: '526', icon: 'bi-box-seam', note: '18 sản phẩm sắp hết' },
];
const orderBars = [45, 62, 52, 78, 66, 88, 72, 92, 80, 96, 84, 100];
const months = ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'];
</script>

<template>
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div><div class="text-primary fw-bold small text-uppercase">Tổng quan</div><h1 class="h3 fw-bold mb-1">Dashboard</h1><p class="text-secondary mb-0">Theo dõi hoạt động kinh doanh của cửa hàng.</p></div>
        <Link href="/admin/products" class="btn btn-primary"><i class="bi bi-plus-lg me-2" /> Thêm sản phẩm</Link>
    </div>
    <div class="row g-3 mb-4">
        <div v-for="stat in stats" :key="stat.label" class="col-12 col-sm-6 col-xl-3"><div class="stat-card p-4 h-100"><div class="d-flex justify-content-between align-items-start"><div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3"><i :class="['bi', stat.icon, 'fs-5']" /></div><span class="badge text-bg-light">{{ stat.note }}</span></div><div class="text-secondary small mt-3">{{ stat.label }}</div><div class="fs-3 fw-bold mt-1">{{ stat.value }}</div></div></div>
    </div>
    <div class="row g-4">
        <div class="col-xl-8"><div class="dashboard-panel p-4"><div class="d-flex justify-content-between align-items-center mb-4"><div><h5 class="fw-bold mb-1">Đơn hàng</h5><div class="small text-secondary">Số lượng đơn theo tháng</div></div><button class="btn btn-sm btn-outline-secondary">12 tháng <i class="bi bi-chevron-down" /></button></div><div class="d-flex align-items-end gap-2" style="height: 260px"><div v-for="(bar, index) in orderBars" :key="months[index]" class="flex-fill d-flex flex-column justify-content-end align-items-center h-100"><div class="w-100 rounded-top bg-primary" :style="{ height: `${bar}%`, opacity: 0.45 + bar / 250 }" /><span class="small text-secondary mt-2">{{ months[index] }}</span></div></div></div></div>
        <div class="col-xl-4"><div class="dashboard-panel p-4 h-100"><div class="d-flex justify-content-between mb-4"><div><h5 class="fw-bold mb-1">Đơn hàng gần đây</h5><div class="small text-secondary">Cần xử lý</div></div><span class="badge text-bg-warning">8 mới</span></div><div class="d-grid gap-3"><div v-for="order in ['#TS-1048', '#TS-1047', '#TS-1046', '#TS-1045']" :key="order" class="d-flex justify-content-between align-items-center border-bottom pb-3"><div><div class="fw-semibold">{{ order }}</div><div class="small text-secondary">Khách hàng</div></div><span class="badge rounded-pill text-bg-light">Chờ xử lý</span></div></div><Link href="/admin/orders" class="btn btn-outline-primary w-100 mt-4">Xem tất cả đơn hàng</Link></div></div>
        <div class="col-12"><div class="dashboard-panel p-4"><h5 class="fw-bold mb-1">Doanh thu theo tháng</h5><div class="small text-secondary mb-4">Biểu đồ nền tảng, sẽ kết nối dữ liệu MySQL ở các giai đoạn tiếp theo.</div><div class="row row-cols-4 row-cols-md-6 row-cols-xl-12 g-2" style="height: 180px"><div v-for="(bar, index) in orderBars" :key="months[index]" class="col text-center h-100 d-flex flex-column justify-content-end"><div class="rounded-top bg-dark mx-1" :style="{ height: `${bar * .75}%`, opacity: .2 + bar / 150 }" /><div class="small text-secondary mt-2">{{ months[index] }}</div></div></div></div></div>
    </div>
</template>
