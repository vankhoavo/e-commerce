<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { formatPrice } from '@/data/products';

type OrderStatus = 'Chờ xử lý' | 'Đang giao' | 'Đã giao' | 'Trả hàng' | 'Hủy hàng';
type TechStoreOrder = {
    id: number;
    code: string;
    createdAt: string;
    customer?: { name?: string; phone?: string; email?: string; address?: string; note?: string };
    items: Array<{ id: number; name: string; price: number; image: string; quantity: number }>;
    subtotal: number;
    shipping: number;
    totalShipping: number;
    total: number;
    payment: string;
    paypalOrderId?: string | null;
    status: OrderStatus;
    cancelledAt?: string | null;
    returnedAt?: string | null;
};

const page = usePage();
const userId = computed<number | null>(() => {
    const id = (page.props as any).auth?.user?.id;
    return id ? Number(id) : null;
});
const orders = ref<TechStoreOrder[]>([]);
const selectedOrder = ref<TechStoreOrder | null>(null);
const cancelTarget = ref<TechStoreOrder | null>(null);
const filter = ref<'all' | OrderStatus>('all');
const loading = ref(false);
const actionError = ref('');

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
const csrfToken = () => document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

async function loadOrders() {
    if (!userId.value) return;
    loading.value = true;
    actionError.value = '';
    try {
        const response = await fetch('/orders', { headers: { Accept: 'application/json' } });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || !Array.isArray(data.orders)) throw new Error(data.message ?? 'Không thể tải lịch sử mua hàng.');
        orders.value = data.orders;
    } catch (error) {
        actionError.value = error instanceof Error ? error.message : 'Không thể tải lịch sử mua hàng.';
    } finally {
        loading.value = false;
    }
}

function formatDate(value: string) { return new Intl.DateTimeFormat('vi-VN', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)); }
function paymentLabel(order: TechStoreOrder) { return order.payment === 'paypal-sandbox' ? 'PayPal Sandbox' : order.payment === 'paypal-demo' ? 'PayPal mô phỏng' : 'Thanh toán khi nhận hàng'; }
function openCancelModal(order: TechStoreOrder) {
    if (order.status !== 'Chờ xử lý') return;
    cancelTarget.value = order;
    actionError.value = '';
}
function closeCancelModal() { cancelTarget.value = null; }
async function confirmCancelOrder() {
    if (!cancelTarget.value) return;
    const order = cancelTarget.value;
    try {
        const response = await fetch(`/orders/${order.id}/cancel`, { method: 'PATCH', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() } });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.order) throw new Error(data.message ?? 'Không thể hủy đơn hàng.');
        const index = orders.value.findIndex((item) => item.id === order.id);
        if (index >= 0) orders.value[index] = data.order;
        if (selectedOrder.value?.id === order.id) selectedOrder.value = data.order;
        cancelTarget.value = null;
    } catch (error) {
        actionError.value = error instanceof Error ? error.message : 'Không thể hủy đơn hàng.';
    }
}
async function requestReturn(order: TechStoreOrder) {
    if (order.status !== 'Đã giao' || !window.confirm(`Gửi yêu cầu trả hàng cho đơn ${order.code}?`)) return;
    try {
        const response = await fetch(`/orders/${order.id}/return`, { method: 'PATCH', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() } });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.order) throw new Error(data.message ?? 'Không thể gửi yêu cầu trả hàng.');
        const index = orders.value.findIndex((item) => item.id === order.id);
        if (index >= 0) orders.value[index] = data.order;
        if (selectedOrder.value?.id === order.id) selectedOrder.value = data.order;
    } catch (error) {
        actionError.value = error instanceof Error ? error.message : 'Không thể gửi yêu cầu trả hàng.';
    }
}
function printInvoice() { window.print(); }
onMounted(loadOrders);
</script>

