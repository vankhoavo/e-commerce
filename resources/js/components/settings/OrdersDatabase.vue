<script setup lang="ts">
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { formatPrice } from '@/data/products';

type Order = {
  id: number;
  code: string;
  createdAt: string;
  customer: { name?: string; phone?: string; email?: string; address?: string; note?: string };
  vatInvoice: { requested: boolean; companyName?: string | null; taxCode?: string | null; address?: string | null; email?: string | null };
  items: Array<{ id: number; name: string; price: number; image: string; quantity: number }>;
  subtotal: number;
  totalShipping: number;
  total: number;
  payment: string;
  status: string;
};

const page = usePage();
const orders = ref<Order[]>(((page.props as any).orders ?? []) as Order[]);
const selected = ref<Order | null>(null);
const returnReason = ref('');
const returnNote = ref('');
const actionError = ref('');
const returnOpen = ref(false);
const filter = ref('all');

const statuses = ['Chờ xử lý', 'Đã duyệt', 'Đang giao', 'Đã giao', 'Yêu cầu trả hàng', 'Chờ Admin duyệt trả hàng', 'Chờ nhận hàng hoàn', 'Đang kiểm tra hàng', 'Đã hoàn tiền', 'Hủy hàng'];
const filtered = computed(() => filter.value === 'all' ? orders.value : orders.value.filter((order) => order.status === filter.value));
const meta = (status: string) => status === 'Đã giao' ? { icon: 'bi-check-circle-fill', c: 'done' } : status === 'Hủy hàng' ? { icon: 'bi-x-circle-fill', c: 'cancel' } : status.includes('trả') || status.includes('Trả') || status === 'Đã hoàn tiền' ? { icon: 'bi-arrow-return-left', c: 'return' } : status === 'Đang giao' ? { icon: 'bi-truck', c: 'shipping' } : { icon: 'bi-hourglass-split', c: 'pending' };
const date = (value: string) => new Intl.DateTimeFormat('vi-VN', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
const payment = (value: string) => value === 'paypal-sandbox' ? 'PayPal Sandbox' : 'Thanh toán khi nhận hàng';

function open(order: Order) { selected.value = order; actionError.value = ''; returnOpen.value = false; document.body.classList.add('techstore-order-modal-open'); }
function close() { selected.value = null; returnOpen.value = false; returnReason.value = ''; returnNote.value = ''; actionError.value = ''; document.body.classList.remove('techstore-order-modal-open'); }

async function request(url: string, method: string, body?: object) {
  const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
  const response = await fetch(url, { method, headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token }, body: body ? JSON.stringify(body) : undefined });
  const data = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error(data.message ?? 'Không thể thực hiện thao tác.');
  return data;
}

async function cancel(order: Order) {
  if (order.status !== 'Chờ xử lý') return;
  try {
    const data = await request(`/orders/${order.id}/cancel`, 'PATCH');
    if (data.order) {
      const index = orders.value.findIndex((item) => item.id === order.id);
      if (index >= 0) orders.value[index] = data.order;
      if (selected.value?.id === order.id) selected.value = data.order;
    }
  } catch (error) { actionError.value = error instanceof Error ? error.message : 'Không thể hủy đơn hàng.'; }
}

async function submitReturn() {
  if (!selected.value || selected.value.status !== 'Đã giao') return;
  if (!returnReason.value.trim()) { actionError.value = 'Vui lòng nhập lý do trả hàng.'; return; }
  try {
    const data = await request(`/orders/${selected.value.id}/return`, 'PATCH', { reason: returnReason.value.trim(), customer_note: returnNote.value.trim() || null });
    const index = orders.value.findIndex((item) => item.id === selected.value?.id);
    if (index >= 0) { orders.value[index] = { ...orders.value[index], status: data.status ?? 'Yêu cầu trả hàng' }; selected.value = orders.value[index]; }
    returnOpen.value = false; returnReason.value = ''; returnNote.value = ''; actionError.value = '';
  } catch (error) { actionError.value = error instanceof Error ? error.message : 'Không thể gửi yêu cầu trả hàng.'; }
}
</script>

