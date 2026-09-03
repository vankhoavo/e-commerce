<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

type DeletionRequest={id:number;request_type:'single'|'all';status:string;created_at:string;requester?:{id:number;name:string;email:string}|null;order?:{id:number;code:string;customer_name:string;total:number}|null};
const props=defineProps<{requests:DeletionRequest[]}>();
const busyId=ref<number|null>(null);const actionError=ref('');
const money=(v:number)=>new Intl.NumberFormat('vi-VN').format(Number(v||0))+' ₫';
const typeLabel=(x:DeletionRequest)=>x.request_type==='all'?'Xóa toàn bộ đơn hàng':`Xóa đơn ${x.order?.code??'không xác định'}`;
function approve(x:DeletionRequest){if(busyId.value!==null)return;busyId.value=x.id;actionError.value='';router.patch(`/admin/orders/deletion-requests/${x.id}/approve`,{},{preserveScroll:true,onError:(e)=>{actionError.value=(e as any)?.message??'Không thể phê duyệt yêu cầu xóa.'},onFinish:()=>busyId.value=null});}
function reject(x:DeletionRequest){if(busyId.value!==null)return;busyId.value=x.id;actionError.value='';router.patch(`/admin/orders/deletion-requests/${x.id}/reject`,{},{preserveScroll:true,onError:(e)=>{actionError.value=(e as any)?.message??'Không thể từ chối yêu cầu.'},onFinish:()=>busyId.value=null});}
</script>
<template>
<div class="admin-page deletion-page">
  <div class="admin-page-head"><div><div class="admin-kicker">KIỂM SOÁT ĐƠN HÀNG</div><h1>Yêu cầu xoá đơn</h1><p>Xử lý yêu cầu xóa đơn hàng trực tiếp trong trang quản trị.</p></div><Link href="/admin/orders" class="back-btn"><i class="bi bi-arrow-left"/> Quay lại đơn hàng</Link></div>
  <div v-if="actionError" class="alert-box"><i class="bi bi-exclamation-circle"/> <span>{{actionError}}</span></div>
  <section class="panel"><header class="panel-head"><div><strong>Danh sách chờ xử lý</strong><span>{{props.requests.length}} yêu cầu</span></div><span class="count-pill"><i class="bi bi-clock"/> Chờ xử lý</span></header>
    <div v-if="!props.requests.length" class="empty"><i class="bi bi-check2-circle"/><strong>Không có yêu cầu xoá đơn hàng</strong><span>Các yêu cầu mới sẽ xuất hiện tại đây.</span></div>
    <div v-else class="list">
      <article v-for="item in props.requests" :key="item.id" class="card">
        <div class="icon"><i class="bi bi-trash3"/></div>
        <div class="main"><span>{{typeLabel(item)}}</span><strong>{{item.order?`${item.order.customer_name} · ${money(item.order.total)}`:'Tất cả đơn hàng hiện có'}}</strong><small>Yêu cầu bởi {{item.requester?.name??'Quản trị viên'}} · {{new Date(item.created_at).toLocaleString('vi-VN')}}</small></div>
        <div class="actions"><button class="reject" :disabled="busyId!==null" @click="reject(item)"><i class="bi bi-x-lg"/> Từ chối</button><button class="approve" :disabled="busyId!==null" @click="approve(item)"><i :class="['bi',busyId===item.id?'bi-arrow-repeat spin':'bi-check-lg']"/> {{busyId===item.id?'Đang xử lý':'Phê duyệt xóa'}}</button></div>
      </article>
    </div>
  </section>
</div>
</template>
<style scoped>
.deletion-page{max-width:1180px}.back-btn{display:inline-flex;align-items:center;gap:7px;padding:10px 13px;border:1px solid #e4e7ec;border-radius:11px;background:#fff;color:#344054;text-decoration:none;font-size:11px;font-weight:800}.back-btn:hover{color:#1d4ed8;border-color:#bfdbfe}.alert-box{display:flex;gap:8px;align-items:center;margin-bottom:16px;padding:11px 13px;border:1px solid #fecdca;background:#fef3f2;color:#b42318;border-radius:12px;font-size:11px;font-weight:700}.panel{border:1px solid #e4e7ec;border-radius:18px;background:#fff;box-shadow:0 10px 30px rgba(16,24,40,.05);overflow:hidden}.panel-head{display:flex;align-items:center;justify-content:space-between;padding:17px 20px;border-bottom:1px solid #edf0f3}.panel-head strong,.panel-head span{display:block}.panel-head strong{font-size:13px}.panel-head div span{margin-top:3px;color:#98a2b3;font-size:9px}.count-pill{display:inline-flex!important;align-items:center;gap:5px;padding:6px 9px;border-radius:999px;background:#fffaeb;color:#b54708;font-size:9px!important;font-weight:800}.card{display:flex;gap:13px;align-items:center;padding:17px 20px;border-bottom:1px solid #f0f2f5}.card:last-child{border-bottom:0}.icon{width:42px;height:42px;flex:0 0 42px;display:grid;place-items:center;border-radius:12px;background:#fef2f2;color:#b42318}.main{min-width:0;flex:1}.main>span{display:block;color:#b42318;font-size:9px;font-weight:900;text-transform:uppercase}.main>strong{display:block;margin-top:3px;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.main>small{display:block;margin-top:5px;color:#98a2b3;font-size:9px}.actions{display:flex;gap:8px;flex-shrink:0}.actions button{min-height:36px;border-radius:9px;padding:0 11px;font-size:10px;font-weight:800;cursor:pointer}.reject{border:1px solid #e4e7ec;background:#fff;color:#475467}.approve{border:0;background:#b42318;color:#fff}.actions button:disabled{opacity:.55;cursor:not-allowed}.empty{display:grid;place-items:center;padding:70px 20px;text-align:center;color:#98a2b3}.empty i{font-size:34px;color:#12b76a}.empty strong{margin-top:10px;color:#344054;font-size:13px}.empty span{margin-top:4px;font-size:10px}.spin{animation:spin .8s linear infinite}@keyframes spin{to{transform:rotate(360deg)}}@media(max-width:760px){.card{align-items:flex-start;flex-wrap:wrap}.actions{width:100%;padding-left:55px}.actions button{flex:1}}
</style>