<template>
    <section class="orders-dashboard">
        <header class="orders-page-header"><div class="orders-title-wrap"><span class="orders-title-icon"><i class="bi bi-bag-check" /></span><div><span class="orders-kicker">TECHSTORE · TÀI KHOẢN</span><h2>Đơn hàng</h2><p>Lịch sử mua hàng, trạng thái giao nhận và hóa đơn bán hàng.</p></div></div><div class="orders-summary-badge"><i class="bi bi-receipt"/><strong>{{ orders.length }}</strong><span>đơn hàng</span></div></header>
        <div v-if="actionError" class="orders-inline-error"><i class="bi bi-exclamation-circle"/> {{ actionError }}</div>
        <div class="orders-stat-grid"><article class="orders-stat-card"><span class="stat-icon blue"><i class="bi bi-receipt-cutoff"/></span><div><small>Tổng đơn hàng</small><strong>{{ orders.length }}</strong><span>Tất cả đơn đã tạo</span></div></article><article class="orders-stat-card"><span class="stat-icon orange"><i class="bi bi-truck"/></span><div><small>Đang xử lý</small><strong>{{ activeCount }}</strong><span>Chờ xử lý hoặc đang giao</span></div></article><article class="orders-stat-card"><span class="stat-icon green"><i class="bi bi-check2-circle"/></span><div><small>Đã giao</small><strong>{{ deliveredCount }}</strong><span>Giao thành công</span></div></article><article class="orders-stat-card"><span class="stat-icon purple"><i class="bi bi-cash-stack"/></span><div><small>Giá trị mua hàng</small><strong>{{ formatPrice(totalSpent) }}</strong><span>Không tính đơn đã hủy</span></div></article></div>
        <section class="orders-history-panel"><div class="history-panel-head"><div><h3>Lịch sử mua hàng</h3><p>Chọn trạng thái để xem nhanh các đơn phù hợp.</p></div><span><i class="bi bi-shield-check"/> Theo tài khoản</span></div>
            <div class="order-status-tabs"><button v-for="item in statusOptions" :key="item.key" type="button" :class="{ active: filter === item.key }" @click="filter = item.key"><i :class="['bi', item.icon]"/> {{ item.label }}<b v-if="item.key !== 'all'">{{ orders.filter((order) => order.status === item.key).length }}</b></button></div>
            <div v-if="loading" class="orders-empty"><div class="orders-empty-icon"><i class="bi bi-arrow-repeat"/></div><h3>Đang tải lịch sử mua hàng</h3><p>Đang đồng bộ dữ liệu từ tài khoản của bạn.</p></div>
            <div v-else-if="filteredOrders.length" class="orders-list"><article v-for="order in filteredOrders" :key="order.code" class="order-history-card"><div class="order-history-head"><div class="order-heading-left"><div class="order-receipt-icon"><i class="bi bi-receipt"/></div><div><span class="order-code">#{{ order.code }}</span><small>{{ formatDate(order.createdAt) }}</small></div></div><span class="order-status" :class="statusMeta[order.status].className"><i :class="['bi', statusMeta[order.status].icon]"/>{{ order.status }}</span></div><div class="order-history-body"><div class="order-product-preview"><div v-for="item in order.items.slice(0,4)" :key="`${order.code}-${item.id}`" class="order-thumb"><img :src="item.image" :alt="item.name"/><span v-if="item.quantity > 1">×{{ item.quantity }}</span></div><div v-if="order.items.length > 4" class="order-more">+{{ order.items.length - 4 }}</div></div><div class="order-history-summary"><span>{{ order.items.reduce((sum,item)=>sum+item.quantity,0) }} sản phẩm · {{ paymentLabel(order) }}</span><strong>{{ formatPrice(order.total) }}</strong><small>{{ statusMeta[order.status].description }}</small></div></div><div class="order-history-foot"><div class="invoice-meta"><i class="bi bi-file-earmark-text"/><span>Hóa đơn bán hàng <small>Đầy đủ thông tin đơn</small></span></div><div class="order-actions"><button type="button" class="invoice-btn" @click="selectedOrder = order"><i class="bi bi-receipt-cutoff"/> Xem hóa đơn</button><button v-if="order.status === 'Chờ xử lý'" type="button" class="danger-outline-btn" @click="openCancelModal(order)"><i class="bi bi-x-lg"/> Hủy hàng</button><button v-if="order.status === 'Đã giao'" type="button" class="return-outline-btn" @click="requestReturn(order)"><i class="bi bi-arrow-return-left"/> Trả hàng</button></div></div></article></div>
            <div v-else class="orders-empty"><div class="orders-empty-icon"><i class="bi bi-bag-x"/></div><h3>{{ filter === 'all' ? 'Chưa có lịch sử mua hàng' : 'Không có đơn hàng phù hợp' }}</h3><p>{{ filter === 'all' ? 'Đơn hàng sau khi đặt sẽ xuất hiện tại đây cùng hóa đơn bán hàng.' : 'Hãy chọn trạng thái khác để xem đơn hàng.' }}</p></div>
        </section>
    </section>

    <div v-if="cancelTarget" class="order-confirm-overlay" @click.self="closeCancelModal">
        <section class="order-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="cancel-order-title">
            <div class="order-confirm-icon"><i class="bi bi-exclamation-triangle-fill"/></div>
            <div class="order-confirm-content"><span class="order-confirm-kicker">XÁC NHẬN HỦY ĐƠN</span><h3 id="cancel-order-title">Bạn muốn hủy đơn hàng?</h3><p>Đơn <strong>#{{ cancelTarget.code }}</strong> đang ở trạng thái <strong>Chờ xử lý</strong>. Sau khi hủy, đơn hàng sẽ chuyển sang <strong>Hủy hàng</strong> và không thể tiếp tục xử lý.</p></div>
            <div class="order-confirm-actions"><button type="button" class="confirm-keep-btn" @click="closeCancelModal">Giữ lại đơn</button><button type="button" class="confirm-cancel-btn" @click="confirmCancelOrder"><i class="bi bi-x-lg"/> Xác nhận hủy</button></div>
        </section>
    </div>

    <div v-if="selectedOrder" class="invoice-overlay" @click.self="selectedOrder = null">
        <section class="invoice-modal" role="dialog" aria-modal="true">
            <div class="invoice-toolbar no-print"><div><span>TECHSTORE</span><strong>HÓA ĐƠN BÁN HÀNG</strong></div><button type="button" @click="selectedOrder = null"><i class="bi bi-x-lg"/></button></div>
            <div class="invoice-sheet">
                <header class="invoice-header"><div><span class="invoice-brand">TECHSTORE</span><h1>Hóa đơn bán hàng</h1><p>Mã đơn: <strong>{{ selectedOrder.code }}</strong></p></div><div class="invoice-date"><span>Ngày đặt</span><strong>{{ formatDate(selectedOrder.createdAt) }}</strong></div></header>
                <div class="invoice-info-grid"><div><small>Thông tin người nhận</small><strong>{{ selectedOrder.customer?.name || 'Khách hàng TechStore' }}</strong><span>{{ selectedOrder.customer?.phone || '—' }}</span><span>{{ selectedOrder.customer?.email || '—' }}</span></div><div><small>Địa chỉ giao hàng</small><strong>{{ selectedOrder.customer?.address || '—' }}</strong><span>Thanh toán: {{ paymentLabel(selectedOrder) }}</span></div></div>
                <div class="invoice-table-wrap"><table class="invoice-table"><thead><tr><th>Sản phẩm</th><th>SL</th><th>Đơn giá</th><th>Thành tiền</th></tr></thead><tbody><tr v-for="item in selectedOrder.items" :key="`${selectedOrder.code}-${item.id}`"><td>{{ item.name }}</td><td>{{ item.quantity }}</td><td>{{ formatPrice(item.price) }}</td><td>{{ formatPrice(item.price * item.quantity) }}</td></tr></tbody></table></div>
                <div class="invoice-total"><div><span>Tạm tính</span><strong>{{ formatPrice(selectedOrder.subtotal) }}</strong></div><div><span>Phí vận chuyển</span><strong>{{ formatPrice(selectedOrder.totalShipping) }}</strong></div><div class="grand"><span>Tổng thanh toán</span><strong>{{ formatPrice(selectedOrder.total) }}</strong></div></div>
                <div class="invoice-status"><span>Trạng thái đơn hàng</span><strong :class="statusMeta[selectedOrder.status].className">{{ selectedOrder.status }}</strong></div>
                <div v-if="selectedOrder.payment === 'paypal-sandbox'" class="invoice-payment-note"><i class="bi bi-shield-check"/><span>Thanh toán được xác nhận qua <strong>PayPal Sandbox</strong>. Đây là môi trường thử nghiệm, không chuyển tiền thật.</span></div>
                <p class="invoice-note">Cảm ơn bạn đã mua sắm tại TechStore.</p>
            </div>
            <div class="invoice-actions no-print"><button type="button" class="secondary-invoice-btn" @click="selectedOrder = null">Đóng</button><button type="button" class="primary-invoice-btn" @click="printInvoice"><i class="bi bi-printer"/> In hóa đơn</button></div>
        </section>
    </div>