<template>
  <section class="orders-page">
    <header class="head">
      <div><span>TECHSTORE · TÀI KHOẢN</span><h2>Đơn hàng</h2><p>Lịch sử mua hàng, hóa đơn và đổi trả.</p></div>
      <strong>{{ orders.length }} đơn</strong>
    </header>

    <nav class="filters">
      <button :class="{ active: filter === 'all' }" @click="filter = 'all'">Tất cả</button>
      <button v-for="status in statuses" :key="status" :class="{ active: filter === status }" @click="filter = status">{{ status }}</button>
    </nav>

    <div v-if="filtered.length" class="list">
      <article v-for="order in filtered" :key="order.id" class="order-card">
        <header>
          <div><strong>#{{ order.code }}</strong><small>{{ date(order.createdAt) }}</small></div>
          <span :class="['status', meta(order.status).c]"><i :class="['bi', meta(order.status).icon]"></i>{{ order.status }}</span>
        </header>

        <div class="body">
          <div class="thumbs">
            <img v-for="item in order.items.slice(0, 4)" :key="`${order.id}-${item.id}`" :src="item.image" :alt="item.name" />
            <b v-if="order.items.length > 4">+{{ order.items.length - 4 }}</b>
          </div>
          <div class="summary"><span>{{ order.items.reduce((count, item) => count + item.quantity, 0) }} sản phẩm · {{ payment(order.payment) }}</span><strong>{{ formatPrice(order.total) }}</strong></div>
        </div>

        <footer>
          <span><i class="bi bi-receipt"></i>{{ order.vatInvoice.requested ? 'Hóa đơn VAT' : 'Hóa đơn bán hàng' }}</span>
          <div>
            <a :href="`/orders/${order.id}/invoice`" target="_blank" rel="noopener" class="invoice"><i class="bi bi-printer"></i>In hóa đơn</a>
            <button @click="open(order)">Chi tiết</button>
            <button v-if="order.status === 'Chờ xử lý'" class="danger" @click="cancel(order)">Hủy</button>
            <button v-if="order.status === 'Đã giao'" class="return" @click="open(order); returnOpen = true">Trả hàng</button>
          </div>
        </footer>
      </article>
    </div>

    <div v-else class="empty">
      <i class="bi bi-bag-x"></i>
      <h3>{{ filter === 'all' ? 'Chưa có đơn hàng' : 'Không có đơn hàng ở trạng thái này' }}</h3>
      <p>{{ filter === 'all' ? 'Các đơn hàng sau khi đặt sẽ xuất hiện tại đây.' : 'Hãy chọn một trạng thái khác để xem đơn hàng.' }}</p>
    </div>

    <Teleport to="body">
      <div v-if="selected" class="overlay" @click.self="close">
        <section class="modal">
          <header>
            <div><small>CHI TIẾT ĐƠN HÀNG</small><h2>#{{ selected.code }}</h2><span>{{ date(selected.createdAt) }}</span></div>
            <button @click="close">×</button>
          </header>

          <div class="modal-body">
            <div class="detail-status"><span :class="['status', meta(selected.status).c]">{{ selected.status }}</span><strong>{{ formatPrice(selected.total) }}</strong></div>

            <section><h3>Thông tin nhận hàng</h3><p><b>{{ selected.customer?.name }}</b> · {{ selected.customer?.phone }}</p><p>{{ selected.customer?.address }}</p></section>

            <section>
              <h3>Sản phẩm</h3>
              <div v-for="item in selected.items" :key="`${selected.id}-${item.id}`" class="item">
                <img :src="item.image" :alt="item.name" />
                <div><strong>{{ item.name }}</strong><small>{{ item.quantity }} × {{ formatPrice(item.price) }}</small></div>
                <b>{{ formatPrice(item.price * item.quantity) }}</b>
              </div>
            </section>

            <section>
              <h3>Thanh toán & hóa đơn</h3>
              <p>{{ payment(selected.payment) }} · Tạm tính {{ formatPrice(selected.subtotal) }} · Vận chuyển {{ formatPrice(selected.totalShipping) }}</p>
              <p v-if="selected.vatInvoice.requested">VAT: {{ selected.vatInvoice.companyName }} · MST {{ selected.vatInvoice.taxCode }}</p>
            </section>

            <div v-if="actionError" class="error">{{ actionError }}</div>
            <div v-if="returnOpen" class="return-box">
              <strong>Yêu cầu trả hàng</strong>
              <textarea v-model="returnReason" rows="3" placeholder="Lý do trả hàng"></textarea>
              <textarea v-model="returnNote" rows="2" placeholder="Ghi chú cho Bán hàng"></textarea>
              <button class="return-confirm" @click="submitReturn">Gửi yêu cầu</button>
            </div>
          </div>

          <footer>
            <button class="close" @click="close">Đóng</button>
            <a :href="`/orders/${selected.id}/invoice`" target="_blank" rel="noopener" class="invoice"><i class="bi bi-printer"></i>In hóa đơn</a>
            <button v-if="selected.status === 'Đã giao' && !returnOpen" class="return" @click="returnOpen = true">Yêu cầu trả hàng</button>
          </footer>
        </section>
      </div>
    </Teleport>
  </section>
</template>

