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
const actionError = ref('');

const statusOptions: Array<{ key: 'all' | OrderStatus; label: string }> = [
    { key: 'all', label: 'Tất cả' },
    { key: 'Chờ xử lý', label: 'Chờ xử lý' },
    { key: 'Đang giao', label: 'Đang giao' },
    { key: 'Đã giao', label: 'Đã giao' },
    { key: 'Trả hàng', label: 'Trả hàng' },
    { key: 'Hủy hàng', label: 'Hủy hàng' },
];

const filteredOrders = computed(() => filter.value === 'all' ? orders.value : orders.value.filter((order) => order.status === filter.value));

const statusMeta: Record<OrderStatus, { icon: string; className: string; description: string }> = {
    'Chờ xử lý': { icon: 'bi-hourglass-split', className: 'is-pending', description: 'TechStore đang tiếp nhận đơn hàng.' },
    'Đang giao': { icon: 'bi-truck', className: 'is-shipping', description: 'Đơn hàng đang được vận chuyển.' },
    'Đã giao': { icon: 'bi-check-circle-fill', className: 'is-delivered', description: 'Đơn hàng đã giao thành công.' },
    'Trả hàng': { icon: 'bi-arrow-return-left', className: 'is-returned', description: 'Đơn hàng đang ở trạng thái trả hàng.' },
    'Hủy hàng': { icon: 'bi-x-circle-fill', className: 'is-cancelled', description: 'Đơn hàng đã được hủy.' },
};

function loadOrders() {
    if (!userId.value) return;
    syncLastOrderToHistory(userId.value);
    orders.value = readOrderHistory(userId.value);
}

