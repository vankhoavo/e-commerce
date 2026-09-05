<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

type Props = { status: number };
const props = defineProps<Props>();

type ErrorCopy = { title: string; description: string; icon: string; label: string };

const errorCopy: Record<number, ErrorCopy> = {
    400: { title: 'Yêu cầu không hợp lệ', description: 'Yêu cầu gửi tới TechStore chưa đúng định dạng hoặc thiếu thông tin cần thiết.', icon: 'bi-file-earmark-x-fill', label: '400 · Yêu cầu không hợp lệ' },
    401: { title: 'Bạn chưa đăng nhập', description: 'Bạn cần đăng nhập để tiếp tục truy cập tài nguyên này.', icon: 'bi-person-lock', label: '401 · Cần xác thực' },
    403: { title: 'Bạn không có quyền', description: 'Tài khoản hiện tại không được phép truy cập khu vực hoặc chức năng này.', icon: 'bi-shield-lock-fill', label: '403 · Truy cập bị từ chối' },
    404: { title: 'Không tìm thấy trang', description: 'Liên kết có thể đã thay đổi, bị xóa hoặc địa chỉ bạn nhập không còn tồn tại.', icon: 'bi-compass-fill', label: '404 · Không tìm thấy' },
    405: { title: 'Phương thức không được hỗ trợ', description: 'Thao tác này không được hỗ trợ tại địa chỉ hiện tại. Hãy quay lại và thử lại.', icon: 'bi-arrow-left-right', label: '405 · Phương thức không hợp lệ' },
    408: { title: 'Yêu cầu đã quá thời gian', description: 'Máy chủ chờ yêu cầu quá lâu. Vui lòng thử lại.', icon: 'bi-hourglass-split', label: '408 · Hết thời gian chờ' },
    409: { title: 'Yêu cầu đang xung đột', description: 'Dữ liệu hiện tại có thể đã thay đổi. Vui lòng tải lại trang và thử lại.', icon: 'bi-arrow-repeat', label: '409 · Xung đột dữ liệu' },
    410: { title: 'Tài nguyên không còn tồn tại', description: 'Nội dung bạn đang tìm đã được gỡ khỏi TechStore.', icon: 'bi-box2-heart-fill', label: '410 · Không còn tồn tại' },
    419: { title: 'Phiên đã hết hạn', description: 'Yêu cầu hiện tại không còn hợp lệ. Hãy tải lại trang và thực hiện lại thao tác.', icon: 'bi-clock-history', label: '419 · Phiên hết hạn' },
    422: { title: 'Dữ liệu chưa hợp lệ', description: 'Thông tin gửi lên chưa đáp ứng yêu cầu của TechStore. Kiểm tra lại và thử lần nữa.', icon: 'bi-exclamation-diamond-fill', label: '422 · Không thể xử lý' },
    429: { title: 'Bạn đang thao tác quá nhanh', description: 'Hệ thống đang giới hạn tạm thời để bảo vệ tài khoản và dịch vụ. Vui lòng thử lại sau ít phút.', icon: 'bi-speedometer2', label: '429 · Quá nhiều yêu cầu' },
    500: { title: 'TechStore đang gặp sự cố', description: 'Máy chủ không thể hoàn tất yêu cầu vừa rồi. Vui lòng thử lại sau.', icon: 'bi-cpu-fill', label: '500 · Lỗi máy chủ' },
    501: { title: 'Tính năng chưa sẵn sàng', description: 'Máy chủ chưa hỗ trợ thao tác này. Tính năng có thể đang trong quá trình phát triển.', icon: 'bi-tools', label: '501 · Chưa được hỗ trợ' },
    502: { title: 'Kết nối máy chủ gặp sự cố', description: 'TechStore nhận được phản hồi không hợp lệ từ một dịch vụ trung gian. Vui lòng thử lại.', icon: 'bi-diagram-3-fill', label: '502 · Phản hồi không hợp lệ' },
    503: { title: 'Dịch vụ tạm thời bận', description: 'Một thành phần của TechStore chưa sẵn sàng. Vui lòng thử lại sau giây lát.', icon: 'bi-cone-striped', label: '503 · Tạm thời gián đoạn' },
    504: { title: 'Máy chủ phản hồi quá chậm', description: 'TechStore chưa nhận được phản hồi đúng thời gian. Vui lòng thử lại sau.', icon: 'bi-stopwatch-fill', label: '504 · Hết thời gian máy chủ' },
};

