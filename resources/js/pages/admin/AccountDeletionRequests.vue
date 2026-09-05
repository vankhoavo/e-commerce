<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

type RequestItem = {
    id: number;
    reason: string | null;
    status: string;
    requested_at: string;
    reviewed_at: string | null;
    review_note: string | null;
    user: { id: number; name: string; email: string; phone: string | null; is_active: boolean; email_verified_at: string | null } | null;
    reviewer: { id: number; name: string } | null;
};

const props = defineProps<{
    requests: { data: RequestItem[]; total: number; current_page: number; last_page: number };
    summary: { pending: number; approved: number; rejected: number };
}>();

const selected = ref<RequestItem | null>(null);
const approving = ref<RequestItem | null>(null);
const reviewNote = ref('');

function openReject(item: RequestItem) { selected.value = item; reviewNote.value = ''; }
function closeReject() { selected.value = null; reviewNote.value = ''; }
function openApprove(item: RequestItem) { approving.value = item; }
function closeApprove() { approving.value = null; }
function approve() {
    if (!approving.value) return;
    router.patch(`/admin/account-deletion-requests/${approving.value.id}/approve`, {}, { onSuccess: () => { approving.value = null; } });
}
function reject() {
    if (!selected.value || !reviewNote.value.trim()) return;
    router.patch(`/admin/account-deletion-requests/${selected.value.id}/reject`, { review_note: reviewNote.value }, { onSuccess: () => closeReject() });
}
function statusLabel(status: string) { return status === 'pending' ? 'Chờ xử lý' : status === 'approved' ? 'Đã duyệt' : 'Đã từ chối'; }
</script>

<template>
    <div class="admin-page">
        <div class="request-head">
            <div><div class="admin-kicker">HỆ THỐNG</div><h1>Yêu cầu xoá tài khoản</h1><p>Tiếp nhận và xử lý yêu cầu xóa tài khoản do khách hàng gửi từ khu vực tài khoản.</p></div>
            <div class="request-count"><i class="bi bi-trash3" /><div><strong>{{ props.summary.pending }}</strong><small>đang chờ xử lý</small></div></div>
        </div>
        <div class="request-stats row g-3 mb-4">
            <div class="col-12 col-md-4"><div class="request-stat pending"><span><i class="bi bi-hourglass-split" /></span><div><small>Chờ xử lý</small><strong>{{ props.summary.pending }}</strong></div></div></div>
            <div class="col-12 col-md-4"><div class="request-stat approved"><span><i class="bi bi-check2-circle" /></span><div><small>Đã duyệt</small><strong>{{ props.summary.approved }}</strong></div></div></div>
            <div class="col-12 col-md-4"><div class="request-stat rejected"><span><i class="bi bi-x-circle" /></span><div><small>Đã từ chối</small><strong>{{ props.summary.rejected }}</strong></div></div></div>
        </div>
        <div class="admin-panel overflow-hidden">
            <div class="table-responsive">
                <table class="table admin-table request-table align-middle">
                    <thead><tr><th>Khách hàng</th><th>Lý do</th><th>Ngày yêu cầu</th><th>Trạng thái</th><th class="text-end">Xử lý</th></tr></thead>
                    <tbody>
                        <tr v-for="item in props.requests.data" :key="item.id">
                            <td><div class="request-user"><span class="request-avatar"><i class="bi bi-person-fill" /></span><div><strong>{{ item.user?.name ?? 'Tài khoản không còn tồn tại' }}</strong><small>{{ item.user?.email ?? '—' }}<span v-if="item.user?.phone"> • {{ item.user.phone }}</span></small></div></div></td>
                            <td><span class="reason-preview">{{ item.reason || 'Khách hàng không ghi lý do.' }}</span></td>
                            <td>{{ new Date(item.requested_at).toLocaleDateString('vi-VN') }}</td>
                            <td><span :class="['request-status', item.status]"><i class="bi bi-dot" />{{ statusLabel(item.status) }}</span></td>
                            <td class="text-end"><div class="request-actions"><template v-if="item.status === 'pending'"><button class="request-action approve" type="button" title="Duyệt xóa tài khoản" aria-label="Duyệt xóa tài khoản" @click="openApprove(item)"><i class="bi bi-check-lg" /></button><button class="request-action reject" type="button" title="Từ chối yêu cầu" aria-label="Từ chối yêu cầu" @click="openReject(item)"><i class="bi bi-x-lg" /></button></template><span v-else class="processed-mark">Đã xử lý</span></div></td>
                        </tr>
                        <tr v-if="!props.requests.data.length"><td colspan="5" class="empty-state"><i class="bi bi-inbox" /><strong>Chưa có yêu cầu xóa tài khoản</strong><span>Khi khách hàng gửi yêu cầu, dữ liệu sẽ xuất hiện tại đây.</span></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div v-if="approving" class="modal d-block admin-modal-backdrop" role="dialog" aria-modal="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg confirm-modal"><div class="confirm-icon"><i class="bi bi-shield-check" /></div><div class="confirm-body"><div class="modal-kicker">XÁC NHẬN THAO TÁC</div><h5>Xác nhận duyệt xóa tài khoản?</h5><p class="confirm-intro">Bạn đang duyệt yêu cầu xóa tài khoản của khách hàng dưới đây.</p><div class="confirm-user"><span class="detail-avatar"><i class="bi bi-person-fill" /></span><div><strong>{{ approving.user?.name ?? 'Tài khoản không còn tồn tại' }}</strong><small>{{ approving.user?.email ?? '—' }}</small></div></div><div class="confirm-warning"><i class="bi bi-exclamation-triangle-fill" /><div><strong>Thao tác này sẽ khóa quyền đăng nhập.</strong><span>Tài khoản sẽ được xóa mềm theo quy trình hệ thống và không thể đăng nhập lại.</span></div></div></div><div class="confirm-footer"><button type="button" class="btn btn-light confirm-cancel" @click="closeApprove">Hủy</button><button type="button" class="btn btn-danger confirm-submit" @click="approve"><i class="bi bi-check2-circle me-2" />Duyệt xóa tài khoản</button></div></div></div></div>
        <div v-if="selected" class="modal d-block admin-modal-backdrop" role="dialog" aria-modal="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg request-modal"><div class="modal-header"><div><div class="modal-kicker">YÊU CẦU #{{ selected.id }}</div><h5 class="mb-0">Từ chối yêu cầu</h5></div><button class="btn-close" type="button" aria-label="Đóng" @click="closeReject" /></div><div class="modal-body"><div class="detail-user"><span class="detail-avatar"><i class="bi bi-person-fill" /></span><div><strong>{{ selected.user?.name ?? 'Tài khoản không còn tồn tại' }}</strong><small>{{ selected.user?.email ?? '—' }}</small></div></div><div class="reason-box"><small>Lý do khách hàng</small><p>{{ selected.reason || 'Khách hàng không ghi lý do.' }}</p></div><div class="field"><label class="form-label">Ghi chú từ chối</label><textarea v-model="reviewNote" class="form-control" rows="4" placeholder="Nhập lý do để khách hàng biết vì sao yêu cầu bị từ chối..." required /></div></div><div class="modal-footer"><button type="button" class="btn btn-light" @click="closeReject">Hủy</button><button type="button" class="btn btn-danger" :disabled="!reviewNote.trim()" @click="reject"><i class="bi bi-x-circle me-2" />Xác nhận từ chối</button></div></div></div></div>
    </div>
