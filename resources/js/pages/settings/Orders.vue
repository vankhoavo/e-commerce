<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { formatPrice } from '@/data/products';
import { readOrderHistory, syncLastOrderToHistory, updateOrderStatus, type OrderStatus, type TechStoreOrder } from '@/lib/orders';

const page = usePage();
const userId = computed<number | null>(() => {
    const id = (page.props as any).auth?.user?.id;
    return id ? Number(id) : null;
});
const orders = ref<TechStoreOrder[]>([]);
const selectedOrder = ref<TechStoreOrder | null>(null);
const filter = ref<'all' | OrderStatus>('all');

const statusOptions: Array<{ key: 'all' | OrderStatus; label: string; icon: string }> = [
    { key: 'all', label: 'Tất cả', icon: 'bi-grid' },
    { key: 'Chờ xử lý', label: 'Chờ xử lý', icon: 'bi-hourglass-split' },
    { key: 'Đang giao', label: 'Đang giao', icon: 'bi-truck' },
    { key: 'Đã giao', label: 'Đã giao', icon: 'bi-check2-circle' },
    { key: 'Trả hàng', label: 'Trả hàng', icon: 'bi-arrow-return-left' },
    { key: 'Hủy hàng', label: 'Hủy hàng', icon: 'bi-x-circle' },
];

const statusMeta: Record<OrderStatus, { icon: string; className: string; description: string }> = {
    'Chờ xử lý': { icon: 'bi-hourglass-split', className: 'is-pending', description: 'TechStore đang tiếp nhận đơn hàng.' },
    'Đang giao': { icon: 'bi-truck', className: 'is-shipping', description: 'Đơn hàng đang được vận chuyển.' },
    'Đã giao': { icon: 'bi-check-circle-fill', className: 'is-delivered', description: 'Đơn hàng đã giao thành công.' },
    'Trả hàng': { icon: 'bi-arrow-return-left', className: 'is-returned', description: 'Đơn hàng đang ở trạng thái trả hàng.' },
    'Hủy hàng': { icon: 'bi-x-circle-fill', className: 'is-cancelled', description: 'Đơn hàng đã được hủy.' },
};

const filteredOrders = computed(() => filter.value === 'all' ? orders.value : orders.value.filter((order) => order.status === filter.value));
const totalSpent = computed(() => orders.value.filter((order) => order.status !== 'Hủy hàng').reduce((sum, order) => sum + order.total, 0));
const deliveredCount = computed(() => orders.value.filter((order) => order.status === 'Đã giao').length);
const activeCount = computed(() => orders.value.filter((order) => ['Chờ xử lý', 'Đang giao'].includes(order.status)).length);

function loadOrders() {
    if (!userId.value) return;
    syncLastOrderToHistory(userId.value);
    orders.value = readOrderHistory(userId.value);
}

