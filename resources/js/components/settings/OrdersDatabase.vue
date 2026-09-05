<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { formatPrice } from '@/data/products';

type OrderStatus = 'Chờ xử lý' | 'Đã duyệt' | 'Hoàn tất' | 'Đang giao' | 'Đã giao' | 'Trả hàng' | 'Hủy hàng';
type Order = {
  id: number;
  code: string;
  createdAt: string;
  customer: { name?: string; phone?: string; email?: string; address?: string; note?: string };
  vatInvoice: { requested: boolean; companyName?: string | null; taxCode?: string | null; address?: string | null; email?: string | null; rate?: number; amount?: number };
  items: Array<{ id: number; name: string; price: number; image: string; quantity: number }>;
  subtotal: number;
  shipping: number;
  totalShipping: number;
  total: number;
  payment: string;
  status: string;
};

const page = usePage();
const orders = ref<Order[]>(((page.props as any).orders ?? []) as Order[]);
watch(
  () => ((page.props as any).orders ?? []) as Order[],
  (value) => {
    orders.value = [...value];
  },
  { deep: true }
);
const filter = ref<'all' | OrderStatus>('all');
const selected = ref<Order | null>(null);
const actionError = ref('');
const showCancelConfirm = ref(false);
const cancelTarget = ref<Order | null>(null);
const cancelling = ref(false);

const statusOptions: Array<{ key: 'all' | OrderStatus; label: string; icon: string }> = [
  { key: 'all', label: 'Tất cả', icon: 'bi-grid' },
  { key: 'Chờ xử lý', label: 'Chờ xử lý', icon: 'bi-hourglass-split' },
  { key: 'Đã duyệt', label: 'Đã duyệt', icon: 'bi-check-circle' },
  { key: 'Hoàn tất', label: 'Hoàn tất', icon: 'bi-check2-circle' },
  { key: 'Đang giao', label: 'Đang giao', icon: 'bi-truck' },
  { key: 'Đã giao', label: 'Đã giao', icon: 'bi-check2-circle' },
  { key: 'Trả hàng', label: 'Trả hàng', icon: 'bi-arrow-return-left' },
  { key: 'Hủy hàng', label: 'Hủy hàng', icon: 'bi-x-circle' },
];

const statusMeta: Record<OrderStatus, { icon: string; className: string; description: string }> = {
  'Chờ xử lý': { icon: 'bi-hourglass-split', className: 'pending', description: 'TechStore đang tiếp nhận đơn hàng.' },
  'Đã duyệt': { icon: 'bi-check-circle', className: 'approved', description: 'Đơn hàng đã được TechStore xác nhận và đang chờ vận chuyển.' },
  'Hoàn tất': { icon: 'bi-check2-circle', className: 'completed', description: 'Đơn hàng đã hoàn tất.' },
  'Đang giao': { icon: 'bi-truck', className: 'shipping', description: 'Đơn hàng đang được vận chuyển.' },
  'Đã giao': { icon: 'bi-check-circle-fill', className: 'delivered', description: 'Đơn hàng đã giao thành công.' },
  'Trả hàng': { icon: 'bi-arrow-return-left', className: 'returned', description: 'Đơn hàng đang ở trạng thái trả hàng.' },
  'Hủy hàng': { icon: 'bi-x-circle-fill', className: 'cancelled', description: 'Đơn hàng đã được hủy.' },
};

