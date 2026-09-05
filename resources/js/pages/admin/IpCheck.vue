<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';

type Country = { rank: number; country: string; code: string | null; visits: number };
type IpLog = {
    id: number;
    created_at: string | null;
    ipv4: string | null;
    ipv6: string | null;
    ip_type: string | null;
    organization: string | null;
    asn: string | null;
    city: string | null;
    region: string | null;
    country: string | null;
    country_code: string | null;
    continent: string | null;
    device: string | null;
    browser: string | null;
    os: string | null;
    ptr: string | null;
    is_proxy: boolean | null;
    is_vpn: boolean | null;
    is_tor: boolean | null;
    metadata: Record<string, unknown> | null;
};
type Lookup = {
    ip: string;
    organization: string | null;
    asn: string | null;
    city: string | null;
    region: string | null;
    country: string | null;
    country_code: string | null;
    continent: string | null;
    type: string | null;
    hostname: string | null;
    ptr: string | null;
    security: { proxy?: boolean | null; vpn?: boolean | null; tor?: boolean | null } | null;
};

const props = defineProps<{
    logs: { data: IpLog[]; total: number; current_page: number; last_page: number };
    countries: Country[];
    summary: { total: number; ipv4: number; ipv6: number; devices: number };
    currentIp: string | null;
    lookup?: Lookup | null;
}>();

const selected = ref<IpLog | null>(null);
const lookupIp = ref(props.lookup?.ip || '');
const lookupLoading = ref(false);
const lookupError = ref<string | null>(null);
const maxVisits = computed(() => Math.max(1, ...props.countries.map((item) => item.visits)));

const formatDate = (value: string | null) => value
    ? new Date(value).toLocaleString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit', day: 'numeric', month: 'numeric', year: '2-digit', hour12: false }).replace(',', '')
    : '—';

const location = (row: IpLog) => [row.city, row.region, row.country].filter(Boolean).join(', ') || 'Chưa xác định';
const sources = (row: IpLog) => row.metadata?.sources as string[] | undefined;
const ipStatus = (row: IpLog) => row.is_proxy || row.is_vpn || row.is_tor ? 'Đáng chú ý' : 'Bình thường';
const lookupLocation = computed(() => [props.lookup?.city, props.lookup?.region, props.lookup?.country].filter(Boolean).join(', ') || 'Chưa xác định');

const performLookup = () => {
    const ip = lookupIp.value.trim();
    lookupError.value = null;

    if (!ip) {
        lookupError.value = 'Vui lòng nhập địa chỉ IP.';
        return;
    }

    lookupLoading.value = true;
    router.post('/admin/ip-check/lookup', { ip }, {
        preserveScroll: true,
        onFinish: () => {
            lookupLoading.value = false;
        },
        onError: (errors) => {
            lookupError.value = Object.values(errors)[0] || 'Không thể tra cứu địa chỉ IP.';
        },
    });
};
</script>

