<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

type DeletionRequest = {
    id: number;
    request_type: 'single' | 'all';
    status: string;
    created_at: string;
    requester?: { id: number; name: string; email: string } | null;
    order?: { id: number; code: string; customer_name: string; total: number } | null;
};

const props = defineProps<{ requests: DeletionRequest[] }>();
const busyId = ref<number | null>(null);
const actionError = ref('');

const money = (value: number) => new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + ' ₫';
const typeLabel = (item: DeletionRequest) => item.request_type === 'all' ? 'Xóa toàn bộ đơn hàng' : `Xóa đơn ${item.order?.code ?? '(đã không còn tồn tại)'}`;

function approve(item: DeletionRequest) {
    if (busyId.value !== null) return;
    busyId.value = item.id;
    actionError.value = '';
    router.patch(`/admin/orders/deletion-requests/${item.id}/approve`, {}, {
        preserveScroll: true,
        onError: () => { actionError.value = 'Không thể phê duyệt yêu cầu. Có thể yêu cầu đã được xử lý hoặc tài khoản không đủ quyền.'; },
        onFinish: () => { busyId.value = null; },
    });
}

function reject(item: DeletionRequest) {
    if (busyId.value !== null) return;
    const reason = window.prompt('Lý do từ chối (có thể bỏ trống):') ?? '';
    if (reason === null) return;
    busyId.value = item.id;
    actionError.value = '';
    router.patch(`/admin/orders/deletion-requests/${item.id}/reject`, { reason }, {
        preserveScroll: true,
        onError: () => { actionError.value = 'Không thể từ chối yêu cầu.'; },
        onFinish: () => { busyId.value = null; },
    });
}
</script>

<template>
    <div class="admin-page deletion-requests-page">
        <div class="admin-page-head deletion-head">
            <div>
                <div class="admin-kicker">KIỂM SOÁT XÓA DỮ LIỆU</div>
                <h1>Yêu cầu xóa đơn hàng</h1>
                <p>Mọi thao tác xóa phải nhập mật khẩu của tài khoản tạo yêu cầu và chờ một quản trị viên khác phê duyệt.</p>
            </div>
            <Link href="/admin/orders" class="back-orders-btn"><i class="bi bi-arrow-left" /> Quay lại đơn hàng</Link>
        </div>

        <div class="deletion-security-note">
            <i class="bi bi-shield-lock-fill" />
            <div><strong>Quy trình hai bước</strong><span>Nhập mật khẩu → gửi yêu cầu → quản trị viên khác phê duyệt → hệ thống mới xóa dữ liệu.</span></div>
        </div>

        <div v-if="actionError" class="deletion-error"><i class="bi bi-exclamation-circle" /><span>{{ actionError }}</span></div>

        <section class="deletion-panel">
            <header><div><strong>Đang chờ phê duyệt</strong><span>{{ props.requests.length }} yêu cầu</span></div><span class="pending-pill"><i class="bi bi-clock-history" /> Chờ xử lý</span></header>
            <div v-if="!props.requests.length" class="empty-deletion"><i class="bi bi-check2-circle" /><strong>Không có yêu cầu chờ phê duyệt</strong><span>Hệ thống sẽ hiển thị yêu cầu mới tại đây.</span></div>
            <div v-else class="deletion-list">
                <article v-for="item in props.requests" :key="item.id" class="deletion-card">
                    <div class="deletion-icon"><i :class="item.request_type === 'all' ? 'bi bi-trash3-fill' : 'bi bi-trash'" /></div>
                    <div class="deletion-main"><span class="deletion-type">{{ typeLabel(item) }}</span><strong v-if="item.order">{{ item.order.customer_name }} · {{ money(item.order.total) }}</strong><strong v-else>Tất cả đơn hàng hiện có trong hệ thống</strong><small>Yêu cầu bởi <b>{{ item.requester?.name ?? 'Quản trị viên' }}</b> · {{ new Date(item.created_at).toLocaleString('vi-VN') }}</small></div>
                    <div class="deletion-actions"><button type="button" class="reject-btn" :disabled="busyId !== null" @click="reject(item)"><i class="bi bi-x-lg" /> Từ chối</button><button type="button" class="approve-delete-btn" :disabled="busyId !== null" @click="approve(item)"><i :class="['bi', busyId === item.id ? 'bi-arrow-repeat spin' : 'bi-check-lg']" />{{ busyId === item.id ? 'Đang xử lý...' : 'Phê duyệt xóa' }}</button></div>
                </article>
            </div>
        </section>
    </div>