const fallbackStatusMeta = { icon: 'bi-question-circle', className: 'pending', description: 'Trạng thái đơn hàng chưa được định nghĩa.' };
const getStatusMeta = (status: string) => Object.prototype.hasOwnProperty.call(statusMeta, status) ? statusMeta[status as OrderStatus] : fallbackStatusMeta;
const filteredOrders = computed(() => filter.value === 'all' ? orders.value : orders.value.filter((o) => o.status === filter.value));
const activeCount = computed(() => orders.value.filter((o) => ['Chờ xử lý', 'Đã duyệt', 'Đang giao'].includes(o.status)).length);
const deliveredCount = computed(() => orders.value.filter((o) => o.status === 'Đã giao').length);
const totalSpent = computed(() => orders.value.filter((o) => o.status !== 'Hủy hàng').reduce((sum, o) => sum + Number(o.total), 0));
const currentUser = computed(() => ((page.props as any).auth?.user ?? null) as any);
const paymentLabel = (payment: string) => payment === 'paypal-sandbox' ? 'PayPal Sandbox' : payment === 'paypal-demo' ? 'PayPal mô phỏng' : 'Thanh toán khi nhận hàng';
const formatDate = (value: string) => {
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? '—' : new Intl.DateTimeFormat('vi-VN', { dateStyle: 'medium', timeStyle: 'short' }).format(date);
};
const statusClass = (status: string) => getStatusMeta(status).className;

function openOrder(order: Order) {
  selected.value = order;
  actionError.value = '';
  document.body.classList.add('techstore-order-modal-open');
}

function closeOrder() {
  selected.value = null;
  document.body.classList.remove('techstore-order-modal-open');
}

function openCancelConfirm(order: Order) {
  if (order.status !== 'Chờ xử lý') return;
  actionError.value = '';
  cancelTarget.value = order;
  showCancelConfirm.value = true;
}

function closeCancelConfirm() {
  if (cancelling.value) return;
  showCancelConfirm.value = false;
  cancelTarget.value = null;
}

async function confirmCancelOrder() {
  const order = cancelTarget.value;
  if (!order || order.status !== 'Chờ xử lý' || cancelling.value) return;

  cancelling.value = true;
  actionError.value = '';

  try {
    const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
    const response = await fetch(`/orders/${order.id}/cancel`, {
      method: 'PATCH',
      headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token },
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok || !data.order) throw new Error(data.message ?? 'Không thể hủy đơn hàng.');

    const index = orders.value.findIndex((item) => item.id === order.id);
    if (index >= 0) orders.value[index] = data.order;
    if (selected.value?.id === order.id) selected.value = data.order;
    showCancelConfirm.value = false;
    cancelTarget.value = null;
  } catch (error) {
    actionError.value = error instanceof Error ? error.message : 'Không thể hủy đơn hàng.';
  } finally {
    cancelling.value = false;
  }
}
</script>