<template>
    <div class="admin-page ip-page">
        <div class="ip-head">
            <div>
                <div class="admin-kicker">HỆ THỐNG</div>
                <h1>Kiểm tra địa chỉ IP</h1>
                <p>Theo dõi địa chỉ IP truy cập, địa lý mạng, thiết bị và trình duyệt của khách truy cập.</p>
            </div>
            <div v-if="props.currentIp" class="current-ip"><span>IP hiện tại</span><strong>{{ props.currentIp }}</strong></div>
        </div>

        <section class="lookup-panel">
            <div class="panel-heading">
                <div><h2><i class="bi bi-search"/> Tra cứu địa chỉ IP</h2><p>Nhập IPv4 hoặc IPv6 công cộng để lấy thông tin mạng và vị trí địa lý.</p></div>
            </div>
            <form class="lookup-form" @submit.prevent="performLookup">
                <input v-model="lookupIp" type="text" inputmode="decimal" autocomplete="off" placeholder="Ví dụ: 8.8.8.8 hoặc 2001:4860:4860::8888" :disabled="lookupLoading">
                <button type="submit" class="lookup-btn" :disabled="lookupLoading">{{ lookupLoading ? 'Đang tra cứu…' : 'Tra cứu' }}</button>
            </form>
            <div v-if="lookupError" class="lookup-error"><i class="bi bi-exclamation-circle"/> {{ lookupError }}</div>
            <div v-if="props.lookup" class="lookup-result">
                <div class="lookup-result-head"><div><span class="result-kicker">KẾT QUẢ TRA CỨU</span><strong>{{ props.lookup.ip }}</strong></div><span class="lookup-status"><i class="bi bi-check-circle"/> Đã tra cứu</span></div>
                <div class="detail-grid lookup-grid">
                    <div><span>Loại IP</span><strong>{{ props.lookup.type || 'Không xác định' }}</strong></div>
                    <div><span>Nhà mạng / tổ chức</span><strong>{{ props.lookup.organization || 'Không xác định' }}</strong></div>
                    <div><span>ASN</span><strong>{{ props.lookup.asn || '—' }}</strong></div>
                    <div><span>PTR / Hostname</span><strong>{{ props.lookup.ptr || props.lookup.hostname || 'Không có' }}</strong></div>
                    <div><span>Địa điểm</span><strong>{{ lookupLocation }}</strong></div>
                    <div><span>Mã quốc gia</span><strong>{{ props.lookup.country_code || '—' }}</strong></div>
                </div>
                <div class="security-line">
                    <span :class="props.lookup.security?.proxy || props.lookup.security?.vpn || props.lookup.security?.tor ? 'danger' : 'safe'">
                        <i :class="['bi', props.lookup.security?.proxy || props.lookup.security?.vpn || props.lookup.security?.tor ? 'bi-exclamation-triangle' : 'bi-shield-check']"/>
                        {{ props.lookup.security?.proxy || props.lookup.security?.vpn || props.lookup.security?.tor ? 'Đáng chú ý' : 'Bình thường' }}
                    </span>
                    <small>{{ props.lookup.security?.proxy || props.lookup.security?.vpn || props.lookup.security?.tor ? 'Phát hiện dấu hiệu proxy, VPN hoặc Tor.' : 'Không phát hiện dấu hiệu proxy, VPN hoặc Tor từ nguồn tra cứu.' }}</small>
                </div>
            </div>
        </section>

        <section class="country-panel">
            <div class="panel-heading">
                <div><h2><i class="bi bi-globe2"/> Quốc gia truy cập</h2><p>Thống kê dựa trên dữ liệu địa lý IP, không phải vị trí GPS chính xác.</p></div>
            </div>
            <div v-if="props.countries.length" class="country-grid">
                <div v-for="item in props.countries" :key="`${item.code}-${item.country}`" class="country-row">
                    <span class="country-rank">{{ item.rank }}</span>
                    <div class="country-main">
                        <div class="country-label"><strong>{{ item.country }}</strong><b>{{ item.visits.toLocaleString('vi-VN') }}</b></div>
                        <div class="country-bar"><span :style="{ width: `${(item.visits / maxVisits) * 100}%` }"/></div>
                        <small>{{ item.code || '—' }}</small>
                    </div>
                </div>
            </div>
            <div v-else class="country-empty">Chưa có dữ liệu địa lý IP.</div>
        </section>

        <div class="row g-3 stats-row">
            <div class="col-12 col-sm-6 col-xl-3"><div class="ip-stat"><span>Tổng lượt</span><strong>{{ props.summary.total.toLocaleString('vi-VN') }}</strong><i class="bi bi-activity"/></div></div>
            <div class="col-12 col-sm-6 col-xl-3"><div class="ip-stat"><span>IPv4</span><strong>{{ props.summary.ipv4.toLocaleString('vi-VN') }}</strong><i class="bi bi-globe"/></div></div>
            <div class="col-12 col-sm-6 col-xl-3"><div class="ip-stat"><span>IPv6</span><strong>{{ props.summary.ipv6.toLocaleString('vi-VN') }}</strong><i class="bi bi-diagram-3"/></div></div>
            <div class="col-12 col-sm-6 col-xl-3"><div class="ip-stat"><span>Loại thiết bị</span><strong>{{ props.summary.devices.toLocaleString('vi-VN') }}</strong><i class="bi bi-phone"/></div></div>
        </div>

        <div class="source-note"><i class="bi bi-shield-check"/><span>Dữ liệu tra cứu IP được đối chiếu từ <strong>IPinfo</strong> và <strong>ipwho.is</strong> khi nguồn cung cấp thông tin.</span></div>

        <section class="admin-panel overflow-hidden">
            <div class="table-responsive">
                <table class="table ip-table align-middle">
                    <thead><tr><th>Thời gian</th><th>IPv4</th><th>IPv6</th><th>Địa điểm</th><th>Thiết bị</th><th>Trình duyệt</th><th>Hệ điều hành</th><th>Trạng thái IP</th></tr></thead>
                    <tbody>
                        <tr v-for="row in props.logs.data" :key="row.id">
                            <td class="time-cell">{{ formatDate(row.created_at) }}</td>
                            <td><div v-if="row.ipv4" class="ip-cell"><strong>{{ row.ipv4 }}</strong><small>{{ sources(row)?.join(' + ') || 'IPinfo + ipwho.is' }}</small></div><span v-else>—</span></td>
                            <td><div v-if="row.ipv6" class="ip-cell"><strong>{{ row.ipv6 }}</strong></div><span v-else :class="['public-badge', row.ip_type === 'private' ? 'private' : '']">{{ row.ip_type === 'private' ? 'Nội bộ' : 'Công cộng' }}</span></td>
                            <td><div class="location-cell"><strong>{{ row.organization || 'Không xác định' }}</strong><small v-if="row.asn">{{ row.asn }}</small><span>{{ location(row) }}</span><small>{{ row.continent || '—' }}</small></div></td>
                            <td class="device-cell"><strong>{{ row.device || 'Unknown' }}</strong></td>
                            <td class="browser-cell">{{ row.browser || 'Unknown' }}</td>
                            <td class="os-cell">{{ row.os || 'Unknown' }}</td>
                            <td><button type="button" :class="['detail-btn', ipStatus(row) === 'Đáng chú ý' ? 'warning' : '']" @click="selected = row">Chi tiết</button></td>
                        </tr>
                        <tr v-if="!props.logs.data.length"><td colspan="8" class="empty-state"><i class="bi bi-globe2"/><strong>Chưa có lượt truy cập</strong><span>Dữ liệu sẽ xuất hiện sau khi có khách truy cập website.</span></td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div v-if="selected" class="modal d-block ip-modal-backdrop" @click.self="selected = null">
            <div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content ip-detail-modal">
                <div class="modal-header"><div><div class="modal-kicker">THÔNG TIN ĐỊA CHỈ IP</div><h5 class="mb-0">{{ selected.ipv4 || selected.ipv6 }}</h5></div><button class="btn-close" @click="selected = null"/></div>
                <div class="modal-body"><div class="detail-grid">
                    <div><span>Loại IP</span><strong>{{ selected.ip_type || 'Không xác định' }}</strong></div><div><span>Nhà mạng / tổ chức</span><strong>{{ selected.organization || 'Không xác định' }}</strong></div>
                    <div><span>ASN</span><strong>{{ selected.asn || '—' }}</strong></div><div><span>PTR</span><strong>{{ selected.ptr || 'Không có PTR' }}</strong></div>
                    <div><span>Địa điểm</span><strong>{{ location(selected) }}</strong></div><div><span>Khu vực</span><strong>{{ selected.continent || '—' }}</strong></div>
                    <div><span>Thiết bị</span><strong>{{ selected.device || 'Unknown' }}</strong></div><div><span>Trình duyệt</span><strong>{{ selected.browser || 'Unknown' }}</strong></div>
                    <div><span>Hệ điều hành</span><strong>{{ selected.os || 'Unknown' }}</strong></div><div><span>Thời gian</span><strong>{{ formatDate(selected.created_at) }}</strong></div>
                </div><div class="security-line"><span :class="selected.is_proxy || selected.is_vpn || selected.is_tor ? 'danger' : 'safe'"><i :class="['bi', selected.is_proxy || selected.is_vpn || selected.is_tor ? 'bi-exclamation-triangle' : 'bi-shield-check']"/>{{ ipStatus(selected) }}</span><small v-if="selected.is_proxy || selected.is_vpn || selected.is_tor">Phát hiện dấu hiệu proxy, VPN hoặc Tor.</small><small v-else>Không phát hiện dấu hiệu proxy, VPN hoặc Tor từ nguồn tra cứu.</small></div></div>
                <div class="modal-footer"><button class="btn btn-light" @click="selected = null">Đóng</button></div>
            </div></div>
        </div>
    </div>