const fallback: ErrorCopy = {
    title: 'Đã xảy ra lỗi',
    description: 'TechStore không thể hoàn tất yêu cầu hiện tại. Vui lòng quay lại và thử lại.',
    icon: 'bi-cloud-exclamation-fill',
    label: `${props.status} · Có lỗi xảy ra`,
};

const copy = errorCopy[props.status] ?? fallback;

function goBack(): void {
    if (typeof window !== 'undefined' && window.history.length > 1) {
        window.history.back();
        return;
    }
    window.location.assign('/');
}
</script>

<template>
    <Head :title="`${props.status} · TechStore`" />
    <main class="error-page">
        <div class="error-orb error-orb-one" aria-hidden="true"></div>
        <div class="error-orb error-orb-two" aria-hidden="true"></div>
        <div class="container position-relative py-4 py-lg-5">
            <div class="error-shell">
                <header class="error-brand-row">
                    <Link href="/" class="error-brand">
                        <span class="error-brand-mark"><i class="bi bi-cpu-fill"></i></span>
                        <span><strong>TechStore</strong><small>CÔNG NGHỆ &amp; LINH KIỆN</small></span>
                    </Link>
                    <span class="error-code">MÃ {{ String(props.status).padStart(3, '0') }}</span>
                </header>

                <section class="error-content">
                    <div class="error-visual" aria-hidden="true">
                        <div class="error-grid"></div>
                        <div class="error-icon-ring"><div class="error-icon-core"><i :class="['bi', copy.icon]"></i></div></div>
                        <div class="error-mini-code"><span v-for="bit in 8" :key="bit"></span></div>
                    </div>

                    <div class="error-copy">
                        <span class="error-kicker"><i class="bi bi-exclamation-circle-fill"></i>{{ copy.label }}</span>
                        <div class="error-number">{{ props.status }}</div>
                        <h1>{{ copy.title }}</h1>
                        <p>{{ copy.description }}</p>
                        <div class="error-actions">
                            <Link href="/" class="btn error-primary"><i class="bi bi-house-door-fill"></i>Về trang chủ</Link>
                            <button type="button" class="btn error-secondary" @click="goBack"><i class="bi bi-arrow-left"></i>Quay lại</button>
                        </div>
                    </div>
                </section>

                <footer class="error-footer">
                    <span><i class="bi bi-shield-check"></i> Hệ thống TechStore</span>
                    <span>Mã lỗi: {{ props.status }}</span>
                </footer>
            </div>
        </div>
    </main>
</template>

