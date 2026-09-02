<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { formatPrice } from '@/data/products';

type OrderStatus = 'Chờ xử lý' | 'Đang giao' | 'Đã giao' | 'Trả hàng' | 'Hủy hàng';
type VatInvoice = {
    requested: boolean;
    companyName?: string | null;
    taxCode?: string | null;
    address?: string | null;
    email?: string | null;
    rate?: number;
    amount?: number;
};
type TechStoreOrder = {
    id: number;
    code: string;
    createdAt: string;
    customer?: { name?: string; phone?: string; email?: string; address?: string; note?: string };
    vatInvoice?: VatInvoice;
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

type InvoiceMode = 'sale' | 'vat';

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
const invoiceMode = ref<InvoiceMode>('sale');

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
const vatAvailable = computed(() => Boolean(selectedOrder.value?.vatInvoice?.requested));
const invoiceTaxRate = computed(() => Number(selectedOrder.value?.vatInvoice?.rate ?? 10));
const invoiceVatAmount = computed(() => {
    if (!selectedOrder.value?.vatInvoice?.requested) return 0;
    return Number(selectedOrder.value.vatInvoice.amount ?? Math.round((selectedOrder.value.subtotal * invoiceTaxRate.value) / (100 + invoiceTaxRate.value)));
});
const invoiceNetAmount = computed(() => Math.max(0, (selectedOrder.value?.subtotal ?? 0) - invoiceVatAmount.value));
const csrfToken = () => document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

async function loadOrders(): Promise<void> {
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

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('vi-VN', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}

function paymentLabel(order: TechStoreOrder): string {
    if (order.payment === 'paypal-sandbox') return 'PayPal Sandbox';
    if (order.payment === 'paypal-demo') return 'PayPal mô phỏng';
    return 'Thanh toán khi nhận hàng';
}

function openCancelModal(order: TechStoreOrder): void {
    if (order.status !== 'Chờ xử lý') return;
    cancelTarget.value = order;
    actionError.value = '';
}

function closeCancelModal(): void {
    cancelTarget.value = null;
}

function openInvoice(order: TechStoreOrder): void {
    selectedOrder.value = order;
    invoiceMode.value = 'sale';
}

function closeInvoice(): void {
    selectedOrder.value = null;
}

function printInvoice(mode: InvoiceMode): void {
    if (!selectedOrder.value) return;
    if (mode === 'vat' && !vatAvailable.value) return;
    invoiceMode.value = mode;
    requestAnimationFrame(() => window.print());
}

function resetPrintMode(): void {
    invoiceMode.value = 'sale';
}

async function confirmCancelOrder(): Promise<void> {
    if (!cancelTarget.value) return;
    const order = cancelTarget.value;
    try {
        const response = await fetch(`/orders/${order.id}/cancel`, {
            method: 'PATCH',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
        });
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

async function requestReturn(order: TechStoreOrder): Promise<void> {
    if (order.status !== 'Đã giao' || !window.confirm(`Gửi yêu cầu trả hàng cho đơn ${order.code}?`)) return;
    try {
        const response = await fetch(`/orders/${order.id}/return`, {
            method: 'PATCH',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.order) throw new Error(data.message ?? 'Không thể gửi yêu cầu trả hàng.');

        const index = orders.value.findIndex((item) => item.id === order.id);
        if (index >= 0) orders.value[index] = data.order;
        if (selectedOrder.value?.id === order.id) selectedOrder.value = data.order;
    } catch (error) {
        actionError.value = error instanceof Error ? error.message : 'Không thể gửi yêu cầu trả hàng.';
    }
}

onMounted(() => {
    void loadOrders();
    window.addEventListener('afterprint', resetPrintMode);
});

onBeforeUnmount(() => {
    window.removeEventListener('afterprint', resetPrintMode);
});
</script>

<template>
    <section class="orders-dashboard">
        <header class="orders-page-header">
            <div class="orders-title-wrap"><span class="orders-title-icon"><i class="bi bi-bag-check" /></span><div><span class="orders-kicker">TECHSTORE · TÀI KHOẢN</span><h2>Đơn hàng</h2><p>Lịch sử mua hàng, trạng thái giao nhận và hóa đơn.</p></div></div>
            <div class="orders-summary-badge"><i class="bi bi-receipt"/><strong>{{ orders.length }}</strong><span>đơn hàng</span></div>
        </header>

        <div v-if="actionError" class="orders-inline-error"><i class="bi bi-exclamation-circle"/> {{ actionError }}</div>

        <div class="orders-stat-grid">
            <article class="orders-stat-card"><span class="stat-icon blue"><i class="bi bi-receipt-cutoff"/></span><div><small>Tổng đơn hàng</small><strong>{{ orders.length }}</strong><span>Tất cả đơn đã tạo</span></div></article>
            <article class="orders-stat-card"><span class="stat-icon orange"><i class="bi bi-truck"/></span><div><small>Đang xử lý</small><strong>{{ activeCount }}</strong><span>Chờ xử lý hoặc đang giao</span></div></article>
            <article class="orders-stat-card"><span class="stat-icon green"><i class="bi bi-check2-circle"/></span><div><small>Đã giao</small><strong>{{ deliveredCount }}</strong><span>Giao thành công</span></div></article>
            <article class="orders-stat-card"><span class="stat-icon purple"><i class="bi bi-cash-stack"/></span><div><small>Giá trị mua hàng</small><strong>{{ formatPrice(totalSpent) }}</strong><span>Không tính đơn đã hủy</span></div></article>
        </div>

        <section class="orders-history-panel">
            <div class="history-panel-head"><div><h3>Lịch sử mua hàng</h3><p>Dữ liệu được tải trực tiếp từ tài khoản của bạn.</p></div><span><i class="bi bi-database-check"/> Dữ liệu hệ thống</span></div>
            <div class="order-status-tabs"><button v-for="item in statusOptions" :key="item.key" type="button" :class="{ active: filter === item.key }" @click="filter = item.key"><i :class="['bi', item.icon]"/> {{ item.label }}<b v-if="item.key !== 'all'">{{ orders.filter((order) => order.status === item.key).length }}</b></button></div>

            <div v-if="loading" class="orders-empty"><div class="orders-empty-icon"><i class="bi bi-arrow-repeat"/></div><h3>Đang tải lịch sử mua hàng</h3><p>Đang đồng bộ dữ liệu từ tài khoản của bạn.</p></div>
            <div v-else-if="filteredOrders.length" class="orders-list">
                <article v-for="order in filteredOrders" :key="order.code" class="order-history-card">
                    <div class="order-history-head"><div class="order-heading-left"><div class="order-receipt-icon"><i class="bi bi-receipt"/></div><div><span class="order-code">#{{ order.code }}</span><small>{{ formatDate(order.createdAt) }}</small></div></div><span class="order-status" :class="statusMeta[order.status].className"><i :class="['bi', statusMeta[order.status].icon]"/>{{ order.status }}</span></div>
                    <div class="order-history-body"><div class="order-product-preview"><div v-for="item in order.items.slice(0,4)" :key="`${order.code}-${item.id}`" class="order-thumb"><img :src="item.image" :alt="item.name"/><span v-if="item.quantity > 1">×{{ item.quantity }}</span></div><div v-if="order.items.length > 4" class="order-more">+{{ order.items.length - 4 }}</div></div><div class="order-history-summary"><span>{{ order.items.reduce((sum,item)=>sum+item.quantity,0) }} sản phẩm · {{ paymentLabel(order) }}</span><strong>{{ formatPrice(order.total) }}</strong><small>{{ statusMeta[order.status].description }}</small></div></div>
                    <div class="order-history-foot"><div class="invoice-meta"><i class="bi bi-file-earmark-text"/><span>Hóa đơn <small>{{ order.vatInvoice?.requested ? 'Có yêu cầu VAT' : 'Hóa đơn bán hàng' }}</small></span></div><div class="order-actions"><button type="button" class="invoice-btn" @click="openInvoice(order)"><i class="bi bi-receipt-cutoff"/> Xem hóa đơn</button><button v-if="order.status === 'Chờ xử lý'" type="button" class="danger-outline-btn" @click="openCancelModal(order)"><i class="bi bi-x-lg"/> Hủy hàng</button><button v-if="order.status === 'Đã giao'" type="button" class="return-outline-btn" @click="requestReturn(order)"><i class="bi bi-arrow-return-left"/> Trả hàng</button></div></div>
                </article>
            </div>
            <div v-else class="orders-empty"><div class="orders-empty-icon"><i class="bi bi-bag-x"/></div><h3>{{ filter === 'all' ? 'Chưa có lịch sử mua hàng' : 'Không có đơn hàng phù hợp' }}</h3><p>{{ filter === 'all' ? 'Đơn hàng sau khi đặt sẽ xuất hiện tại đây.' : 'Hãy chọn trạng thái khác để xem đơn hàng.' }}</p></div>
        </section>
    </section>

    <div v-if="cancelTarget" class="order-confirm-overlay" @click.self="closeCancelModal">
        <section class="order-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="cancel-order-title">
            <div class="order-confirm-icon"><i class="bi bi-exclamation-triangle-fill"/></div>
            <span class="order-confirm-kicker">XÁC NHẬN HỦY ĐƠN</span><h3 id="cancel-order-title">Bạn muốn hủy đơn hàng?</h3><p>Đơn <strong>#{{ cancelTarget.code }}</strong> đang ở trạng thái <strong>Chờ xử lý</strong>. Sau khi hủy, đơn hàng sẽ chuyển sang <strong>Hủy hàng</strong>.</p>
            <div class="order-confirm-actions"><button type="button" class="confirm-keep-btn" @click="closeCancelModal">Giữ lại đơn</button><button type="button" class="confirm-cancel-btn" @click="confirmCancelOrder"><i class="bi bi-x-lg"/> Xác nhận hủy</button></div>
        </section>
    </div>

    <Teleport to="body">
        <div v-if="selectedOrder" class="invoice-print-root" @click.self="closeInvoice">
            <section class="invoice-modal" role="dialog" aria-modal="true" aria-labelledby="invoice-title">
                <div class="invoice-toolbar no-print"><div><span>TECHSTORE</span><strong>{{ invoiceMode === 'vat' ? 'HÓA ĐƠN GIÁ TRỊ GIA TĂNG' : 'HÓA ĐƠN BÁN HÀNG' }}</strong></div><button type="button" aria-label="Đóng" @click="closeInvoice"><i class="bi bi-x-lg"/></button></div>

                <div class="invoice-sheet">
                    <div class="invoice-watermark" aria-hidden="true">TECHSTORE</div>
                    <header class="invoice-topline">
                        <div class="seller-block"><span class="invoice-label">ĐƠN VỊ BÁN HÀNG</span><h2>TECHSTORE</h2><p>Nền tảng bán lẻ máy tính xách tay và phụ kiện</p><p>Website: TechStore</p></div>
                        <div class="invoice-title-block"><span class="invoice-label">{{ invoiceMode === 'vat' ? 'HÓA ĐƠN GIÁ TRỊ GIA TĂNG' : 'HÓA ĐƠN BÁN HÀNG' }}</span><h1 id="invoice-title">{{ invoiceMode === 'vat' ? 'HÓA ĐƠN GIÁ TRỊ GIA TĂNG' : 'HÓA ĐƠN BÁN HÀNG' }}</h1><span class="preview-badge">BẢN XEM TRƯỚC</span></div>
                    </header>

                    <div class="invoice-meta-grid"><div><span>Ngày lập</span><strong>{{ formatDate(selectedOrder.createdAt) }}</strong></div><div><span>Mã đơn hàng</span><strong>{{ selectedOrder.code }}</strong></div><div><span>Phương thức thanh toán</span><strong>{{ paymentLabel(selectedOrder) }}</strong></div><div><span>Trạng thái</span><strong>{{ selectedOrder.status }}</strong></div></div>

                    <section class="invoice-party-grid">
                        <div class="invoice-party"><h3>THÔNG TIN NGƯỜI MUA</h3><p><strong>{{ invoiceMode === 'vat' && selectedOrder.vatInvoice?.companyName ? selectedOrder.vatInvoice.companyName : (selectedOrder.customer?.name || 'Khách hàng TechStore') }}</strong></p><p v-if="invoiceMode === 'vat' && selectedOrder.vatInvoice?.taxCode"><span>Mã số thuế:</span> {{ selectedOrder.vatInvoice.taxCode }}</p><p v-if="invoiceMode === 'vat' && selectedOrder.vatInvoice?.address"><span>Địa chỉ xuất hóa đơn:</span> {{ selectedOrder.vatInvoice.address }}</p><p v-else><span>Địa chỉ giao hàng:</span> {{ selectedOrder.customer?.address || '—' }}</p><p><span>Điện thoại:</span> {{ selectedOrder.customer?.phone || '—' }}</p><p><span>Email:</span> {{ invoiceMode === 'vat' ? (selectedOrder.vatInvoice?.email || selectedOrder.customer?.email || '—') : (selectedOrder.customer?.email || '—') }}</p></div>
                        <div class="invoice-party"><h3>THÔNG TIN GIAO HÀNG</h3><p><strong>{{ selectedOrder.customer?.name || 'Khách hàng TechStore' }}</strong></p><p><span>Địa chỉ:</span> {{ selectedOrder.customer?.address || '—' }}</p><p><span>Điện thoại:</span> {{ selectedOrder.customer?.phone || '—' }}</p><p v-if="selectedOrder.customer?.note"><span>Ghi chú:</span> {{ selectedOrder.customer.note }}</p></div>
                    </section>

                    <table class="invoice-table"><thead><tr><th class="stt">STT</th><th>Tên hàng hóa, dịch vụ</th><th class="unit">ĐVT</th><th class="qty">Số lượng</th><th class="money">Đơn giá</th><th class="money">Thành tiền</th></tr></thead><tbody><tr v-for="(item,index) in selectedOrder.items" :key="`${selectedOrder.code}-${item.id}`"><td class="stt">{{ index + 1 }}</td><td><strong>{{ item.name }}</strong></td><td class="unit">Cái</td><td class="qty">{{ item.quantity }}</td><td class="money">{{ formatPrice(item.price) }}</td><td class="money">{{ formatPrice(item.price * item.quantity) }}</td></tr></tbody></table>

                    <section class="invoice-summary-area">
                        <div class="invoice-signature-box"><span>Người mua hàng</span><small>(Ký, ghi rõ họ tên)</small></div>
                        <div class="invoice-total-box">
                            <div><span>Cộng tiền hàng</span><strong>{{ formatPrice(selectedOrder.subtotal) }}</strong></div>
                            <div v-if="invoiceMode === 'vat'"><span>Giá tính thuế</span><strong>{{ formatPrice(invoiceNetAmount) }}</strong></div>
                            <div v-if="invoiceMode === 'vat'"><span>Thuế GTGT ({{ invoiceTaxRate.toFixed(0) }}%)</span><strong>{{ formatPrice(invoiceVatAmount) }}</strong></div>
                            <div><span>Phí vận chuyển</span><strong>{{ formatPrice(selectedOrder.totalShipping) }}</strong></div>
                            <div class="invoice-grand-total"><span>TỔNG CỘNG THANH TOÁN</span><strong>{{ formatPrice(selectedOrder.total) }}</strong></div>
                        </div>
                    </section>

                    <p class="invoice-amount-note">Số tiền bằng chữ: <strong>{{ formatPrice(selectedOrder.total) }}</strong></p>
                    <div v-if="invoiceMode === 'vat'" class="invoice-vat-notice"><i class="bi bi-info-circle-fill"/><span>Phiên bản này mô phỏng bố cục hóa đơn VAT để in/kiểm thử. Việc phát hành hóa đơn điện tử có giá trị pháp lý cần được thực hiện qua hệ thống hóa đơn điện tử đáp ứng quy định.</span></div>
                    <p class="invoice-footer-note">Cảm ơn Quý khách đã mua sắm tại TechStore.</p>
                </div>

                <div class="invoice-actions no-print"><button type="button" class="secondary-invoice-btn" @click="closeInvoice">Đóng</button><button type="button" class="sale-invoice-btn" @click="printInvoice('sale')"><i class="bi bi-printer"/> In hóa đơn</button><button type="button" class="vat-invoice-btn" :disabled="!vatAvailable" :title="vatAvailable ? 'In hóa đơn VAT' : 'Đơn hàng không yêu cầu xuất hóa đơn VAT'" @click="printInvoice('vat')"><i class="bi bi-receipt-cutoff"/> In hóa đơn VAT</button></div>
            </section>
        </div>
    </Teleport>
</template>

<style>
.orders-dashboard{display:grid;gap:18px;color:#101828}.orders-page-header{display:flex;align-items:center;justify-content:space-between;gap:18px}.orders-title-wrap{display:flex;align-items:center;gap:14px}.orders-title-icon{display:grid;width:48px;height:48px;flex:0 0 48px;place-items:center;border:1px solid #dbeafe;border-radius:15px;color:#2563eb;background:linear-gradient(145deg,#eff6ff,#f5f3ff);font-size:1.1rem}.orders-kicker{display:block;margin-bottom:3px;color:#2563eb;font-size:.57rem;font-weight:950;letter-spacing:.15em}.orders-page-header h2{margin:0;font-size:1.65rem;font-weight:900}.orders-page-header p{margin:5px 0 0;color:#667085;font-size:.72rem}.orders-summary-badge{display:flex;align-items:center;gap:7px;padding:9px 12px;border:1px solid #e4e7ec;border-radius:11px;color:#667085;background:#fff;font-size:.65rem}.orders-summary-badge i{color:#2563eb}.orders-summary-badge strong{color:#101828;font-size:.9rem}.orders-stat-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.orders-stat-card{display:flex;align-items:center;gap:11px;padding:15px;border:1px solid #e4e7ec;border-radius:15px;background:#fff;box-shadow:0 6px 22px rgba(16,24,40,.035)}.stat-icon{display:grid;width:38px;height:38px;flex:0 0 38px;place-items:center;border-radius:11px}.stat-icon.blue{color:#2563eb;background:#eff6ff}.stat-icon.orange{color:#c2410c;background:#fff7ed}.stat-icon.green{color:#047857;background:#ecfdf3}.stat-icon.purple{color:#7c3aed;background:#f5f3ff}.orders-stat-card div{display:grid;min-width:0}.orders-stat-card small{color:#667085;font-size:.58rem}.orders-stat-card strong{margin-top:2px;font-size:.95rem;font-weight:900}.orders-stat-card span{margin-top:2px;color:#98a2b3;font-size:.53rem}.orders-history-panel{border:1px solid #e4e7ec;border-radius:18px;background:#fff;box-shadow:0 8px 28px rgba(16,24,40,.045)}.history-panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:20px 22px 14px}.history-panel-head h3{margin:0;font-size:.95rem;font-weight:850}.history-panel-head p{margin:4px 0 0;color:#667085;font-size:.65rem}.history-panel-head>span{padding:7px 9px;border-radius:9px;color:#067647;background:#ecfdf3;font-size:.6rem;font-weight:800}.order-status-tabs{display:flex;gap:6px;overflow:auto;padding:0 22px 14px;border-bottom:1px solid #edf0f3}.order-status-tabs button{display:inline-flex;align-items:center;gap:5px;white-space:nowrap;padding:8px 10px;border:1px solid #e4e7ec;border-radius:9px;color:#667085;background:#fff;font-size:.62rem;font-weight:800;cursor:pointer}.order-status-tabs button.active{border-color:#bfdbfe;color:#1d4ed8;background:#eff6ff}.order-status-tabs b{padding:2px 5px;border-radius:99px;background:#f2f4f7;font-size:.54rem}.orders-inline-error{display:flex;gap:7px;padding:9px 11px;border:1px solid #fecaca;border-radius:10px;color:#b42318;background:#fff7f7;font-size:.62rem}.orders-list{display:grid;gap:12px;padding:16px}.order-history-card{border:1px solid #e4e7ec;border-radius:14px;background:#fff;overflow:hidden}.order-history-head,.order-history-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 14px}.order-history-head{border-bottom:1px solid #f0f2f5}.order-heading-left{display:flex;align-items:center;gap:9px}.order-receipt-icon{display:grid;width:34px;height:34px;place-items:center;border-radius:9px;color:#2563eb;background:#eff6ff}.order-code{display:block;font-size:.7rem;font-weight:900}.order-heading-left small{display:block;margin-top:2px;color:#98a2b3;font-size:.58rem}.order-status{display:inline-flex;align-items:center;gap:5px;padding:5px 8px;border-radius:999px;font-size:.58rem;font-weight:850}.order-status.is-pending{color:#b54708;background:#fffaeb}.order-status.is-shipping{color:#175cd3;background:#eff8ff}.order-status.is-delivered{color:#067647;background:#ecfdf3}.order-status.is-returned{color:#6941c6;background:#f9f5ff}.order-status.is-cancelled{color:#b42318;background:#fef3f2}.order-history-body{display:flex;align-items:center;gap:15px;padding:14px}.order-product-preview{display:flex;gap:6px}.order-thumb,.order-more{position:relative;display:grid;width:50px;height:50px;place-items:center;overflow:hidden;border:1px solid #edf0f3;border-radius:9px;background:#f8fafc}.order-thumb img{width:100%;height:100%;object-fit:cover}.order-thumb span{position:absolute;right:2px;bottom:2px;padding:2px 4px;border-radius:4px;color:#fff;background:#111827;font-size:.5rem;font-weight:800}.order-more{color:#667085;font-size:.65rem;font-weight:850}.order-history-summary{display:grid;min-width:0;flex:1}.order-history-summary span,.order-history-summary small{color:#667085;font-size:.6rem}.order-history-summary strong{margin:3px 0;color:#101828;font-size:.86rem}.order-history-foot{border-top:1px solid #f0f2f5}.invoice-meta{display:flex;align-items:center;gap:7px;color:#667085;font-size:.65rem;font-weight:800}.invoice-meta i{color:#2563eb}.invoice-meta small{display:block;margin-top:2px;color:#98a2b3;font-size:.52rem}.order-actions{display:flex;gap:6px}.invoice-btn,.danger-outline-btn,.return-outline-btn{border:1px solid #dfe3e8;border-radius:8px;padding:7px 9px;background:#fff;font-size:.6rem;font-weight:800;cursor:pointer}.invoice-btn{color:#2563eb;border-color:#bfdbfe;background:#f8fbff}.danger-outline-btn{color:#b42318}.return-outline-btn{color:#6941c6}.orders-empty{text-align:center;padding:50px 20px}.orders-empty-icon{display:grid;width:52px;height:52px;margin:0 auto 12px;place-items:center;border-radius:15px;color:#2563eb;background:#eff6ff;font-size:1.1rem}.orders-empty h3{margin:0;font-size:.9rem}.orders-empty p{margin:5px 0;color:#667085;font-size:.65rem}
.order-confirm-overlay{position:fixed;inset:0;z-index:2500;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(15,23,42,.56);backdrop-filter:blur(3px)}.order-confirm-modal{width:min(430px,100%);padding:24px;border:1px solid #e4e7ec;border-radius:20px;background:#fff;box-shadow:0 25px 80px rgba(15,23,42,.25)}.order-confirm-icon{display:grid;width:48px;height:48px;margin-bottom:14px;place-items:center;border-radius:14px;color:#b42318;background:#fef3f2;font-size:1.05rem}.order-confirm-kicker{display:block;color:#b42318;font-size:.57rem;font-weight:950;letter-spacing:.14em}.order-confirm-modal h3{margin:4px 0 8px;color:#101828;font-size:1.08rem;font-weight:900}.order-confirm-modal p{margin:0;color:#667085;font-size:.7rem;line-height:1.65}.order-confirm-modal strong{color:#344054}.order-confirm-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:20px}.confirm-keep-btn,.confirm-cancel-btn{border:1px solid #d0d5dd;border-radius:9px;padding:9px 12px;font-size:.65rem;font-weight:850;cursor:pointer}.confirm-keep-btn{color:#344054;background:#fff}.confirm-cancel-btn{border-color:#b42318;color:#fff;background:#b42318}
.invoice-print-root{position:fixed;inset:0;z-index:3000;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(15,23,42,.62)}.invoice-modal{width:min(900px,100%);max-height:calc(100vh - 40px);display:flex;flex-direction:column;overflow:hidden;border-radius:18px;background:#fff;box-shadow:0 28px 90px rgba(15,23,42,.3)}.invoice-toolbar{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;color:#fff;background:#0f172a}.invoice-toolbar div{display:grid}.invoice-toolbar span{color:#93c5fd;font-size:.55rem;font-weight:950;letter-spacing:.16em}.invoice-toolbar strong{margin-top:2px;font-size:.78rem}.invoice-toolbar button{border:0;color:#fff;background:transparent;font-size:1rem;cursor:pointer}.invoice-sheet{position:relative;overflow:auto;padding:28px 34px 30px;background:#fff}.invoice-watermark{position:absolute;top:46%;right:7%;z-index:0;color:rgba(37,99,235,.035);font-size:42px;font-weight:950;letter-spacing:.12em;transform:rotate(-28deg);pointer-events:none}.invoice-topline,.invoice-meta-grid,.invoice-party-grid,.invoice-table,.invoice-summary-area,.invoice-amount-note,.invoice-vat-notice,.invoice-footer-note{position:relative;z-index:1}.invoice-topline{display:grid;grid-template-columns:1fr 1.15fr;gap:28px;padding-bottom:18px;border-bottom:2px solid #0f172a}.seller-block h2{margin:4px 0 5px;color:#101828;font-size:1.1rem;font-weight:950;letter-spacing:.04em}.seller-block p{margin:2px 0;color:#667085;font-size:.62rem}.invoice-label{display:block;color:#2563eb;font-size:.52rem;font-weight:950;letter-spacing:.12em}.invoice-title-block{text-align:right}.invoice-title-block h1{margin:5px 0 8px;color:#101828;font-size:1.25rem;font-weight:950;letter-spacing:-.02em}.preview-badge{display:inline-flex;padding:4px 7px;border:1px solid #bfdbfe;border-radius:999px;color:#1d4ed8;background:#eff6ff;font-size:.5rem;font-weight:900}.invoice-meta-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:0;margin:18px 0;border:1px solid #dfe4ec;border-radius:10px;overflow:hidden}.invoice-meta-grid>div{display:grid;gap:3px;padding:9px 11px;border-right:1px solid #e8ecf1}.invoice-meta-grid>div:last-child{border-right:0}.invoice-meta-grid span{color:#98a2b3;font-size:.5rem;font-weight:750}.invoice-meta-grid strong{color:#344054;font-size:.6rem;font-weight:850}.invoice-party-grid{display:grid;grid-template-columns:1.15fr .85fr;gap:18px;margin-bottom:18px}.invoice-party{padding:12px 14px;border:1px solid #e4e7ec;border-radius:10px;background:#fbfcfe}.invoice-party h3{margin:0 0 8px;color:#2563eb;font-size:.54rem;font-weight:950;letter-spacing:.08em}.invoice-party p{margin:4px 0;color:#475467;font-size:.59rem;line-height:1.45}.invoice-party p strong{color:#101828}.invoice-party p span{color:#667085}.invoice-table{width:100%;border-collapse:collapse;table-layout:fixed}.invoice-table th,.invoice-table td{padding:8px 7px;border:1px solid #dfe4ec;font-size:.58rem}.invoice-table th{color:#344054;background:#f3f6fa;font-size:.53rem;font-weight:900;text-transform:uppercase}.invoice-table td{color:#475467;vertical-align:top}.invoice-table td strong{color:#101828;font-weight:800}.invoice-table .stt{width:40px;text-align:center}.invoice-table .unit{width:55px;text-align:center}.invoice-table .qty{width:68px;text-align:center}.invoice-table .money{width:118px;text-align:right}.invoice-summary-area{display:grid;grid-template-columns:1fr 320px;gap:30px;margin-top:18px}.invoice-signature-box{display:flex;min-height:90px;align-items:center;flex-direction:column;justify-content:flex-start;padding-top:5px;color:#344054;font-size:.62rem;font-weight:800}.invoice-signature-box small{margin-top:4px;color:#98a2b3;font-size:.52rem;font-style:italic}.invoice-total-box{display:grid;gap:6px}.invoice-total-box>div{display:flex;justify-content:space-between;gap:15px;color:#667085;font-size:.62rem}.invoice-total-box strong{color:#344054}.invoice-grand-total{margin-top:5px;padding-top:9px;border-top:2px solid #0f172a;color:#101828!important;font-size:.69rem!important;font-weight:950}.invoice-grand-total strong{color:#dc2626;font-size:.84rem}.invoice-amount-note{margin:16px 0 0;color:#667085;font-size:.59rem}.invoice-amount-note strong{color:#344054}.invoice-vat-notice{display:flex;gap:7px;margin-top:12px;padding:9px 10px;border:1px solid #dbeafe;border-radius:9px;color:#475467;background:#f8fbff;font-size:.55rem;line-height:1.5}.invoice-vat-notice i{color:#2563eb}.invoice-footer-note{margin:16px 0 0;padding-top:10px;border-top:1px dashed #dfe4ec;color:#98a2b3;text-align:center;font-size:.55rem}.invoice-actions{display:flex;justify-content:flex-end;gap:8px;padding:11px 15px;border-top:1px solid #e4e7ec;background:#fff}.secondary-invoice-btn,.sale-invoice-btn,.vat-invoice-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-width:120px;border:1px solid transparent;border-radius:9px;padding:9px 12px;font-size:.62rem;font-weight:850;cursor:pointer}.secondary-invoice-btn{color:#344054;background:#f2f4f7}.sale-invoice-btn{color:#fff;border-color:#2563eb;background:#2563eb;box-shadow:0 6px 15px rgba(37,99,235,.16)}.vat-invoice-btn{color:#fff;border-color:#15803d;background:#15803d;box-shadow:0 6px 15px rgba(21,128,61,.14)}.vat-invoice-btn:disabled{color:#98a2b3;border-color:#e4e7ec;background:#f2f4f7;box-shadow:none;cursor:not-allowed;opacity:.8}
@media(max-width:900px){.orders-stat-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.invoice-meta-grid{grid-template-columns:repeat(2,1fr)}.invoice-meta-grid>div:nth-child(2){border-right:0}.invoice-meta-grid>div:nth-child(-n+2){border-bottom:1px solid #e8ecf1}.invoice-topline,.invoice-summary-area{grid-template-columns:1fr}.invoice-title-block{text-align:left}.invoice-party-grid{grid-template-columns:1fr}}
@media(max-width:620px){.orders-page-header,.history-panel-head,.order-history-foot{align-items:flex-start;flex-direction:column}.orders-stat-grid{grid-template-columns:1fr}.order-history-body{align-items:flex-start;flex-direction:column}.order-actions{width:100%}.order-actions button{flex:1}.invoice-print-root{padding:8px}.invoice-modal{max-height:calc(100vh - 16px)}.invoice-sheet{padding:20px 16px}.invoice-meta-grid{grid-template-columns:1fr}.invoice-meta-grid>div{border-right:0!important;border-bottom:1px solid #e8ecf1}.invoice-meta-grid>div:last-child{border-bottom:0}.invoice-table{font-size:.5rem}.invoice-table th,.invoice-table td{padding:5px 4px;font-size:.48rem}.invoice-table .money{width:85px}.invoice-table .qty{width:50px}.invoice-table .unit{width:42px}.invoice-summary-area{gap:12px}.invoice-total-box{width:100%}.invoice-actions{flex-wrap:wrap}.secondary-invoice-btn,.sale-invoice-btn,.vat-invoice-btn{flex:1;min-width:0}}
@media print{
    @page{size:A4 portrait;margin:0}
    html,body{width:210mm!important;min-height:297mm!important;margin:0!important;padding:0!important;background:#fff!important}
    body>*:not(.invoice-print-root){display:none!important}
    .invoice-print-root{position:static!important;inset:auto!important;display:block!important;width:210mm!important;min-height:297mm!important;padding:0!important;background:#fff!important}
    .invoice-modal{width:210mm!important;min-height:297mm!important;max-height:none!important;display:block!important;overflow:visible!important;border:0!important;border-radius:0!important;box-shadow:none!important;background:#fff!important}
    .invoice-toolbar,.invoice-actions,.no-print{display:none!important}
    .invoice-sheet{width:210mm!important;min-height:297mm!important;box-sizing:border-box!important;overflow:visible!important;padding:12mm 12mm 10mm!important;background:#fff!important}
    .invoice-watermark{display:block!important}
    .invoice-topline{padding-bottom:5mm!important}.seller-block h2{font-size:12pt!important}.seller-block p{font-size:7pt!important}.invoice-label{font-size:6pt!important}.invoice-title-block h1{font-size:14pt!important;margin:1mm 0 2mm!important}.preview-badge{font-size:6pt!important;padding:1mm 2mm!important}
    .invoice-meta-grid{margin:4mm 0!important}.invoice-meta-grid>div{padding:2.5mm 3mm!important}.invoice-meta-grid span{font-size:6pt!important}.invoice-meta-grid strong{font-size:7pt!important}
    .invoice-party-grid{gap:4mm!important;margin-bottom:4mm!important}.invoice-party{padding:3mm 3.5mm!important}.invoice-party h3{font-size:6pt!important;margin-bottom:2mm!important}.invoice-party p{font-size:6.7pt!important;margin:1.2mm 0!important}
    .invoice-table th,.invoice-table td{padding:2.1mm 1.8mm!important;font-size:6.7pt!important}.invoice-table th{font-size:6pt!important}.invoice-table .stt{width:9mm!important}.invoice-table .unit{width:13mm!important}.invoice-table .qty{width:16mm!important}.invoice-table .money{width:31mm!important}
    .invoice-summary-area{grid-template-columns:1fr 80mm!important;gap:5mm!important;margin-top:4mm!important}.invoice-signature-box{font-size:7pt!important}.invoice-signature-box small{font-size:6pt!important}.invoice-total-box{gap:1.5mm!important}.invoice-total-box>div{font-size:7pt!important}.invoice-grand-total{font-size:7.5pt!important;padding-top:2mm!important}.invoice-grand-total strong{font-size:9pt!important}.invoice-amount-note{margin-top:4mm!important;font-size:6.7pt!important}.invoice-vat-notice{margin-top:3mm!important;padding:2.5mm 3mm!important;font-size:6pt!important}.invoice-footer-note{margin-top:4mm!important;padding-top:3mm!important;font-size:6pt!important}
    .invoice-table tr{break-inside:avoid!important;page-break-inside:avoid!important}.invoice-party-grid,.invoice-summary-area{break-inside:avoid!important;page-break-inside:avoid!important}
}
</style>