</template>

<style scoped>
.ip-page{max-width:1500px}.ip-head{display:flex;justify-content:space-between;align-items:end;gap:20px;margin-bottom:18px}.ip-head h1{margin:3px 0;font-size:1.8rem;font-weight:900;letter-spacing:-.04em}.ip-head p{margin:0;color:#667085;font-size:.8rem}.admin-kicker{color:#2563eb;font-size:.68rem;font-weight:900;letter-spacing:.14em}.current-ip{display:flex;flex-direction:column;align-items:flex-end;padding:8px 12px;border:1px solid #e5e9f0;border-radius:12px;background:#fff}.current-ip span{color:#98a2b3;font-size:.58rem}.current-ip strong{margin-top:2px;color:#2563eb;font-size:.78rem;font-family:ui-monospace,SFMono-Regular,Menlo,monospace}.lookup-panel,.country-panel{padding:15px 17px 18px;border:1px solid #e5e9f0;border-radius:17px;background:#fff;box-shadow:0 8px 28px rgba(16,24,40,.05);margin-bottom:18px}.panel-heading{padding-bottom:11px;border-bottom:1px solid #edf0f4}.panel-heading h2{margin:0;font-size:.84rem;font-weight:900}.panel-heading h2 i{color:#334155;margin-right:4px}.panel-heading p{margin:3px 0 0;color:#98a2b3;font-size:.63rem}.lookup-form{display:flex;gap:9px;margin-top:14px}.lookup-form input{flex:1;min-width:0;height:40px;padding:0 12px;border:1px solid #d0d5dd;border-radius:10px;outline:none;font-size:.72rem;font-family:ui-monospace,SFMono-Regular,Menlo,monospace}.lookup-form input:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1)}.lookup-btn{height:40px;padding:0 15px;border:0;border-radius:10px;background:#2563eb;color:#fff;font-size:.68rem;font-weight:800}.lookup-btn:disabled{opacity:.65;cursor:not-allowed}.lookup-error{display:flex;align-items:center;gap:6px;margin-top:9px;color:#b42318;font-size:.64rem}.lookup-result{margin-top:13px;padding:13px;border:1px solid #dbeafe;border-radius:13px;background:#f8fbff}.lookup-result-head{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:10px}.lookup-result-head>div{display:flex;flex-direction:column}.result-kicker{color:#667085;font-size:.55rem;font-weight:900;letter-spacing:.12em}.lookup-result-head strong{margin-top:2px;color:#2563eb;font-size:.78rem;font-family:ui-monospace,SFMono-Regular,Menlo,monospace}.lookup-status{display:inline-flex;align-items:center;gap:4px;padding:4px 7px;border-radius:999px;background:#ecfdf3;color:#15803d;font-size:.56rem;font-weight:800}.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}.detail-grid>div{padding:11px 12px;border:1px solid #edf0f4;border-radius:12px;background:#f8fafc}.detail-grid span{display:block;color:#98a2b3;font-size:.58rem}.detail-grid strong{display:block;margin-top:3px;color:#344054;font-size:.7rem;word-break:break-word}.lookup-grid>div{background:#fff}.lookup-grid{gap:8px}.security-line{display:flex;align-items:center;gap:8px;margin-top:10px;padding:10px 12px;border-radius:12px;background:#fff}.security-line>span{display:inline-flex;align-items:center;gap:5px;padding:4px 7px;border-radius:999px;font-size:.58rem;font-weight:800}.security-line .safe{background:#ecfdf3;color:#15803d}.security-line .danger{background:#fff7ed;color:#b45309}.security-line small{color:#667085;font-size:.6rem}.country-grid{display:grid;grid-template-columns:1fr 1fr;column-gap:22px}.country-row{display:flex;gap:10px;align-items:flex-start;padding:10px 0;border-bottom:1px solid #edf0f4}.country-rank{display:grid;flex:0 0 21px;width:21px;height:21px;place-items:center;border-radius:7px;background:#f1f5f9;color:#334155;font-size:.62rem;font-weight:900}.country-main{min-width:0;flex:1}.country-label{display:flex;justify-content:space-between;gap:12px}.country-label strong{font-size:.69rem}.country-label b{font-size:.7rem}.country-bar{height:6px;margin-top:7px;border-radius:99px;background:#e9eef5;overflow:hidden}.country-bar span{display:block;height:100%;border-radius:99px;background:#2563eb}.country-main small{display:block;margin-top:5px;color:#2563eb;font-size:.56rem}.country-empty{padding:25px 0;text-align:center;color:#98a2b3;font-size:.7rem}.stats-row{margin-bottom:15px}.ip-stat{position:relative;overflow:hidden;min-height:102px;padding:19px 20px;border:1px solid #e5e9f0;border-radius:17px;background:#fff;box-shadow:0 8px 28px rgba(16,24,40,.05)}.ip-stat span{display:block;color:#667085;font-size:.68rem}.ip-stat strong{display:block;margin-top:4px;color:#101828;font-size:1.45rem;line-height:1}.ip-stat i{position:absolute;right:-10px;bottom:-17px;display:grid;width:58px;height:58px;place-items:center;border-radius:50%;background:#eff6ff;color:#2563eb;font-size:18px}.source-note{display:flex;align-items:center;gap:8px;margin-bottom:12px;padding:9px 12px;border:1px solid #dbeafe;border-radius:11px;background:#f8fbff;color:#64748b;font-size:.64rem}.source-note i{color:#2563eb;font-size:14px}.admin-panel{border:1px solid #e5e9f0;border-radius:16px;background:#fff;box-shadow:0 8px 28px rgba(16,24,40,.05)}.ip-table{min-width:1160px;margin:0}.ip-table th{padding:8px 8px;background:#f8fafc;color:#667085;font-size:.61rem;font-weight:900;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;border-bottom:1px solid #e5e9f0}.ip-table td{padding:9px 8px;font-size:.7rem;border-color:#e5e9f0}.ip-table tbody tr:hover{background:#fbfdff}.time-cell{white-space:nowrap;color:#101828}.ip-cell{display:flex;min-width:205px;flex-direction:column}.ip-cell strong{color:#2563eb;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.69rem}.ip-cell small{margin-top:2px;color:#64748b;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.54rem;white-space:nowrap}.public-badge{display:inline-flex;width:max-content;padding:4px 8px;border:1px solid #9bd9c7;border-radius:999px;background:#e7f7f1;color:#087f5b;font-size:.57rem;font-weight:800}.public-badge.private{border-color:#e5e7eb;background:#f3f4f6;color:#667085}.location-cell{display:flex;min-width:190px;flex-direction:column}.location-cell strong{font-size:.68rem}.location-cell span{margin-top:3px;white-space:nowrap}.location-cell small{margin-top:2px;color:#98a2b3;font-size:.56rem}.device-cell strong{font-size:.68rem}.browser-cell,.os-cell{white-space:nowrap}.detail-btn{min-width:56px;padding:5px 8px;border:1px solid #2563eb;border-radius:9px;background:#fff;color:#2563eb;font-size:.6rem;font-weight:800}.detail-btn:hover{background:#eff6ff}.detail-btn.warning{border-color:#d97706;color:#b45309}.empty-state{padding:45px!important;text-align:center;color:#98a2b3}.empty-state i,.empty-state strong,.empty-state span{display:block}.empty-state i{margin-bottom:7px;color:#2563eb;font-size:26px}.empty-state strong{color:#667085;font-size:.78rem}.empty-state span{margin-top:3px;font-size:.63rem}.ip-modal-backdrop{background:rgba(15,23,42,.5);backdrop-filter:blur(4px)}.ip-detail-modal{overflow:hidden;border:0;border-radius:20px;box-shadow:0 24px 70px rgba(15,23,42,.25)}.modal-kicker{margin-bottom:3px;color:#4f46e5;font-size:8px;font-weight:900;letter-spacing:.14em}@media(max-width:767px){.ip-head{align-items:stretch;flex-direction:column}.current-ip{align-items:flex-start}.country-grid,.detail-grid{grid-template-columns:1fr}.lookup-form{flex-direction:column}.lookup-btn{width:100%}.source-note{align-items:flex-start}}
</style>