</template>

<style scoped>
.deletion-requests-page{max-width:1180px}.deletion-head{display:flex;align-items:flex-end;justify-content:space-between;gap:20px}.deletion-head h1{margin-bottom:8px}.deletion-head p{max-width:720px}.back-orders-btn{display:inline-flex;align-items:center;gap:7px;padding:10px 13px;border:1px solid #e4e7ec;border-radius:11px;background:#fff;color:#344054;font-size:11px;font-weight:800;text-decoration:none;white-space:nowrap}.back-orders-btn:hover{border-color:#bfdbfe;color:#1d4ed8}.deletion-security-note{display:flex;align-items:center;gap:12px;margin:20px 0;padding:14px 16px;border:1px solid #bfdbfe;border-radius:14px;background:#eff6ff;color:#1d4ed8}.deletion-security-note>i{font-size:19px}.deletion-security-note strong,.deletion-security-note span{display:block}.deletion-security-note strong{font-size:11px}.deletion-security-note span{margin-top:2px;color:#475467;font-size:10px}.deletion-error{display:flex;gap:8px;align-items:center;margin-bottom:14px;padding:11px 13px;border:1px solid #fecdca;border-radius:11px;background:#fef3f2;color:#b42318;font-size:10px;font-weight:700}.deletion-panel{border:1px solid #e4e7ec;border-radius:18px;background:#fff;box-shadow:0 10px 30px rgba(16,24,40,.05);overflow:hidden}.deletion-panel>header{display:flex;align-items:center;justify-content:space-between;padding:17px 20px;border-bottom:1px solid #edf0f3}.deletion-panel>header strong,.deletion-panel>header span{display:block}.deletion-panel>header strong{font-size:13px}.deletion-panel>header div span{margin-top:3px;color:#98a2b3;font-size:9px}.pending-pill{display:inline-flex!important;align-items:center;gap:5px;padding:6px 9px;border-radius:999px;color:#b54708;background:#fffaeb;font-size:9px!important;font-weight:800}.deletion-list{display:grid}.deletion-card{display:flex;align-items:center;gap:13px;padding:16px 20px;border-bottom:1px solid #f0f2f5}.deletion-card:last-child{border-bottom:0}.deletion-icon{display:grid;width:42px;height:42px;flex:0 0 42px;place-items:center;border-radius:12px;color:#b42318;background:#fef3f2}.deletion-main{min-width:0;flex:1}.deletion-type{display:block;color:#b42318;font-size:9px;font-weight:900;letter-spacing:.06em;text-transform:uppercase}.deletion-main strong{display:block;margin-top:3px;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.deletion-main small{display:block;margin-top:5px;color:#98a2b3;font-size:9px}.deletion-main small b{color:#475467}.deletion-actions{display:flex;gap:7px;flex-shrink:0}.deletion-actions button{min-height:36px;padding:0 11px;border-radius:9px;font-size:10px;font-weight:800;cursor:pointer}.reject-btn{border:1px solid #e4e7ec;background:#fff;color:#475467}.approve-delete-btn{border:0;background:#b42318;color:#fff;box-shadow:0 6px 15px rgba(180,35,24,.15)}.deletion-actions button:disabled{opacity:.55;cursor:not-allowed}.empty-deletion{display:grid;place-items:center;padding:70px 20px;color:#98a2b3;text-align:center}.empty-deletion i{font-size:32px;color:#12b76a}.empty-deletion strong{margin-top:10px;color:#344054;font-size:13px}.empty-deletion span{margin-top:4px;font-size:10px}.spin{animation:spin .8s linear infinite}@keyframes spin{to{transform:rotate(360deg)}}@media(max-width:700px){.deletion-head{align-items:flex-start;flex-direction:column}.deletion-card{align-items:flex-start;flex-wrap:wrap}.deletion-actions{width:100%;padding-left:55px}.deletion-actions button{flex:1}.deletion-security-note{align-items:flex-start}}
</style>