function formatDate(value: string) {
    return new Intl.DateTimeFormat('vi-VN', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}

function paymentLabel(order: TechStoreOrder) {
    return order.payment === 'paypal-demo' ? 'PayPal mô phỏng' : 'Thanh toán khi nhận hàng';
}

function openInvoice(order: TechStoreOrder) {
    selectedOrder.value = order;
    actionError.value = '';
}

function closeInvoice() {
    selectedOrder.value = null;
}

function cancelOrder(order: TechStoreOrder) {
    if (!userId.value || order.status !== 'Chờ xử lý') return;
    if (!window.confirm(`Bạn có chắc muốn hủy đơn ${order.code}?`)) return;
    orders.value = updateOrderStatus(userId.value, order.code, 'Hủy hàng');
}

function requestReturn(order: TechStoreOrder) {
    if (!userId.value || order.status !== 'Đã giao') return;
    if (!window.confirm(`Gửi yêu cầu trả hàng cho đơn ${order.code}?`)) return;
    orders.value = updateOrderStatus(userId.value, order.code, 'Trả hàng');
}

function printInvoice() {
    if (!selectedOrder.value) return;
    window.print();
}

onMounted(loadOrders);
</script>

<template>
    <Head title="Lịch sử mua hàng" />

    <div class="order-history-page">
        <section class="settings-view orders-view">
            <div class="view-header">
                <div class="view-title-wrap">
                    <div class="view-icon orders-icon"><i class="bi bi-receipt-cutoff" /></div>
                    <div>
                        <span class="section-kicker">MUA SẮM</span>
                        <h2>Lịch sử mua hàng</h2>
                        <p>Theo dõi đơn hàng, trạng thái giao hàng và hóa đơn bán hàng.</p>
                    </div>
                </div>
                <div class="orders-count"><strong>{{ orders.length }}</strong><span>đơn hàng</span></div>
            </div>

            <div class="order-status-tabs">
                <button v-for="item in statusOptions" :key="item.key" type="button" :class="{ active: filter === item.key }" @click="filter = item.key">
                    {{ item.label }}
                    <span v-if="item.key !== 'all'">{{ orders.filter((order) => order.status === item.key).length }}</span>
                </button>
            </div>

            <div v-if="filteredOrders.length" class="orders-list">
                <article v-for="order in filteredOrders" :key="order.code" class="order-history-card">
                    <div class="order-history-head">
                        <div>
                            <span class="order-code">Đơn hàng #{{ order.code }}</span>
                            <small>{{ formatDate(order.createdAt) }}</small>
                        </div>
                        <span class="order-status" :class="statusMeta[order.status].className"><i :class="['bi', statusMeta[order.status].icon]" />{{ order.status }}</span>
                    </div>

                    <div class="order-history-body">
                        <div class="order-product-preview">
                            <div v-for="item in order.items.slice(0, 4)" :key="`${order.code}-${item.id}`" class="order-thumb">
                                <img :src="item.image" :alt="item.name" />
                                <span v-if="item.quantity > 1">×{{ item.quantity }}</span>
                            </div>
                            <div v-if="order.items.length > 4" class="order-more">+{{ order.items.length - 4 }}</div>
                        </div>
                        <div class="order-history-summary">
                            <span>{{ order.items.length }} sản phẩm · {{ paymentLabel(order) }}</span>
                            <strong>{{ formatPrice(order.total) }}</strong>
                            <small>{{ statusMeta[order.status].description }}</small>
                        </div>
                    </div>

                    <div class="order-history-foot">
                        <button type="button" class="invoice-btn" @click="openInvoice(order)"><i class="bi bi-receipt" /> Hóa đơn bán hàng</button>
                        <div class="order-actions">
                            <button v-if="order.status === 'Chờ xử lý'" type="button" class="danger-outline-btn" @click="cancelOrder(order)"><i class="bi bi-x-lg" /> Hủy hàng</button>
                            <button v-if="order.status === 'Đã giao'" type="button" class="return-outline-btn" @click="requestReturn(order)"><i class="bi bi-arrow-return-left" /> Trả hàng</button>
                        </div>
                    </div>
                </article>
            </div>

            <div v-else class="orders-empty">
                <div class="orders-empty-icon"><i class="bi bi-receipt" /></div>
                <h3>{{ filter === 'all' ? 'Chưa có lịch sử mua hàng' : 'Không có đơn hàng phù hợp' }}</h3>
                <p>{{ filter === 'all' ? 'Các đơn hàng bạn hoàn tất tại TechStore sẽ được lưu tại đây.' : 'Hãy chọn trạng thái khác để xem đơn hàng.' }}</p>
            </div>
        </section>

        <div v-if="selectedOrder" class="invoice-overlay" @click.self="closeInvoice">
            <section class="invoice-modal" role="dialog" aria-modal="true" aria-labelledby="invoice-title">
                <div class="invoice-toolbar no-print">
                    <span>HÓA ĐƠN BÁN HÀNG</span>
                    <button type="button" aria-label="Đóng" @click="closeInvoice"><i class="bi bi-x-lg" /></button>
                </div>
                <div class="invoice-sheet">
                    <header class="invoice-header">
                        <div><span class="invoice-brand">TECHSTORE</span><h1>Hóa đơn bán hàng</h1><p>Mã đơn: <strong>{{ selectedOrder.code }}</strong></p></div>
                        <div class="invoice-date"><span>Ngày đặt</span><strong>{{ formatDate(selectedOrder.createdAt) }}</strong></div>
                    </header>

                    <div class="invoice-info-grid">
                        <div><small>Người nhận</small><strong>{{ selectedOrder.customer?.name || 'Khách hàng TechStore' }}</strong><span>{{ selectedOrder.customer?.phone || '—' }}</span><span>{{ selectedOrder.customer?.email || '—' }}</span></div>
                        <div><small>Địa chỉ giao hàng</small><strong>{{ selectedOrder.customer?.address || '—' }}</strong><span>Thanh toán: {{ paymentLabel(selectedOrder) }}</span></div>
                    </div>

                    <div class="invoice-table-wrap">
                        <table class="invoice-table">
                            <thead><tr><th>Sản phẩm</th><th>SL</th><th>Đơn giá</th><th>Thành tiền</th></tr></thead>
                            <tbody><tr v-for="item in selectedOrder.items" :key="`${selectedOrder.code}-invoice-${item.id}`"><td>{{ item.name }}</td><td>{{ item.quantity }}</td><td>{{ formatPrice(item.price) }}</td><td>{{ formatPrice(item.price * item.quantity) }}</td></tr></tbody>
                        </table>
                    </div>

                    <div class="invoice-total"><div><span>Tạm tính</span><strong>{{ formatPrice(selectedOrder.subtotal) }}</strong></div><div><span>Phí vận chuyển</span><strong>{{ formatPrice(selectedOrder.totalShipping) }}</strong></div><div class="grand"><span>Tổng thanh toán</span><strong>{{ formatPrice(selectedOrder.total) }}</strong></div></div>
                    <div class="invoice-status"><span>Trạng thái đơn hàng</span><strong :class="statusMeta[selectedOrder.status].className">{{ selectedOrder.status }}</strong></div>
                    <p class="invoice-note">Cảm ơn bạn đã mua sắm tại TechStore. Đây là hóa đơn điện tử mô phỏng trong phiên bản hiện tại.</p>
                </div>
                <div class="invoice-actions no-print"><button type="button" class="secondary-invoice-btn" @click="closeInvoice">Đóng</button><button type="button" class="primary-invoice-btn" @click="printInvoice"><i class="bi bi-printer" /> In hóa đơn</button></div>
            </section>
        </div>
    </div>
</template>

<style>
.order-history-page{width:100%;color:#101828}.orders-icon{color:#2563eb;background:#eff6ff}.orders-count{display:flex;align-items:baseline;gap:6px;padding:9px 13px;border:1px solid #e4e7ec;border-radius:12px;background:#f8fafc}.orders-count strong{font-size:1rem;color:#2563eb}.orders-count span{color:#667085;font-size:.7rem;font-weight:700}.order-status-tabs{display:flex;flex-wrap:wrap;gap:7px;margin:0 0 16px}.order-status-tabs button{border:1px solid #e4e7ec;border-radius:999px;padding:7px 11px;color:#667085;background:#fff;font-size:.7rem;font-weight:800;cursor:pointer}.order-status-tabs button:hover,.order-status-tabs button.active{border-color:#bfdbfe;color:#1d4ed8;background:#eff6ff}.order-status-tabs span{display:inline-grid;min-width:18px;height:18px;margin-left:4px;place-items:center;border-radius:999px;background:#f2f4f7;font-size:.62rem}.order-status-tabs button.active span{background:#fff}.orders-list{display:grid;gap:12px}.order-history-card{border:1px solid #e4e7ec;border-radius:17px;background:#fff;box-shadow:0 8px 28px rgba(16,24,40,.045);overflow:hidden}.order-history-head{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:15px 17px;border-bottom:1px solid #f0f2f5}.order-code{display:block;color:#101828;font-size:.78rem;font-weight:900}.order-history-head small{display:block;margin-top:4px;color:#98a2b3;font-size:.67rem}.order-status{display:inline-flex;align-items:center;gap:5px;padding:6px 9px;border-radius:999px;font-size:.64rem;font-weight:850;white-space:nowrap}.order-status.is-pending{color:#a15c00;background:#fff7e6}.order-status.is-shipping{color:#1d4ed8;background:#eff6ff}.order-status.is-delivered{color:#087443;background:#ecfdf3}.order-status.is-returned{color:#7c3aed;background:#f5f3ff}.order-status.is-cancelled{color:#b42318;background:#fef3f2}.order-history-body{display:flex;align-items:center;gap:16px;padding:15px 17px}.order-product-preview{display:flex;align-items:center;gap:7px;min-width:0}.order-thumb,.order-more{position:relative;width:58px;height:58px;flex:0 0 58px;overflow:hidden;border:1px solid #eaecf0;border-radius:11px;background:#f8fafc}.order-thumb img{width:100%;height:100%;object-fit:contain}.order-thumb span{position:absolute;right:3px;bottom:3px;padding:2px 4px;border-radius:5px;color:#fff;background:rgba(16,24,40,.72);font-size:.56rem;font-weight:800}.order-more{display:grid;place-items:center;color:#667085;font-size:.75rem;font-weight:900}.order-history-summary{display:grid;gap:3px;min-width:0}.order-history-summary span{color:#667085;font-size:.67rem}.order-history-summary strong{color:#101828;font-size:1rem;font-weight:900}.order-history-summary small{color:#98a2b3;font-size:.65rem}.order-history-foot{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:11px 17px;border-top:1px solid #f0f2f5;background:#fcfcfd}.order-actions{display:flex;gap:7px}.invoice-btn,.danger-outline-btn,.return-outline-btn{display:inline-flex;align-items:center;gap:6px;border-radius:9px;padding:7px 10px;font-size:.67rem;font-weight:800;cursor:pointer}.invoice-btn{border:1px solid #bfdbfe;color:#1d4ed8;background:#fff}.invoice-btn:hover{background:#eff6ff}.danger-outline-btn{border:1px solid #fecdca;color:#b42318;background:#fff}.danger-outline-btn:hover{background:#fef3f2}.return-outline-btn{border:1px solid #ddd6fe;color:#6d28d9;background:#fff}.return-outline-btn:hover{background:#f5f3ff}.orders-empty{display:grid;place-items:center;padding:65px 20px;border:1px dashed #d0d5dd;border-radius:17px;background:#fff;text-align:center}.orders-empty-icon{display:grid;width:58px;height:58px;place-items:center;margin-bottom:12px;border-radius:16px;color:#2563eb;background:#eff6ff;font-size:1.3rem}.orders-empty h3{margin:0;color:#101828;font-size:1rem;font-weight:850}.orders-empty p{margin:5px 0 0;color:#98a2b3;font-size:.72rem}.invoice-overlay{position:fixed;inset:0;z-index:1200;display:grid;place-items:center;padding:20px;background:rgba(16,24,40,.56);backdrop-filter:blur(5px)}.invoice-modal{width:min(100%,850px);max-height:calc(100vh - 40px);overflow:auto;border-radius:18px;background:#fff;box-shadow:0 25px 70px rgba(16,24,40,.24)}.invoice-toolbar{display:flex;align-items:center;justify-content:space-between;padding:13px 18px;border-bottom:1px solid #e4e7ec;color:#667085;font-size:.68rem;font-weight:900;letter-spacing:.08em}.invoice-toolbar button{border:0;color:#667085;background:transparent;cursor:pointer}.invoice-sheet{padding:30px}.invoice-header{display:flex;justify-content:space-between;gap:25px;padding-bottom:20px;border-bottom:2px solid #101828}.invoice-brand{color:#2563eb;font-size:.75rem;font-weight:950;letter-spacing:.14em}.invoice-header h1{margin:6px 0 4px;color:#101828;font-size:1.55rem;font-weight:900}.invoice-header p,.invoice-date span{margin:0;color:#667085;font-size:.68rem}.invoice-date{text-align:right}.invoice-date strong{display:block;margin-top:5px;color:#101828;font-size:.72rem}.invoice-info-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;padding:20px 0}.invoice-info-grid>div{display:grid;gap:4px}.invoice-info-grid small{color:#98a2b3;font-size:.62rem;font-weight:800;text-transform:uppercase}.invoice-info-grid strong,.invoice-info-grid span{color:#344054;font-size:.72rem}.invoice-table-wrap{overflow:auto}.invoice-table{width:100%;border-collapse:collapse;font-size:.68rem}.invoice-table th{padding:9px 7px;color:#667085;background:#f8fafc;text-align:left;font-size:.62rem}.invoice-table td{padding:10px 7px;border-bottom:1px solid #eaecf0;color:#344054}.invoice-table th:not(:first-child),.invoice-table td:not(:first-child){text-align:right}.invoice-total{display:grid;justify-content:end;min-width:270px;margin-top:18px}.invoice-total div{display:flex;justify-content:space-between;gap:35px;padding:5px 0;color:#667085;font-size:.7rem}.invoice-total strong{color:#344054}.invoice-total .grand{margin-top:5px;padding-top:10px;border-top:1px solid #d0d5dd;color:#101828;font-size:.78rem}.invoice-total .grand strong{color:#2563eb;font-size:1rem}.invoice-status{display:flex;justify-content:space-between;margin-top:17px;padding:11px 13px;border-radius:10px;background:#f8fafc;font-size:.7rem}.invoice-status strong.is-pending{color:#a15c00}.invoice-status strong.is-shipping{color:#1d4ed8}.invoice-status strong.is-delivered{color:#087443}.invoice-status strong.is-returned{color:#7c3aed}.invoice-status strong.is-cancelled{color:#b42318}.invoice-note{margin:18px 0 0;color:#98a2b3;font-size:.62rem;text-align:center}.invoice-actions{display:flex;justify-content:flex-end;gap:8px;padding:12px 18px;border-top:1px solid #e4e7ec;background:#fcfcfd}.secondary-invoice-btn,.primary-invoice-btn{border-radius:9px;padding:8px 12px;font-size:.68rem;font-weight:800;cursor:pointer}.secondary-invoice-btn{border:1px solid #d0d5dd;color:#344054;background:#fff}.primary-invoice-btn{border:1px solid #2563eb;color:#fff;background:#2563eb}.primary-invoice-btn:hover{background:#1d4ed8}@media(max-width:700px){.order-history-body{align-items:flex-start;flex-direction:column}.order-history-foot{align-items:stretch;flex-direction:column}.order-actions{justify-content:flex-end}.invoice-sheet{padding:20px}.invoice-info-grid{grid-template-columns:1fr}.invoice-header{flex-direction:column}.invoice-date{text-align:left}}@media print{body *{visibility:hidden!important}.invoice-sheet,.invoice-sheet *{visibility:visible!important}.invoice-overlay{position:static!important;background:#fff!important;padding:0!important}.invoice-modal{width:100%!important;max-height:none!important;box-shadow:none!important;border-radius:0!important}.no-print{display:none!important}.invoice-sheet{padding:0!important}.invoice-table{font-size:10pt}.invoice-header h1{font-size:18pt}}
</style>