function formatDate(value: string) {
    return new Intl.DateTimeFormat('vi-VN', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}

function paymentLabel(order: TechStoreOrder) {
    if (order.payment === 'paypal-sandbox') return 'PayPal Sandbox';
    if (order.payment === 'paypal-demo') return 'PayPal mô phỏng';
    return 'Thanh toán khi nhận hàng';
}

function openInvoice(order: TechStoreOrder) {
    selectedOrder.value = order;
}

function closeInvoice() {
    selectedOrder.value = null;
}

function cancelOrder(order: TechStoreOrder) {
    if (!userId.value || order.status !== 'Chờ xử lý') return;
    if (!window.confirm(`Bạn có chắc muốn hủy đơn ${order.code}?`)) return;
    orders.value = updateOrderStatus(userId.value, order.code, 'Hủy hàng');
    if (selectedOrder.value?.code === order.code) selectedOrder.value = orders.value.find((item) => item.code === order.code) ?? null;
}

function requestReturn(order: TechStoreOrder) {
    if (!userId.value || order.status !== 'Đã giao') return;
    if (!window.confirm(`Gửi yêu cầu trả hàng cho đơn ${order.code}?`)) return;
    orders.value = updateOrderStatus(userId.value, order.code, 'Trả hàng');
}

function printInvoice() {
    if (selectedOrder.value) window.print();
}

onMounted(loadOrders);
</script>

<template>
    <Head title="Đơn hàng" />
    <div class="order-history-page">
        <section class="orders-dashboard">
            <header class="orders-page-header">
                <div class="orders-title-wrap">
                    <span class="orders-title-icon"><i class="bi bi-bag-check" /></span>
                    <div><span class="orders-kicker">TECHSTORE · TÀI KHOẢN</span><h2>Đơn hàng</h2><p>Lịch sử mua hàng, trạng thái giao nhận và hóa đơn bán hàng.</p></div>
                </div>
                <div class="orders-summary-badge"><i class="bi bi-receipt"/><strong>{{ orders.length }}</strong><span>đơn hàng</span></div>
            </header>

            <div class="orders-stat-grid">
                <article class="orders-stat-card"><span class="stat-icon blue"><i class="bi bi-receipt-cutoff"/></span><div><small>Tổng đơn hàng</small><strong>{{ orders.length }}</strong><span>Tất cả đơn đã tạo</span></div></article>
                <article class="orders-stat-card"><span class="stat-icon orange"><i class="bi bi-truck"/></span><div><small>Đang xử lý</small><strong>{{ activeCount }}</strong><span>Chờ xử lý hoặc đang giao</span></div></article>
                <article class="orders-stat-card"><span class="stat-icon green"><i class="bi bi-check2-circle"/></span><div><small>Đã giao</small><strong>{{ deliveredCount }}</strong><span>Giao thành công</span></div></article>
                <article class="orders-stat-card"><span class="stat-icon purple"><i class="bi bi-cash-stack"/></span><div><small>Giá trị mua hàng</small><strong>{{ formatPrice(totalSpent) }}</strong><span>Không tính đơn đã hủy</span></div></article>
            </div>

            <section class="orders-history-panel">
                <div class="history-panel-head"><div><h3>Lịch sử mua hàng</h3><p>Chọn trạng thái để xem nhanh các đơn phù hợp.</p></div><span><i class="bi bi-shield-check"/> Theo tài khoản</span></div>
                <div class="order-status-tabs">
                    <button v-for="item in statusOptions" :key="item.key" type="button" :class="{ active: filter === item.key }" @click="filter = item.key"><i :class="['bi', item.icon]"/> {{ item.label }}<b v-if="item.key !== 'all'">{{ orders.filter((order) => order.status === item.key).length }}</b></button>
                </div>

                <div v-if="filteredOrders.length" class="orders-list">
                    <article v-for="order in filteredOrders" :key="order.code" class="order-history-card">
                        <div class="order-history-head">
                            <div class="order-heading-left"><div class="order-receipt-icon"><i class="bi bi-receipt"/></div><div><span class="order-code">#{{ order.code }}</span><small>{{ formatDate(order.createdAt) }}</small></div></div>
                            <span class="order-status" :class="statusMeta[order.status].className"><i :class="['bi', statusMeta[order.status].icon]"/>{{ order.status }}</span>
                        </div>
                        <div class="order-history-body">
                            <div class="order-product-preview">
                                <div v-for="item in order.items.slice(0, 4)" :key="`${order.code}-${item.id}`" class="order-thumb"><img :src="item.image" :alt="item.name"/><span v-if="item.quantity > 1">×{{ item.quantity }}</span></div>
                                <div v-if="order.items.length > 4" class="order-more">+{{ order.items.length - 4 }}</div>
                            </div>
                            <div class="order-history-summary"><span>{{ order.items.reduce((sum, item) => sum + item.quantity, 0) }} sản phẩm · {{ paymentLabel(order) }}</span><strong>{{ formatPrice(order.total) }}</strong><small>{{ statusMeta[order.status].description }}</small></div>
                        </div>
                        <div class="order-history-foot"><div class="invoice-meta"><i class="bi bi-file-earmark-text"/><span>Hóa đơn bán hàng <small>Đầy đủ thông tin đơn</small></span></div><div class="order-actions"><button type="button" class="invoice-btn" @click="openInvoice(order)"><i class="bi bi-receipt-cutoff"/> Xem hóa đơn</button><button v-if="order.status === 'Chờ xử lý'" type="button" class="danger-outline-btn" @click="cancelOrder(order)"><i class="bi bi-x-lg"/> Hủy hàng</button><button v-if="order.status === 'Đã giao'" type="button" class="return-outline-btn" @click="requestReturn(order)"><i class="bi bi-arrow-return-left"/> Trả hàng</button></div></div>
                    </article>
                </div>

                <div v-else class="orders-empty"><div class="orders-empty-icon"><i class="bi bi-bag-x"/></div><h3>{{ filter === 'all' ? 'Chưa có lịch sử mua hàng' : 'Không có đơn hàng phù hợp' }}</h3><p>{{ filter === 'all' ? 'Đơn hàng sau khi đặt sẽ xuất hiện tại đây cùng hóa đơn bán hàng.' : 'Hãy chọn trạng thái khác để xem đơn hàng.' }}</p></div>
            </section>
        </section>

        <div v-if="selectedOrder" class="invoice-overlay" @click.self="closeInvoice">
            <section class="invoice-modal" role="dialog" aria-modal="true" aria-labelledby="invoice-title">
                <div class="invoice-toolbar no-print"><div><span>TECHSTORE</span><strong>HÓA ĐƠN BÁN HÀNG</strong></div><button type="button" aria-label="Đóng" @click="closeInvoice"><i class="bi bi-x-lg"/></button></div>
                <div class="invoice-sheet">
                    <header class="invoice-header"><div><span class="invoice-brand">TECHSTORE</span><h1>Hóa đơn bán hàng</h1><p>Mã đơn: <strong>{{ selectedOrder.code }}</strong></p></div><div class="invoice-date"><span>Ngày đặt</span><strong>{{ formatDate(selectedOrder.createdAt) }}</strong></div></header>
                    <div class="invoice-info-grid"><div><small>Thông tin người nhận</small><strong>{{ selectedOrder.customer?.name || 'Khách hàng TechStore' }}</strong><span>{{ selectedOrder.customer?.phone || '—' }}</span><span>{{ selectedOrder.customer?.email || '—' }}</span></div><div><small>Địa chỉ giao hàng</small><strong>{{ selectedOrder.customer?.address || '—' }}</strong><span>Thanh toán: {{ paymentLabel(selectedOrder) }}</span></div></div>
                    <div class="invoice-table-wrap"><table class="invoice-table"><thead><tr><th>Sản phẩm</th><th>SL</th><th>Đơn giá</th><th>Thành tiền</th></tr></thead><tbody><tr v-for="item in selectedOrder.items" :key="`${selectedOrder.code}-invoice-${item.id}`"><td>{{ item.name }}</td><td>{{ item.quantity }}</td><td>{{ formatPrice(item.price) }}</td><td>{{ formatPrice(item.price * item.quantity) }}</td></tr></tbody></table></div>
                    <div class="invoice-total"><div><span>Tạm tính</span><strong>{{ formatPrice(selectedOrder.subtotal) }}</strong></div><div><span>Phí vận chuyển</span><strong>{{ formatPrice(selectedOrder.totalShipping) }}</strong></div><div class="grand"><span>Tổng thanh toán</span><strong>{{ formatPrice(selectedOrder.total) }}</strong></div></div>
                    <div class="invoice-status"><span>Trạng thái đơn hàng</span><strong :class="statusMeta[selectedOrder.status].className">{{ selectedOrder.status }}</strong></div>
                    <div class="invoice-payment-note" v-if="selectedOrder.payment === 'paypal-sandbox'"><i class="bi bi-shield-check"/><span>Thanh toán được xác nhận qua <strong>PayPal Sandbox</strong>. Đây là môi trường thử nghiệm, không chuyển tiền thật.</span></div>
                    <p class="invoice-note">Cảm ơn bạn đã mua sắm tại TechStore. Hóa đơn này được tạo từ dữ liệu đơn hàng của tài khoản.</p>
                </div>
                <div class="invoice-actions no-print"><button type="button" class="secondary-invoice-btn" @click="closeInvoice">Đóng</button><button type="button" class="primary-invoice-btn" @click="printInvoice"><i class="bi bi-printer"/> In hóa đơn</button></div>
            </section>
        </div>
    </div>
</template>

<style>
.order-history-page{width:100%;color:#101828}.orders-dashboard{display:grid;gap:18px}.orders-page-header{display:flex;align-items:center;justify-content:space-between;gap:18px}.orders-title-wrap{display:flex;align-items:center;gap:14px}.orders-title-icon{display:grid;width:48px;height:48px;flex:0 0 48px;place-items:center;border:1px solid #dbeafe;border-radius:15px;color:#2563eb;background:linear-gradient(145deg,#eff6ff,#f5f3ff);font-size:1.1rem}.orders-kicker{display:block;margin-bottom:3px;color:#2563eb;font-size:.57rem;font-weight:950;letter-spacing:.15em}.orders-page-header h2{margin:0;color:#101828;font-size:1.65rem;font-weight:900;letter-spacing:-.035em}.orders-page-header p{margin:5px 0 0;color:#667085;font-size:.72rem}.orders-summary-badge{display:flex;align-items:center;gap:7px;padding:9px 12px;border:1px solid #e4e7ec;border-radius:11px;color:#667085;background:#fff;font-size:.65rem}.orders-summary-badge i{color:#2563eb}.orders-summary-badge strong{color:#101828;font-size:.9rem}.orders-stat-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.orders-stat-card{display:flex;align-items:center;gap:11px;padding:15px;border:1px solid #e4e7ec;border-radius:15px;background:#fff;box-shadow:0 6px 22px rgba(16,24,40,.035)}.stat-icon{display:grid;width:38px;height:38px;flex:0 0 38px;place-items:center;border-radius:11px;font-size:.9rem}.stat-icon.blue{color:#2563eb;background:#eff6ff}.stat-icon.orange{color:#c2410c;background:#fff7ed}.stat-icon.green{color:#047857;background:#ecfdf3}.stat-icon.purple{color:#7c3aed;background:#f5f3ff}.orders-stat-card div{display:grid;min-width:0}.orders-stat-card small{color:#667085;font-size:.58rem;font-weight:750}.orders-stat-card strong{margin-top:2px;overflow:hidden;color:#101828;font-size:.95rem;font-weight:900;text-overflow:ellipsis;white-space:nowrap}.orders-stat-card span{margin-top:2px;color:#98a2b3;font-size:.53rem}.orders-history-panel{border:1px solid #e4e7ec;border-radius:18px;background:#fff;box-shadow:0 8px 28px rgba(16,24,40,.045);overflow:hidden}.history-panel-head{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:19px 20px 13px}.history-panel-head h3{margin:0;font-size:.95rem;font-weight:900}.history-panel-head p{margin:4px 0 0;color:#98a2b3;font-size:.63rem}.history-panel-head>span{display:inline-flex;align-items:center;gap:5px;color:#667085;font-size:.58rem;font-weight:750}.history-panel-head>span i{color:#16a34a}.order-status-tabs{display:flex;flex-wrap:wrap;gap:7px;padding:0 20px 15px;border-bottom:1px solid #f0f2f5}.order-status-tabs button{display:inline-flex;align-items:center;gap:5px;border:1px solid #e4e7ec;border-radius:9px;padding:7px 10px;color:#667085;background:#fff;font-size:.62rem;font-weight:800;cursor:pointer}.order-status-tabs button:hover,.order-status-tabs button.active{border-color:#bfdbfe;color:#1d4ed8;background:#eff6ff}.order-status-tabs b{display:inline-grid;min-width:18px;height:18px;margin-left:2px;place-items:center;border-radius:999px;color:#667085;background:#f2f4f7;font-size:.55rem}.order-status-tabs button.active b{color:#1d4ed8;background:#fff}.orders-list{display:grid;gap:10px;padding:14px 20px 20px}.order-history-card{border:1px solid #e4e7ec;border-radius:15px;background:#fff;overflow:hidden;transition:.18s;box-shadow:0 5px 18px rgba(16,24,40,.025)}.order-history-card:hover{border-color:#cbdaf5;box-shadow:0 9px 25px rgba(37,99,235,.07)}.order-history-head{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:13px 14px;border-bottom:1px solid #f0f2f5}.order-heading-left{display:flex;align-items:center;gap:9px}.order-receipt-icon{display:grid;width:34px;height:34px;place-items:center;border-radius:9px;color:#2563eb;background:#eff6ff}.order-code{display:block;color:#101828;font-size:.73rem;font-weight:900}.order-history-head small{display:block;margin-top:3px;color:#98a2b3;font-size:.59rem}.order-status{display:inline-flex;align-items:center;gap:5px;padding:6px 9px;border-radius:999px;font-size:.59rem;font-weight:850;white-space:nowrap}.order-status.is-pending{color:#a15c00;background:#fff7e6}.order-status.is-shipping{color:#1d4ed8;background:#eff6ff}.order-status.is-delivered{color:#087443;background:#ecfdf3}.order-status.is-returned{color:#7c3aed;background:#f5f3ff}.order-status.is-cancelled{color:#b42318;background:#fef3f2}.order-history-body{display:flex;align-items:center;gap:15px;padding:14px}.order-product-preview{display:flex;align-items:center;gap:6px;min-width:0}.order-thumb,.order-more{position:relative;width:52px;height:52px;flex:0 0 52px;overflow:hidden;border:1px solid #eaecf0;border-radius:10px;background:#f8fafc}.order-thumb img{width:100%;height:100%;object-fit:contain}.order-thumb span{position:absolute;right:3px;bottom:3px;padding:2px 4px;border-radius:4px;color:#fff;background:rgba(16,24,40,.72);font-size:.5rem;font-weight:800}.order-more{display:grid;place-items:center;color:#667085;font-size:.68rem;font-weight:900}.order-history-summary{display:grid;gap:3px;min-width:0}.order-history-summary span{color:#667085;font-size:.61rem}.order-history-summary strong{color:#101828;font-size:.92rem;font-weight:900}.order-history-summary small{color:#98a2b3;font-size:.58rem}.order-history-foot{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 14px;border-top:1px solid #f0f2f5;background:#fcfcfd}.invoice-meta{display:flex;align-items:center;gap:7px;color:#667085;font-size:.6rem}.invoice-meta>i{color:#2563eb}.invoice-meta span,.invoice-meta small{display:block}.invoice-meta small{margin-top:2px;color:#98a2b3;font-size:.52rem}.order-actions{display:flex;gap:6px}.invoice-btn,.danger-outline-btn,.return-outline-btn{display:inline-flex;align-items:center;gap:5px;border-radius:8px;padding:7px 9px;font-size:.59rem;font-weight:800;cursor:pointer}.invoice-btn{border:1px solid #bfdbfe;color:#1d4ed8;background:#fff}.invoice-btn:hover{background:#eff6ff}.danger-outline-btn{border:1px solid #fecdca;color:#b42318;background:#fff}.danger-outline-btn:hover{background:#fef3f2}.return-outline-btn{border:1px solid #ddd6fe;color:#6d28d9;background:#fff}.return-outline-btn:hover{background:#f5f3ff}.orders-empty{display:grid;place-items:center;padding:65px 20px;text-align:center}.orders-empty-icon{display:grid;width:58px;height:58px;place-items:center;margin-bottom:12px;border-radius:16px;color:#2563eb;background:#eff6ff;font-size:1.3rem}.orders-empty h3{margin:0;color:#101828;font-size:.95rem;font-weight:850}.orders-empty p{margin:5px 0 0;color:#98a2b3;font-size:.66rem}.invoice-overlay{position:fixed;inset:0;z-index:1200;display:grid;place-items:center;padding:20px;background:rgba(16,24,40,.56);backdrop-filter:blur(5px)}.invoice-modal{width:min(100%,850px);max-height:calc(100vh - 40px);overflow:auto;border-radius:18px;background:#fff;box-shadow:0 25px 70px rgba(16,24,40,.24)}.invoice-toolbar{display:flex;align-items:center;justify-content:space-between;padding:12px 18px;border-bottom:1px solid #e4e7ec}.invoice-toolbar div{display:flex;align-items:center;gap:8px}.invoice-toolbar span{color:#2563eb;font-size:.58rem;font-weight:950;letter-spacing:.12em}.invoice-toolbar strong{color:#667085;font-size:.61rem}.invoice-toolbar button{display:grid;width:30px;height:30px;place-items:center;border:1px solid #e4e7ec;border-radius:8px;color:#667085;background:#fff;cursor:pointer}.invoice-sheet{padding:28px}.invoice-header{display:flex;justify-content:space-between;gap:25px;padding-bottom:18px;border-bottom:2px solid #101828}.invoice-brand{color:#2563eb;font-size:.7rem;font-weight:950;letter-spacing:.14em}.invoice-header h1{margin:5px 0 4px;color:#101828;font-size:1.45rem;font-weight:900}.invoice-header p,.invoice-date span{margin:0;color:#667085;font-size:.62rem}.invoice-date{text-align:right}.invoice-date strong{display:block;margin-top:4px;color:#101828;font-size:.68rem}.invoice-info-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;padding:18px 0}.invoice-info-grid>div{display:grid;gap:4px}.invoice-info-grid small{color:#98a2b3;font-size:.58rem;font-weight:800;text-transform:uppercase}.invoice-info-grid strong,.invoice-info-grid span{color:#344054;font-size:.68rem}.invoice-table{width:100%;border-collapse:collapse;font-size:.63rem}.invoice-table th{padding:8px 6px;color:#667085;background:#f8fafc;text-align:left;font-size:.57rem}.invoice-table td{padding:8px 6px;border-bottom:1px solid #eaecf0;color:#344054}.invoice-table th:not(:first-child),.invoice-table td:not(:first-child){text-align:right}.invoice-total{display:grid;justify-content:end;min-width:270px;margin-top:15px}.invoice-total div{display:flex;justify-content:space-between;gap:35px;padding:4px 0;color:#667085;font-size:.65rem}.invoice-total strong{color:#344054}.invoice-total .grand{margin-top:4px;padding-top:8px;border-top:1px solid #d0d5dd;color:#101828;font-size:.73rem}.invoice-total .grand strong{color:#2563eb;font-size:.92rem}.invoice-status{display:flex;justify-content:space-between;margin-top:14px;padding:9px 11px;border-radius:9px;background:#f8fafc;font-size:.64rem}.invoice-status strong.is-pending{color:#a15c00}.invoice-status strong.is-shipping{color:#1d4ed8}.invoice-status strong.is-delivered{color:#087443}.invoice-status strong.is-returned{color:#7c3aed}.invoice-status strong.is-cancelled{color:#b42318}.invoice-payment-note{display:flex;gap:7px;margin-top:10px;padding:8px 10px;border:1px solid #bfdbfe;border-radius:8px;color:#1e40af;background:#eff6ff;font-size:.58rem;line-height:1.4}.invoice-payment-note i{color:#2563eb}.invoice-note{margin:14px 0 0;color:#98a2b3;font-size:.55rem;text-align:center}.invoice-actions{display:flex;justify-content:flex-end;gap:8px;padding:11px 18px;border-top:1px solid #e4e7ec;background:#fcfcfd}.secondary-invoice-btn,.primary-invoice-btn{border-radius:8px;padding:8px 12px;font-size:.63rem;font-weight:800;cursor:pointer}.secondary-invoice-btn{border:1px solid #d0d5dd;color:#344054;background:#fff}.primary-invoice-btn{border:1px solid #2563eb;color:#fff;background:#2563eb}.primary-invoice-btn:hover{background:#1d4ed8}
@media(max-width:900px){.orders-stat-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:700px){.orders-page-header,.history-panel-head{align-items:flex-start;flex-direction:column}.orders-stat-grid{grid-template-columns:1fr}.order-history-body{align-items:flex-start;flex-direction:column}.order-history-foot{align-items:stretch;flex-direction:column}.order-actions{justify-content:flex-end}.invoice-sheet{padding:20px}.invoice-info-grid{grid-template-columns:1fr}.invoice-header{flex-direction:column}.invoice-date{text-align:left}}
@media print{html,body{width:210mm!important;height:297mm!important;margin:0!important;background:#fff!important}body *{visibility:hidden!important}.invoice-overlay{position:static!important;display:block!important;inset:auto!important;padding:0!important;background:#fff!important;backdrop-filter:none!important}.invoice-modal{width:194mm!important;height:281mm!important;max-height:none!important;overflow:hidden!important;margin:0 auto!important;border-radius:0!important;box-shadow:none!important}.invoice-sheet,.invoice-sheet *{visibility:visible!important}.invoice-sheet{width:100%!important;height:281mm!important;max-height:281mm!important;overflow:hidden!important;padding:7mm!important;box-sizing:border-box!important;zoom:.82!important}.invoice-header{padding-bottom:5mm!important}.invoice-header h1{font-size:17pt!important}.invoice-info-grid{padding:4mm 0!important}.invoice-table{font-size:8.5pt!important}.invoice-table th{padding:2.5mm 2mm!important;font-size:7.5pt!important}.invoice-table td{padding:2.5mm 2mm!important;font-size:8pt!important}.invoice-total{margin-top:4mm!important}.invoice-total div{padding:1.2mm 0!important}.invoice-status{margin-top:4mm!important}.invoice-payment-note{margin-top:3mm!important}.invoice-note{margin-top:4mm!important}.no-print{display:none!important;visibility:hidden!important}@page{size:A4 portrait;margin:8mm}}
</style>