<style scoped>
.orders-page{display:grid;gap:15px;color:#101828}.head{display:flex;justify-content:space-between;align-items:center}.head span{color:#2563eb;font-size:.58rem;font-weight:900;letter-spacing:.14em}.head h2{margin:3px 0;font-size:1.55rem;font-weight:900}.head p{margin:0;color:#667085;font-size:.7rem}.head>strong{padding:8px 11px;border:1px solid #e4e7ec;border-radius:10px;background:#fff;font-size:.65rem}.filters{display:flex;gap:6px;overflow:auto;padding-bottom:2px}.filters button{white-space:nowrap;padding:7px 9px;border:1px solid #e4e7ec;border-radius:9px;background:#fff;color:#667085;font-size:.6rem;font-weight:800}.filters button.active{color:#fff;border-color:#2563eb;background:#2563eb}.list{display:grid;gap:10px}.order-card{overflow:hidden;border:1px solid #e4e7ec;border-radius:16px;background:#fff}.order-card>header,.order-card>footer{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:13px 15px}.order-card>header{border-bottom:1px solid #edf0f4}.order-card header div{display:grid;gap:2px}.order-card header strong{font-size:.75rem}.order-card header small,.summary span{color:#98a2b3;font-size:.58rem}.status{display:inline-flex;align-items:center;gap:5px;padding:5px 8px;border-radius:8px;font-size:.57rem;font-weight:800}.status.pending{color:#a16207;background:#fffbeb}.status.shipping{color:#2563eb;background:#eff6ff}.status.done{color:#047857;background:#ecfdf3}.status.cancel{color:#b42318;background:#fff1f0}.status.return{color:#7c3aed;background:#f5f3ff}.body{display:flex;justify-content:space-between;gap:12px;padding:13px 15px}.thumbs{display:flex;gap:5px}.thumbs img,.thumbs b{width:45px;height:45px;object-fit:cover;border-radius:8px;border:1px solid #edf0f4}.thumbs b{display:grid;place-items:center;color:#667085;background:#f8fafc;font-size:.65rem}.summary{display:grid;justify-items:end;align-content:center;gap:4px}.summary strong{color:#dc2626;font-size:.85rem}.order-card footer{border-top:1px solid #edf0f4}.order-card footer>span{color:#667085;font-size:.6rem}.order-card footer>div{display:flex;gap:6px;align-items:center}.order-card button,.invoice{padding:7px 9px;border:1px solid #dbe2eb;border-radius:8px;color:#344054;background:#fff;text-decoration:none;font-size:.58rem;font-weight:800}.invoice{color:#2563eb;border-color:#bfdbfe}.order-card button.danger{color:#b42318}.order-card button.return,.return{color:#7c3aed;border-color:#ddd6fe}.empty{padding:55px;text-align:center;border:1px dashed #d0d5dd;border-radius:15px;color:#98a2b3}.empty i{font-size:28px}.empty h3{margin:8px 0 4px;color:#475467}.empty p{margin:0;font-size:.65rem}.overlay{position:fixed;z-index:1000;inset:0;display:grid;place-items:center;padding:18px;background:rgba(15,23,42,.55)}.modal{width:min(100%,650px);max-height:92vh;display:flex;flex-direction:column;border-radius:20px;background:#fff;box-shadow:0 25px 70px rgba(15,23,42,.28);overflow:hidden}.modal>header,.modal>footer{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:16px 19px;border-bottom:1px solid #edf0f4}.modal>header button{border:0;background:none;font-size:22px}.modal header small{color:#2563eb;font-size:.55rem;font-weight:900;letter-spacing:.12em}.modal h2{margin:3px 0;font-size:1.1rem;font-weight:900}.modal header span{color:#98a2b3;font-size:.58rem}.modal-body{padding:18px;overflow:auto}.detail-status{display:flex;justify-content:space-between;align-items:center;margin-bottom:15px}.detail-status>strong{color:#dc2626}.modal-body section{padding:13px 0;border-top:1px solid #edf0f4}.modal-body h3{margin:0 0 8px;font-size:.75rem}.modal-body p{margin:4px 0;color:#667085;font-size:.65rem}.item{display:flex;align-items:center;gap:9px;padding:7px 0}.item img{width:45px;height:45px;object-fit:cover;border-radius:8px}.item div{display:grid;flex:1}.item strong{font-size:.65rem}.item small{color:#98a2b3;font-size:.57rem}.return-box{display:grid;gap:7px;padding:12px;border-radius:12px;background:#f5f3ff}.return-box textarea{padding:9px;border:1px solid #ddd6fe;border-radius:8px;font-size:.65rem}.return-confirm{padding:9px;border:0;border-radius:9px;color:#fff;background:#7c3aed;font-size:.65rem;font-weight:800}.error{margin-top:10px;color:#b42318;font-size:.6rem}.modal>footer{border-top:1px solid #edf0f4;border-bottom:0;justify-content:flex-end}@media(max-width:600px){.body{flex-direction:column}.summary{justify-items:start}.order-card footer{align-items:flex-start;flex-direction:column}.order-card footer>div{flex-wrap:wrap}.modal{max-height:100dvh;height:100dvh;border-radius:0}}
</style>