<style scoped>
.error-page{position:relative;min-height:100vh;display:flex;align-items:center;overflow:hidden;background:radial-gradient(circle at 10% 12%,rgba(37,99,235,.12),transparent 28%),radial-gradient(circle at 90% 88%,rgba(124,58,237,.1),transparent 30%),linear-gradient(180deg,#f8fbff 0%,#f2f5fa 100%);color:#101828}.error-orb{position:absolute;border-radius:999px;pointer-events:none;filter:blur(5px)}.error-orb-one{width:360px;height:360px;top:-190px;left:-130px;background:radial-gradient(circle,rgba(96,165,250,.18),transparent 68%)}.error-orb-two{width:430px;height:430px;right:-170px;bottom:-230px;background:radial-gradient(circle,rgba(167,139,250,.17),transparent 68%)}
.error-shell{width:min(1050px,100%);margin:0 auto;overflow:hidden;border:1px solid rgba(255,255,255,.92);border-radius:30px;background:rgba(255,255,255,.9);box-shadow:0 28px 90px rgba(15,23,42,.12);backdrop-filter:blur(18px)}.error-brand-row{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:22px 28px;border-bottom:1px solid #edf1f6}.error-brand{display:inline-flex;align-items:center;gap:11px;color:#101828;text-decoration:none}.error-brand-mark{display:grid;width:42px;height:42px;place-items:center;border-radius:13px;color:#fff;background:linear-gradient(135deg,#2563eb,#7c3aed);box-shadow:0 10px 24px rgba(37,99,235,.22)}.error-brand strong{display:block;font-size:17px;font-weight:900;letter-spacing:-.03em}.error-brand small{display:block;margin-top:3px;color:#98a2b3;font-size:7px;font-weight:900;letter-spacing:.15em}.error-code{padding:8px 11px;border:1px solid #dbe7ff;border-radius:999px;color:#2563eb;background:#f6f9ff;font-size:9px;font-weight:900;letter-spacing:.1em}
.error-content{display:grid;grid-template-columns:320px 1fr;gap:48px;align-items:center;padding:52px 58px 58px}.error-visual{position:relative;display:flex;min-height:320px;align-items:center;justify-content:center;overflow:hidden;border:1px solid #e6edf7;border-radius:28px;background:linear-gradient(145deg,#fafcff,#f3f6fb)}.error-visual::before{content:"";position:absolute;inset:20px;border:1px dashed #d9e3f2;border-radius:22px}.error-grid{position:absolute;inset:0;opacity:.45;background-image:linear-gradient(#e7edf6 1px,transparent 1px),linear-gradient(90deg,#e7edf6 1px,transparent 1px);background-size:32px 32px;mask-image:linear-gradient(to bottom,transparent,#000 20%,#000 80%,transparent)}.error-icon-ring{position:relative;z-index:1;display:grid;width:180px;height:180px;place-items:center;border-radius:50%;background:radial-gradient(circle,#fff 55%,#edf4ff 56%,#edf4ff 65%,transparent 66%);box-shadow:0 24px 65px rgba(37,99,235,.13)}.error-icon-core{display:grid;width:116px;height:116px;place-items:center;border:1px solid #d6e4ff;border-radius:32px;color:#2563eb;background:#fff;box-shadow:0 15px 38px rgba(37,99,235,.12);font-size:43px}.error-mini-code{position:absolute;left:32px;right:32px;bottom:27px;display:grid;grid-template-columns:repeat(8,1fr);gap:5px}.error-mini-code span{height:4px;border-radius:99px;background:#dce7f6}.error-mini-code span:nth-child(2n){background:#c8dafa}
.error-copy{max-width:570px}.error-kicker{display:inline-flex;align-items:center;gap:6px;padding:7px 10px;border-radius:999px;color:#2563eb;background:#edf4ff;font-size:9px;font-weight:900;letter-spacing:.04em}.error-number{margin-top:10px;color:#dce5f2;font-size:clamp(4.8rem,9vw,7.4rem);font-weight:950;letter-spacing:-.09em;line-height:.82}.error-copy h1{margin:13px 0 10px;color:#101828;font-size:clamp(1.9rem,4vw,3rem);line-height:1.05;font-weight:900;letter-spacing:-.05em}.error-copy p{max-width:520px;margin:0;color:#667085;font-size:.92rem;line-height:1.75}.error-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:24px}.error-primary,.error-secondary{min-height:45px;padding:0 17px;border-radius:11px;font-size:.75rem;font-weight:850;transition:.18s ease}.error-primary{display:inline-flex;align-items:center;gap:8px;color:#fff;background:linear-gradient(135deg,#2563eb,#4f46e5);box-shadow:0 10px 22px rgba(37,99,235,.18)}.error-primary:hover{color:#fff;transform:translateY(-1px);box-shadow:0 14px 28px rgba(37,99,235,.24)}.error-secondary{display:inline-flex;align-items:center;gap:8px;border:1px solid #dfe5ee;color:#344054;background:#fff}.error-secondary:hover{border-color:#bfd1f5;color:#2563eb;background:#f8fbff}.error-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:15px 28px;border-top:1px solid #edf1f6;color:#98a2b3;font-size:10px}.error-footer span{display:inline-flex;align-items:center;gap:6px}.error-footer i{color:#16a34a}
@media(max-width:767px){.error-page{align-items:flex-start}.error-brand-row{padding:18px 16px}.error-content{grid-template-columns:1fr;gap:26px;padding:28px 16px 34px}.error-visual{min-height:245px}.error-copy{max-width:none}.error-number{font-size:5rem}.error-copy h1{font-size:2rem}.error-footer{padding:13px 16px;align-items:flex-start;flex-direction:column}.error-code{font-size:8px}}
</style>