<template>
  <section class="db-orders">
    <header class="db-orders-head">
      <div class="db-title"><span><i class="bi bi-bag-check" /></span><div><small>TECHSTORE · TÀI KHOẢN</small><h2>Đơn hàng</h2><p>Lịch sử mua hàng, trạng thái giao nhận và hóa đơn.</p></div></div>
      <div class="db-count"><i class="bi bi-receipt" /><strong>{{ orders.length }}</strong><span>đơn hàng</span></div>
    </header>

    <div v-if="actionError" class="db-error" role="alert"><i class="bi bi-exclamation-circle" />{{ actionError }}</div>

    <div class="db-stats">
      <article><span class="blue"><i class="bi bi-receipt-cutoff" /></span><div><small>Tổng đơn hàng</small><strong>{{ orders.length }}</strong><em>Tất cả đơn đã tạo</em></div></article>
      <article><span class="orange"><i class="bi bi-truck" /></span><div><small>Đang xử lý</small><strong>{{ activeCount }}</strong><em>Chờ duyệt, đã duyệt hoặc đang giao</em></div></article>
      <article><span class="green"><i class="bi bi-check2-circle" /></span><div><small>Đã giao</small><strong>{{ deliveredCount }}</strong><em>Giao thành công</em></div></article>
      <article><span class="purple"><i class="bi bi-cash-stack" /></span><div><small>Giá trị mua hàng</small><strong>{{ formatPrice(totalSpent) }}</strong><em>Không tính đơn đã hủy</em></div></article>
    </div>

    <section class="db-history">
      <header><div><h3>Lịch sử mua hàng</h3><p>Dữ liệu được nạp trực tiếp từ cơ sở dữ liệu theo tài khoản đang đăng nhập.</p></div><span><i class="bi bi-database-check" /> Cơ sở dữ liệu</span></header>
      <nav class="db-tabs">
        <button v-for="item in statusOptions" :key="item.key" type="button" :class="{ active: filter === item.key }" @click="filter = item.key"><i :class="['bi', item.icon]" />{{ item.label }}<b v-if="item.key !== 'all'">{{ orders.filter((o) => o.status === item.key).length }}</b></button>
      </nav>

      <div v-if="filteredOrders.length" class="db-list">
        <article v-for="order in filteredOrders" :key="order.code" class="db-card">
          <header><div class="db-order-title"><span><i class="bi bi-receipt" /></span><div><strong>#{{ order.code }}</strong><small>{{ formatDate(order.createdAt) }}</small></div></div><span :class="['db-status', statusClass(order.status)]"><i :class="['bi', getStatusMeta(order.status).icon]" />{{ order.status }}</span></header>
          <div class="db-card-body"><div class="db-thumbs"><div v-for="item in order.items.slice(0, 4)" :key="`${order.id}-${item.id}`" class="db-thumb"><img :src="item.image" :alt="item.name" /><b v-if="item.quantity > 1">×{{ item.quantity }}</b></div><div v-if="order.items.length > 4" class="db-more">+{{ order.items.length - 4 }}</div></div><div class="db-summary"><span>{{ order.items.reduce((sum, item) => sum + item.quantity, 0) }} sản phẩm · {{ paymentLabel(order.payment) }}</span><strong>{{ formatPrice(order.total) }}</strong><em>{{ getStatusMeta(order.status).description }}</em></div></div>
          <footer><span><i class="bi bi-file-earmark-text" />{{ order.vatInvoice.requested ? 'Có yêu cầu hóa đơn VAT' : 'Hóa đơn bán hàng' }}</span><div>
            <a :href="`/orders/${order.id}/sales-invoice`" target="_blank" rel="noopener" class="sales"><i class="bi bi-file-earmark-text" /> Tải hóa đơn</a>
            <a v-if="order.vatInvoice.requested" :href="`/orders/${order.id}/vat-invoice`" target="_blank" rel="noopener" class="vat"><i class="bi bi-file-earmark-pdf" /> Tải hóa đơn VAT</a>
            <button type="button" @click="openOrder(order)"><i class="bi bi-eye" /> Chi tiết</button>
            <button v-if="order.status === 'Chờ xử lý'" type="button" class="danger" @click="openCancelConfirm(order)"><i class="bi bi-x-lg" /> Hủy hàng</button>
          </div></footer>
        </article>
      </div>

      <div v-else class="db-empty"><div><i class="bi bi-bag-x" /></div><h3>Chưa có đơn hàng</h3><p>Tài khoản <strong>{{ currentUser?.email || currentUser?.name || 'hiện tại' }}</strong> chưa có đơn hàng phù hợp với trạng thái đã chọn.</p></div>
    </section>

    <Teleport to="body">
      <div v-if="selected" class="db-overlay" @click.self="closeOrder">
        <section class="db-modal" role="dialog" aria-modal="true" :aria-label="`Chi tiết đơn hàng ${selected.code}`">
          <header><div><small>CHI TIẾT ĐƠN HÀNG</small><h2>{{ selected.code }}</h2><span>{{ formatDate(selected.createdAt) }}</span></div><button type="button" aria-label="Đóng" @click="closeOrder"><i class="bi bi-x-lg" /></button></header>
          <div class="db-modal-body"><div class="db-detail-status"><span :class="['db-status', statusClass(selected.status)]"><i :class="['bi', getStatusMeta(selected.status).icon]" />{{ selected.status }}</span><strong>{{ formatPrice(selected.total) }}</strong></div><section><h3><i class="bi bi-person-vcard" /> Thông tin khách hàng</h3><div class="db-info-grid"><div><small>Họ và tên</small><strong>{{ selected.customer?.name || '—' }}</strong></div><div><small>Số điện thoại</small><strong>{{ selected.customer?.phone || '—' }}</strong></div><div><small>Email</small><strong>{{ selected.customer?.email || currentUser?.email || '—' }}</strong></div><div class="full"><small>Địa chỉ nhận hàng</small><strong>{{ selected.customer?.address || '—' }}</strong></div><div v-if="selected.customer?.note" class="full"><small>Ghi chú</small><strong>{{ selected.customer.note }}</strong></div></div></section><section><h3><i class="bi bi-box-seam" /> Sản phẩm</h3><div class="db-items"><div v-for="item in selected.items" :key="`${selected.id}-${item.id}`"><img :src="item.image" :alt="item.name" /><span><strong>{{ item.name }}</strong><small>{{ item.quantity }} × {{ formatPrice(item.price) }}</small></span><b>{{ formatPrice(item.price * item.quantity) }}</b></div></div></section><section><h3><i class="bi bi-receipt-cutoff" /> Hóa đơn & thanh toán</h3><div class="db-info-list"><div><span>Thanh toán</span><strong>{{ paymentLabel(selected.payment) }}</strong></div><div><span>Tạm tính</span><strong>{{ formatPrice(selected.subtotal) }}</strong></div><div><span>Phí vận chuyển</span><strong>{{ formatPrice(selected.totalShipping) }}</strong></div><div v-if="selected.vatInvoice.requested"><span>Đơn vị xuất VAT</span><strong>{{ selected.vatInvoice.companyName || '—' }}</strong></div><div v-if="selected.vatInvoice.requested"><span>Mã số thuế</span><strong>{{ selected.vatInvoice.taxCode || '—' }}</strong></div><div v-if="selected.vatInvoice.requested"><span>Địa chỉ VAT</span><strong>{{ selected.vatInvoice.address || '—' }}</strong></div><div class="grand"><span>Tổng thanh toán</span><strong>{{ formatPrice(selected.total) }}</strong></div></div></section></div>
          <footer><button type="button" class="close" @click="closeOrder">Đóng</button><button v-if="selected.status === 'Chờ xử lý'" type="button" class="cancel" @click="openCancelConfirm(selected)"><i class="bi bi-x-lg" /> Hủy đơn</button></footer>
        </section>
      </div>

      <Transition name="cancel-confirm">
        <div v-if="showCancelConfirm && cancelTarget" class="cancel-confirm-overlay" @click.self="closeCancelConfirm">
          <section class="cancel-confirm" role="dialog" aria-modal="true">
            <div class="cancel-confirm-icon"><i class="bi bi-exclamation-triangle-fill" /></div>
            <h3>Hủy đơn hàng?</h3>
            <p>Bạn có chắc chắn muốn hủy đơn <strong>#{{ cancelTarget.code }}</strong> không?</p>
            <div class="cancel-confirm-actions"><button type="button" class="keep" :disabled="cancelling" @click="closeCancelConfirm">Giữ lại</button><button type="button" class="confirm" :disabled="cancelling" @click="confirmCancelOrder"><span v-if="cancelling" class="spinner-border spinner-border-sm" /><i v-else class="bi bi-trash3" />{{ cancelling ? 'Đang hủy...' : 'Xác nhận hủy' }}</button></div>
          </section>
        </div>
      </Transition>
    </Teleport>
  </section>