</template>

<style>
.orders-dashboard{display:grid;gap:18px;color:#101828}.orders-page-header{display:flex;align-items:center;justify-content:space-between;gap:18px}.orders-title-wrap{display:flex;align-items:center;gap:14px}.orders-title-icon{display:grid;width:48px;height:48px;flex:0 0 48px;place-items:center;border:1px solid #dbeafe;border-radius:15px;color:#2563eb;background:linear-gradient(145deg,#eff6ff,#f5f3ff);font-size:1.1rem}.orders-kicker{display:block;margin-bottom:3px;color:#2563eb;font-size:.57rem;font-weight:950;letter-spacing:.15em}.orders-page-header h2{margin:0;font-size:1.65rem;font-weight:900}.orders-page-header p{margin:5px 0 0;color:#667085;font-size:.72rem}.orders-summary-badge{display:flex;align-items:center;gap:7px;padding:9px 12px;border:1px solid #e4e7ec;border-radius:11px;color:#667085;background:#fff;font-size:.65rem}.orders-summary-badge i{color:#2563eb}.orders-summary-badge strong{color:#101828;font-size:.9rem}.orders-stat-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.orders-stat-card{display:flex;align-items:center;gap:11px;padding:15px;border:1px solid #e4e7ec;border-radius:15px;background:#fff;box-shadow:0 6px 22px rgba(16,24,40,.035)}.stat-icon{display:grid;width:38px;height:38px;flex:0 0 38px;place-items:center;border-radius:11px}.stat-icon.blue{color:#2563eb;background:#eff6ff}.stat-icon.orange{color:#c2410c;background:#fff7ed}.stat-icon.green{color:#047857;background:#ecfdf3}.stat-icon.purple{color:#7c3aed;background:#f5f3ff}.orders-stat-card div{display:grid;min-width:0}.orders-stat-card small{color:#667085;font-size:.58rem}.orders-stat-card strong{margin-top:2px;font-size:.95rem;font-weight:900}.orders-stat-card span{margin-top:2px;color:#98a2b3;font-size:.53rem}.orders-history-panel{border:1px solid #e4e7ec;border-radius:18px;background:#fff;box-shadow:0 8px 28px rgba(16,24,40,.045)}.history-panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:20px 22px 14px}.history-panel-head h3{margin:0;font-size:.95rem;font-weight:850}.history-panel-head p{margin:4px 0 0;color:#667085;font-size:.65rem}.history-panel-head>span{padding:7px 9px;border-radius:9px;color:#067647;background:#ecfdf3;font-size:.6rem;font-weight:800}.order-status-tabs{display:flex;gap:6px;overflow:auto;padding:0 22px 14px;border-bottom:1px solid #edf0f3}.order-status-tabs button{display:inline-flex;align-items:center;gap:5px;white-space:nowrap;padding:8px 10px;border:1px solid #e4e7ec;border-radius:9px;color:#667085;background:#fff;font-size:.62rem;font-weight:800;cursor:pointer}.order-status-tabs button.active{border-color:#bfdbfe;color:#1d4ed8;background:#eff6ff}.order-status-tabs b{padding:2px 5px;border-radius:99px;background:#f2f4f7;font-size:.54rem}.orders-inline-error{display:flex;gap:7px;padding:9px 11px;border:1px solid #fecaca;border-radius:10px;color:#b42318;background:#fff7f7;font-size:.62rem}.orders-list{display:grid;gap:12px;padding:16px}.order-history-card{border:1px solid #e4e7ec;border-radius:14px;background:#fff;overflow:hidden}.order-history-head,.order-history-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 14px}.order-history-head{border-bottom:1px solid #f0f2f5}.order-heading-left{display:flex;align-items:center;gap:9px}.order-receipt-icon{display:grid;width:34px;height:34px;place-items:center;border-radius:9px;color:#2563eb;background:#eff6ff}.order-code{display:block;font-size:.7rem;font-weight:900}.order-heading-left small{display:block;margin-top:2px;color:#98a2b3;font-size:.58rem}.order-status{display:inline-flex;align-items:center;gap:5px;padding:5px 8px;border-radius:999px;font-size:.58rem;font-weight:850}.order-status.is-pending{color:#b54708;background:#fffaeb}.order-status.is-shipping{color:#175cd3;background:#eff8ff}.order-status.is-delivered{color:#067647;background:#ecfdf3}.order-status.is-returned{color:#6941c6;background:#f9f5ff}.order-status.is-cancelled{color:#b42318;background:#fef3f2}.order-history-body{display:flex;align-items:center;gap:15px;padding:14px}.order-product-preview{display:flex;gap:6px}.order-thumb,.order-more{position:relative;display:grid;width:50px;height:50px;place-items:center;overflow:hidden;border:1px solid #edf0f3;border-radius:9px;background:#f8fafc}.order-thumb img{width:100%;height:100%;object-fit:cover}.order-thumb span{position:absolute;right:2px;bottom:2px;padding:2px 4px;border-radius:4px;color:#fff;background:#111827;font-size:.5rem;font-weight:800}.order-more{color:#667085;font-size:.65rem;font-weight:850}.order-history-summary{display:grid;min-width:0;flex:1}.order-history-summary span,.order-history-summary small{color:#667085;font-size:.6rem}.order-history-summary strong{margin:3px 0;color:#101828;font-size:.86rem}.order-history-foot{border-top:1px solid #f0f2f5}.invoice-meta{display:flex;align-items:center;gap:7px;color:#667085;font-size:.65rem;font-weight:800}.invoice-meta i{color:#2563eb}.invoice-meta small{display:block;margin-top:2px;color:#98a2b3;font-size:.52rem}.order-actions{display:flex;gap:6px}.invoice-btn,.danger-outline-btn,.return-outline-btn{border:1px solid #dfe3e8;border-radius:8px;padding:7px 9px;background:#fff;font-size:.6rem;font-weight:800;cursor:pointer}.invoice-btn{color:#2563eb;border-color:#bfdbfe;background:#f8fbff}.danger-outline-btn{color:#b42318}.return-outline-btn{color:#6941c6}.orders-empty{text-align:center;padding:50px 20px}.orders-empty-icon{display:grid;width:52px;height:52px;margin:0 auto 12px;place-items:center;border-radius:15px;color:#2563eb;background:#eff6ff;font-size:1.1rem}.orders-empty h3{margin:0;font-size:.9rem}.orders-empty p{margin:5px 0;color:#667085;font-size:.65rem}
.order-confirm-overlay{position:fixed;inset:0;z-index:2500;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(15,23,42,.56);backdrop-filter:blur(3px)}.order-confirm-modal{width:min(430px,100%);padding:24px;border:1px solid #e4e7ec;border-radius:20px;background:#fff;box-shadow:0 25px 80px rgba(15,23,42,.25)}.order-confirm-icon{display:grid;width:48px;height:48px;margin-bottom:15px;place-items:center;border-radius:14px;color:#b42318;background:#fef3f2;font-size:1.05rem}.order-confirm-kicker{display:block;color:#b42318;font-size:.57rem;font-weight:950;letter-spacing:.14em}.order-confirm-content h3{margin:4px 0 8px;color:#101828;font-size:1.08rem;font-weight:900}.order-confirm-content p{margin:0;color:#667085;font-size:.7rem;line-height:1.65}.order-confirm-content strong{color:#344054}.order-confirm-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:20px}.confirm-keep-btn,.confirm-cancel-btn{border:1px solid #d0d5dd;border-radius:9px;padding:9px 12px;font-size:.65rem;font-weight:850;cursor:pointer}.confirm-keep-btn{color:#344054;background:#fff}.confirm-cancel-btn{border-color:#b42318;color:#fff;background:#b42318}
.invoice-overlay{position:fixed;inset:0;z-index:2000;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(15,23,42,.58)}.invoice-modal{width:min(820px,100%);max-height:calc(100vh - 40px);display:flex;flex-direction:column;overflow:hidden;border-radius:16px;background:#fff;box-shadow:0 25px 70px rgba(0,0,0,.25)}.invoice-toolbar{display:flex;align-items:center;justify-content:space-between;padding:11px 15px;color:#fff;background:#101828}.invoice-toolbar div{display:grid}.invoice-toolbar span{font-size:.55rem;font-weight:900;letter-spacing:.15em;color:#93c5fd}.invoice-toolbar strong{margin-top:2px;font-size:.75rem}.invoice-toolbar button{border:0;color:#fff;background:transparent;cursor:pointer}.invoice-sheet{padding:22px 28px;background:#fff}.invoice-header{display:flex;justify-content:space-between;gap:20px;padding-bottom:14px;border-bottom:2px solid #101828}.invoice-brand{color:#2563eb;font-size:.68rem;font-weight:950;letter-spacing:.16em}.invoice-header h1{margin:4px 0;font-size:1.25rem;font-weight:900}.invoice-header p,.invoice-date span,.invoice-date strong{font-size:.62rem}.invoice-header p{margin:0;color:#667085}.invoice-date{display:grid;align-content:center;text-align:right;gap:3px}.invoice-date span{color:#98a2b3}.invoice-info-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;padding:14px 0}.invoice-info-grid div{display:grid;gap:3px}.invoice-info-grid small{color:#98a2b3;font-size:.56rem;font-weight:850;text-transform:uppercase}.invoice-info-grid strong,.invoice-info-grid span{font-size:.63rem}.invoice-info-grid span{color:#667085}.invoice-table-wrap{overflow:visible}.invoice-table{width:100%;border-collapse:collapse;table-layout:fixed}.invoice-table th,.invoice-table td{padding:7px 6px;border-bottom:1px solid #e4e7ec;text-align:left;font-size:.58rem}.invoice-table th{color:#667085;background:#f8fafc;font-size:.55rem;text-transform:uppercase}.invoice-table th:nth-child(2),.invoice-table td:nth-child(2){width:45px;text-align:center}.invoice-table th:nth-child(3),.invoice-table td:nth-child(3),.invoice-table th:nth-child(4),.invoice-table td:nth-child(4){width:115px;text-align:right}.invoice-total{display:grid;gap:5px;width:290px;margin:12px 0 0 auto}.invoice-total div{display:flex;justify-content:space-between;font-size:.61rem}.invoice-total .grand{margin-top:3px;padding-top:8px;border-top:2px solid #101828;font-size:.72rem;font-weight:900}.invoice-status{display:flex;justify-content:space-between;margin-top:12px;padding:8px 10px;border-radius:8px;background:#f8fafc;font-size:.61rem}.invoice-status strong{font-weight:900}.invoice-payment-note{display:flex;align-items:center;gap:7px;margin-top:10px;padding:8px 10px;border-radius:8px;color:#067647;background:#ecfdf3;font-size:.56rem}.invoice-note{margin:12px 0 0;color:#98a2b3;text-align:center;font-size:.55rem}.invoice-actions{display:flex;justify-content:flex-end;gap:7px;padding:10px 15px;border-top:1px solid #e4e7ec;background:#fff}.secondary-invoice-btn,.primary-invoice-btn{border:0;border-radius:8px;padding:8px 12px;font-size:.62rem;font-weight:850;cursor:pointer}.secondary-invoice-btn{color:#344054;background:#f2f4f7}.primary-invoice-btn{color:#fff;background:#2563eb}
@media(max-width:900px){.orders-stat-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:620px){.orders-page-header,.history-panel-head,.order-history-foot{align-items:flex-start;flex-direction:column}.orders-stat-grid{grid-template-columns:1fr}.order-history-body{align-items:flex-start;flex-direction:column}.order-actions{width:100%}.order-actions button{flex:1}.invoice-sheet{padding:16px}.invoice-info-grid{grid-template-columns:1fr}.invoice-total{width:100%}.order-confirm-modal{padding:20px}.order-confirm-actions{flex-direction:column}.confirm-keep-btn,.confirm-cancel-btn{width:100%}}
@media print{
  @page{size:A4 portrait;margin:8mm}
  html,body{width:210mm!important;min-height:100%!important;background:#fff!important}
  body *{visibility:hidden!important}
  .invoice-overlay,.invoice-overlay *{visibility:visible!important}
  .invoice-overlay{position:absolute!important;inset:0!important;display:block!important;padding:0!important;background:#fff!important}
  .invoice-modal{position:static!important;width:100%!important;max-width:none!important;max-height:none!important;overflow:visible!important;border:0!important;border-radius:0!important;box-shadow:none!important}
  .invoice-toolbar,.invoice-actions,.no-print,.order-confirm-overlay{display:none!important}
  .invoice-sheet{display:block!important;padding:0!important;width:100%!important}
  .invoice-header{padding-bottom:8px!important}
  .invoice-header h1{font-size:16pt!important;margin:2px 0!important}
  .invoice-info-grid{padding:8px 0!important;gap:10px!important}
  .invoice-table th,.invoice-table td{padding:4px!important;font-size:8pt!important}
  .invoice-total{margin-top:7px!important;gap:3px!important;width:270px!important}
  .invoice-status,.invoice-payment-note{margin-top:7px!important;padding:5px 7px!important}
  .invoice-note{margin-top:7px!important}
  .invoice-table tr{break-inside:avoid!important;page-break-inside:avoid!important}
}
</style>