</template>

<style scoped>
.request-head{display:flex;justify-content:space-between;align-items:end;gap:20px;margin-bottom:20px}.admin-kicker{color:#2563eb;font-size:.68rem;font-weight:900;letter-spacing:.14em}.request-head h1{margin:3px 0;font-size:1.8rem;font-weight:900;letter-spacing:-.04em}.request-head p{margin:0;color:#667085;font-size:.8rem}.request-count{display:flex;align-items:center;gap:10px;padding:9px 13px;border:1px solid #fee2e2;border-radius:13px;background:#fff7f7}.request-count>i{display:grid;width:30px;height:30px;place-items:center;border-radius:9px;background:#fee2e2;color:#dc2626}.request-count div{display:flex;flex-direction:column}.request-count strong{color:#991b1b;font-size:1rem;line-height:1}.request-count small{margin-top:3px;color:#b45309;font-size:.61rem}.request-stat{display:flex;align-items:center;gap:12px;padding:15px 17px;border:1px solid #e5e9f0;border-radius:15px;background:#fff;box-shadow:0 7px 24px rgba(16,24,40,.04)}.request-stat>span{display:grid;width:40px;height:40px;place-items:center;border-radius:12px}.request-stat.pending>span{background:#fffbeb;color:#d97706}.request-stat.approved>span{background:#ecfdf3;color:#16a34a}.request-stat.rejected>span{background:#fef2f2;color:#dc2626}.request-stat div{display:flex;flex-direction:column}.request-stat small{color:#667085;font-size:.65rem;font-weight:700}.request-stat strong{margin-top:2px;font-size:1.15rem}.admin-panel{border:1px solid #e5e9f0;border-radius:16px;background:#fff;box-shadow:0 8px 28px rgba(16,24,40,.05)}.admin-table{margin:0}.admin-table th{font-size:.67rem;color:#667085;text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid #edf0f4;background:#f8fafc}.admin-table td{font-size:.76rem;border-color:#f2f4f7}.request-user{display:flex;align-items:center;gap:10px}.request-avatar{display:grid;flex:0 0 40px;width:40px;height:40px;place-items:center;border-radius:12px;background:#fef2f2;color:#dc2626}.request-user div{display:flex;flex-direction:column}.request-user strong{font-size:.77rem}.request-user small{margin-top:2px;color:#98a2b3;font-size:.62rem}.reason-preview{display:block;max-width:330px;overflow:hidden;color:#475467;text-overflow:ellipsis;white-space:nowrap}.request-status{display:inline-flex;align-items:center;padding:5px 9px;border-radius:999px;font-size:.62rem;font-weight:800}.request-status.pending{background:#fffbeb;color:#b45309}.request-status.approved{background:#ecfdf3;color:#15803d}.request-status.rejected{background:#f2f4f7;color:#667085}.request-actions{display:flex;justify-content:flex-end;align-items:center;gap:7px}.request-action{display:grid;width:36px;height:36px;place-items:center;border:1px solid transparent;border-radius:10px;background:#fff;font-size:.85rem}.request-action.approve{border-color:#bbf7d0;background:#f0fdf4;color:#15803d}.request-action.reject{border-color:#fecaca;background:#fff7f7;color:#dc2626}.request-action:focus-visible{outline:3px solid rgba(37,99,235,.16);outline-offset:2px}.processed-mark{color:#98a2b3;font-size:.62rem}.empty-state{display:flex!important;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:5px;padding:52px!important;color:#98a2b3}.empty-state i{font-size:28px;margin-bottom:3px}.empty-state strong{color:#667085;font-size:.8rem}.empty-state span{font-size:.65rem}.admin-modal-backdrop{background:rgba(15,23,42,.56);backdrop-filter:blur(5px);z-index:1055}.confirm-modal{border-radius:22px;overflow:hidden;max-width:470px;margin:auto}.confirm-icon{display:grid;width:58px;height:58px;place-items:center;margin:24px auto 0;border:1px solid #bbf7d0;border-radius:17px;background:#ecfdf3;color:#16a34a;font-size:25px}
</style>