</template>

<style scoped>
.db-orders{color:#101828}.db-orders-head{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:20px}.db-title{display:flex;align-items:center;gap:13px;min-width:0}.db-title>span{display:grid;width:46px;height:46px;flex:0 0 46px;place-items:center;border:1px solid #dbeafe;border-radius:13px;color:#2563eb;background:#eff6ff}.db-title small{display:block;color:#2563eb;font-size:9px;font-weight:900;letter-spacing:.14em}.db-title h2{margin:3px 0 0;font-size:1.35rem;font-weight:850;letter-spacing:-.03em}.db-title p{margin:4px 0 0;color:#667085;font-size:.74rem}.db-count{display:flex;align-items:center;gap:7px;padding:9px 12px;border:1px solid #e4e7ec;border-radius:11px;color:#667085;background:#fff;font-size:.72rem}.db-count strong{color:#101828;font-size:.9rem}.db-error{display:flex;gap:8px;align-items:center;margin-bottom:15px;padding:10px 12px;border:1px solid #fecaca;border-radius:11px;color:#b42318;background:#fff1f2;font-size:.72rem}.db-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}.db-stats article{display:flex;align-items:center;gap:10px;padding:13px;border:1px solid #e4e7ec;border-radius:15px;background:#fff;box-shadow:0 7px 22px rgba(16,24,40,.035)}.db-stats article>span{display:grid;width:38px;height:38px;place-items:center;border-radius:11px}.db-stats article>span.blue{color:#2563eb;background:#eff6ff}.db-stats article>span.orange{color:#ea580c;background:#fff7ed}.db-stats article>span.green{color:#16a34a;background:#ecfdf3}.db-stats article>span.purple{color:#7c3aed;background:#f5f3ff}.db-stats small,.db-stats strong,.db-stats em{display:block}.db-stats small{color:#667085;font-size:.62rem}.db-stats strong{margin-top:2px;font-size:.85rem}.db-stats em{margin-top:2px;color:#98a2b3;font-style:normal;font-size:.57rem}.db-history{overflow:hidden;border:1px solid #e4e7ec;border-radius:18px;background:#fff;box-shadow:0 8px 28px rgba(16,24,40,.045)}.db-history>header{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:18px 19px 14px}.db-history>header h3{margin:0;font-size:.94rem;font-weight:850}.db-history>header p{margin:3px 0 0;color:#667085;font-size:.66rem}.db-history>header>span{display:inline-flex;align-items:center;gap:6px;padding:6px 8px;border-radius:8px;color:#067647;background:#ecfdf3;font-size:.6rem;font-weight:800}.db-tabs{display:flex;gap:6px;overflow:auto;padding:0 14px 13px;border-bottom:1px solid #edf0f3}.db-tabs button{display:inline-flex;align-items:center;gap:5px;white-space:nowrap;border:1px solid transparent;border-radius:9px;padding:7px 9px;color:#667085;background:#f8fafc;font-size:.62rem;font-weight:750;cursor:pointer}.db-tabs button b{min-width:17px;padding:2px 5px;border-radius:999px;color:#667085;background:#fff;font-size:.56rem}.db-tabs button.active{color:#2563eb;border-color:#bfdbfe;background:#eff6ff}.db-tabs button.active b{color:#2563eb}.db-list{display:grid;gap:11px;padding:14px}.db-card{overflow:hidden;border:1px solid #e8ecf1;border-radius:14px;background:#fff}.db-card>header{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:13px 14px;border-bottom:1px solid #edf0f3}.db-order-title{display:flex;align-items:center;gap:9px;min-width:0}.db-order-title>span{display:grid;width:34px;height:34px;flex:0 0 34px;place-items:center;border-radius:10px;color:#2563eb;background:#eff6ff}.db-order-title strong,.db-order-title small{display:block}.db-order-title strong{font-size:.75rem}.db-order-title small{margin-top:2px;color:#98a2b3;font-size:.58rem}.db-status{display:inline-flex;align-items:center;gap:5px;padding:6px 8px;border-radius:999px;font-size:.58rem;font-weight:850}.db-status.pending{color:#b54708;background:#fffaeb}.db-status.approved{color:#2563eb;background:#eff6ff}.db-status.shipping{color:#7c2d12;background:#fff7ed}.db-status.delivered{color:#067647;background:#ecfdf3}.db-status.completed{color:#047857;background:#ecfdf3}.db-status.returned{color:#7e22ce;background:#f5f3ff}.db-status.cancelled{color:#b42318;background:#fff1f2}.db-card-body{display:flex;gap:13px;padding:14px}.db-thumbs{display:flex;align-items:center;gap:7px}.db-thumb,.db-more{position:relative;display:grid;width:48px;height:48px;place-items:center;overflow:hidden;border:1px solid #e5e7eb;border-radius:10px;background:#f8fafc}.db-thumb img{width:100%;height:100%;object-fit:cover}.db-thumb b{position:absolute;right:3px;bottom:3px;padding:2px 4px;border-radius:5px;color:#fff;background:rgba(16,24,40,.72);font-size:.5rem}.db-more{color:#667085;font-size:.7rem;font-weight:800}.db-summary{min-width:0;display:grid;gap:3px}.db-summary span{color:#667085;font-size:.64rem}.db-summary strong{font-size:.82rem}.db-summary em{color:#98a2b3;font-size:.58rem;font-style:normal}.db-card>footer{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 14px;border-top:1px solid #edf0f3;background:#fafbfc}.db-card>footer>span{display:flex;align-items:center;gap:6px;min-width:0;color:#667085;font-size:.59rem}.db-card>footer>div{display:flex;align-items:center;justify-content:flex-end;gap:6px;flex-wrap:wrap}.db-card>footer a,.db-card>footer button{display:inline-flex;align-items:center;gap:5px;min-height:29px;padding:6px 9px;border:1px solid #dfe5ee;border-radius:8px;color:#475467;background:#fff;font:inherit;font-size:.59rem;font-weight:800;text-decoration:none;cursor:pointer}.db-card>footer a.sales{color:#2563eb;border-color:#cfe0ff;background:#eff6ff}.db-card>footer a.vat{color:#b42318;border-color:#fecaca;background:#fff1f2}.db-card>footer button:hover,.db-card>footer a:hover{filter:brightness(.98);transform:translateY(-1px)}.db-card>footer button.danger{color:#b42318;border-color:#fecaca;background:#fff1f2}.db-empty{padding:58px 22px;text-align:center}.db-empty>div{display:grid;width:54px;height:54px;margin:0 auto 13px;place-items:center;border-radius:15px;color:#98a2b3;background:#f8fafc;font-size:22px}.db-empty h3{margin:0;font-size:.92rem}.db-empty p{max-width:500px;margin:5px auto 0;color:#98a2b3;font-size:.67rem}.db-overlay,.cancel-confirm-overlay{position:fixed;inset:0;z-index:1200;display:grid;place-items:center;padding:18px;background:rgba(15,23,42,.45);backdrop-filter:blur(6px)}.db-modal{width:min(720px,100%);max-height:min(84vh,760px);display:grid;grid-template-rows:auto 1fr auto;overflow:hidden;border:1px solid #e4e7ec;border-radius:20px;background:#fff;box-shadow:0 30px 80px rgba(15,23,42,.25)}.db-modal>header{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:19px 20px;border-bottom:1px solid #edf0f3}.db-modal>header small{display:block;color:#2563eb;font-size:.58rem;font-weight:900;letter-spacing:.14em}.db-modal>header h2{margin:3px 0 0;font-size:1.12rem;font-weight:850}.db-modal>header span{display:block;margin-top:2px;color:#98a2b3;font-size:.61rem}.db-modal>header button{display:grid;width:34px;height:34px;place-items:center;border:1px solid #e4e7ec;border-radius:9px;color:#667085;background:#fff;cursor:pointer}.db-modal-body{overflow:auto;padding:18px 20px}.db-detail-status{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px}.db-detail-status>strong{font-size:1rem}.db-modal-body section{padding:15px 0;border-top:1px solid #edf0f3}.db-modal-body section:first-of-type{border-top:0}.db-modal-body h3{display:flex;align-items:center;gap:7px;margin:0 0 11px;font-size:.77rem;font-weight:850}.db-info-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:11px}.db-info-grid>div.full{grid-column:1/-1}.db-info-grid small,.db-info-grid strong{display:block}.db-info-grid small{color:#98a2b3;font-size:.56rem}.db-info-grid strong{margin-top:2px;font-size:.68rem;line-height:1.45;word-break:break-word}.db-items{display:grid;gap:9px}.db-items>div{display:flex;align-items:center;gap:10px;padding:8px;border:1px solid #edf0f3;border-radius:10px;background:#fafbfc}.db-items img{width:42px;height:42px;object-fit:cover;border-radius:8px}.db-items span{min-width:0;flex:1}.db-items span strong,.db-items span small{display:block}.db-items span strong{font-size:.65rem}.db-items span small{margin-top:2px;color:#98a2b3;font-size:.57rem}.db-items b{font-size:.66rem}.db-info-list{display:grid;gap:8px}.db-info-list>div{display:flex;align-items:center;justify-content:space-between;gap:12px;font-size:.62rem}.db-info-list>div span{color:#667085}.db-info-list>div strong{font-size:.63rem;text-align:right}.db-info-list>div.grand{margin-top:4px;padding-top:10px;border-top:1px dashed #e4e7ec}.db-info-list>div.grand span,.db-info-list>div.grand strong{font-size:.76rem;color:#101828}.db-modal>footer{display:flex;justify-content:flex-end;gap:8px;padding:13px 18px;border-top:1px solid #edf0f3;background:#fafbfc}.db-modal>footer button{min-height:36px;border-radius:9px;padding:8px 13px;border:1px solid #dfe3e8;color:#475467;background:#fff;font:inherit;font-size:.64rem;font-weight:800}.db-modal>footer button.cancel{color:#b42318;border-color:#fecaca;background:#fff1f2}.cancel-confirm{width:min(410px,100%);padding:24px;border:1px solid #e4e7ec;border-radius:18px;background:#fff;text-align:center;box-shadow:0 30px 80px rgba(15,23,42,.25)}.cancel-confirm-icon{display:grid;width:48px;height:48px;margin:0 auto 12px;place-items:center;border-radius:14px;color:#b42318;background:#fff1f2}.cancel-confirm h3{margin:0;font-size:1rem}.cancel-confirm p{margin:6px 0 0;color:#667085;font-size:.7rem;line-height:1.5}.cancel-confirm-actions{display:flex;justify-content:center;gap:8px;margin-top:17px}.cancel-confirm-actions button{min-height:36px;padding:8px 12px;border-radius:9px;font:inherit;font-size:.64rem;font-weight:800;cursor:pointer}.cancel-confirm-actions button.keep{border:1px solid #dfe3e8;color:#475467;background:#fff}.cancel-confirm-actions button.confirm{display:inline-flex;align-items:center;justify-content:center;gap:6px;border:1px solid #fecaca;color:#fff;background:#dc2626}.cancel-confirm-actions button:disabled{opacity:.6;cursor:not-allowed}@media(max-width:1000px){.db-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:720px){.db-orders-head{align-items:flex-start}.db-count{display:none}.db-card-body{flex-direction:column}.db-card>footer{align-items:flex-start;flex-direction:column}.db-card>footer>div{justify-content:flex-start}.db-info-grid{grid-template-columns:1fr}.db-info-grid>div.full{grid-column:auto}}@media(max-width:520px){.db-stats{grid-template-columns:1fr}.db-history>header{align-items:flex-start;flex-direction:column}.db-history>header>span{align-self:flex-start}.db-modal-body{padding:15px}.db-modal>header{padding:15px}.db-modal>footer{padding:11px 12px}.cancel-confirm{padding:20px}}
</style>
