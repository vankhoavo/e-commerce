<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

type Props = {
    status: number;
};

const props = defineProps<Props>();

const errorCopy: Record<number, { title: string; description: string; icon: string; label: string }> = {
    401: {
        title: 'Bạn chưa đăng nhập',
        description: 'Phiên làm việc hiện tại không có quyền truy cập tài nguyên này. Hãy đăng nhập và thử lại.',
        icon: 'bi-person-lock',
        label: 'Cần xác thực',
    },
    403: {
        title: 'Bạn không có quyền',
        description: 'Tài khoản hiện tại không được phép truy cập khu vực hoặc chức năng này.',
        icon: 'bi-shield-lock-fill',
        label: 'Truy cập bị từ chối',
    },
    404: {
        title: 'Không tìm thấy trang',
        description: 'Liên kết có thể đã thay đổi, bị xóa hoặc địa chỉ bạn nhập không còn tồn tại.',
        icon: 'bi-compass-fill',
        label: '404 · Không tìm thấy',
    },
    419: {
        title: 'Phiên đã hết hạn',
        description: 'Yêu cầu hiện tại không còn hợp lệ. Hãy tải lại trang và thực hiện lại thao tác.',
        icon: 'bi-clock-history',
        label: '419 · Phiên hết hạn',
    },
    422: {
        title: 'Dữ liệu chưa hợp lệ',
        description: 'Thông tin gửi lên chưa đáp ứng yêu cầu của TechStore. Kiểm tra lại và thử lần nữa.',
        icon: 'bi-exclamation-diamond-fill',
        label: '422 · Không thể xử lý',
    },
    429: {
        title: 'Bạn đang thao tác quá nhanh',
        description: 'Hệ thống đang giới hạn tạm thời để bảo vệ tài khoản và dịch vụ. Vui lòng thử lại sau ít phút.',
        icon: 'bi-speedometer2',
        label: '429 · Quá nhiều yêu cầu',
    },
    500: {
        title: 'TechStore đang gặp sự cố',
        description: 'Máy chủ không thể hoàn tất yêu cầu vừa rồi. Vui lòng thử lại sau.',
        icon: 'bi-cpu-fill',
        label: '500 · Lỗi máy chủ',
    },
    503: {
        title: 'Dịch vụ tạm thời bận',
        description: 'Một thành phần của TechStore chưa sẵn sàng. Vui lòng thử lại sau giây lát.',
        icon: 'bi-cone-striped',
        label: '503 · Tạm thời gián đoạn',
    },
};

const fallback = {
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

        <div class="container position-relative py-5">
            <div class="error-shell">
                <header class="error-brand-row">
                    <Link href="/" class="error-brand">
                        <span class="error-brand-mark"><i class="bi bi-cpu-fill"></i></span>
                        <span>
                            <strong>TechStore</strong>
                            <small>CÔNG NGHỆ &amp; LINH KIỆN</small>
                        </span>
                    </Link>
                    <span class="error-code">{{ String(props.status).padStart(3, '0') }}</span>
                </header>

                <section class="error-content">
                    <div class="error-visual" aria-hidden="true">
                        <div class="error-icon-ring">
                            <div class="error-icon-core">
                                <i :class="['bi', copy.icon]"></i>
                            </div>
                        </div>
                        <div class="error-mini-code">
                            <span v-for="bit in 8" :key="bit"></span>
                        </div>
                    </div>

                    <div class="error-copy">
                        <span class="error-kicker">{{ copy.label }}</span>
                        <div class="error-number">{{ props.status }}</div>
                        <h1>{{ copy.title }}</h1>
                        <p>{{ copy.description }}</p>

                        <div class="error-actions">
                            <Link href="/" class="btn error-primary">
                                <i class="bi bi-house-door-fill"></i>
                                Về trang chủ
                            </Link>
                            <button type="button" class="btn error-secondary" @click="goBack">
                                <i class="bi bi-arrow-left"></i>
                                Quay lại
                            </button>
                        </div>
                    </div>
                </section>

                <footer class="error-footer">
                    <span><i class="bi bi-shield-check"></i> TechStore luôn sẵn sàng hỗ trợ</span>
                    <span>Mã lỗi: {{ props.status }}</span>
                </footer>
            </div>
        </div>
    </main>
</template>

<style scoped>
.error-page{position:relative;min-height:calc(100vh - 108px);display:flex;align-items:center;overflow:hidden;background:radial-gradient(circle at 12% 18%,rgba(37,99,235,.12),transparent 26%),radial-gradient(circle at 84% 82%,rgba(124,58,237,.1),transparent 28%),linear-gradient(180deg,#f8fbff 0%,#f3f6fb 100%)}
.error-orb{position:absolute;border-radius:999px;filter:blur(4px);opacity:.75}.error-orb-one{width:280px;height:280px;top:-90px;left:-80px;background:radial-gradient(circle,rgba(96,165,250,.18),rgba(96,165,250,0))}.error-orb-two{width:340px;height:340px;right:-100px;bottom:-120px;background:radial-gradient(circle,rgba(167,139,250,.18),rgba(167,139,250,0))}
.error-shell{width:min(1020px,100%);margin:0 auto;overflow:hidden;border:1px solid rgba(255,255,255,.85);border-radius:30px;background:rgba(255,255,255,.88);box-shadow:0 30px 90px rgba(15,23,42,.1);backdrop-filter:blur(18px)}
.error-brand-row{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:24px 28px;border-bottom:1px solid #edf1f6}.error-brand{display:inline-flex;align-items:center;gap:11px;color:#101828;text-decoration:none}.error-brand-mark{display:grid;width:42px;height:42px;place-items:center;border-radius:13px;color:#fff;background:linear-gradient(135deg,#2563eb,#7c3aed);box-shadow:0 10px 24px rgba(37,99,235,.22)}.error-brand strong{display:block;font-size:17px;font-weight:900;letter-spacing:-.03em}.error-brand small{display:block;margin-top:3px;color:#98a2b3;font-size:7px;font-weight:900;letter-spacing:.15em}.error-code{padding:8px 11px;border:1px solid #dbe7ff;border-radius:999px;color:#2563eb;background:#f6f9ff;font-size:10px;font-weight:900;letter-spacing:.12em}
.error-content{display:grid;grid-template-columns:300px 1fr;gap:42px;align-items:center;padding:54px 58px 60px}.error-visual{display:flex;min-height:310px;align-items:center;justify-content:center;position:relative;border:1px solid #e8eef7;border-radius:28px;background:linear-gradient(145deg,#f9fbff,#f4f7fc);overflow:hidden}.error-visual::before{content:"";position:absolute;inset:22px;border:1px dashed #dbe5f4;border-radius:22px}.error-icon-ring{position:relative;z-index:1;display:grid;width:172px;height:172px;place-items:center;border-radius:50%;background:radial-gradient(circle,#fff 56%,#edf4ff 57%,#edf4ff 64%,transparent 65%);box-shadow:0 22px 60px rgba(37,99,235,.12)}.error-icon-core{display:grid;width:112px;height:112px;place-items:center;border:1px solid #d7e5ff;border-radius:32px;color:#2563eb;background:#fff;box-shadow:0 14px 36px rgba(37,99,235,.12);font-size:42px}.error-mini-code{position:absolute;left:30px;right:30px;bottom:28px;display:grid;grid-template-columns:repeat(8,1fr);gap:5px}.error-mini-code span{height:4px;border-radius:99px;background:#dbe7f7}.error-mini-code span:nth-child(2n){background:#c6d8fa}
.error-copy{max-width:560px}.error-kicker{display:inline-flex;align-items:center;padding:7px 10px;border-radius:999px;color:#2563eb;background:#edf4ff;font-size:9px;font-weight:900;letter-spacing:.08em}.error-number{margin-top:11px;color:#dbe4f2;font-size:clamp(4.6rem,9vw,7.2rem);font-weight:950;letter-spacing:-.08em;line-height:.84}.error-copy h1{margin:10px 0 10px;color:#101828;font-size:clamp(1.9rem,4vw,3rem);line-height:1.05;font-weight:900;letter-spacing:-.05em}.error-copy p{max-width:520px;color:#667085;font-size:.95rem;line-height:1.75}.error-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:23px}.error-primary,.error-secondary{min-height:44px;padding:0 16px;border-radius:11px;font-size:.75rem;font-weight:850}.error-primary{display:inline-flex;align-items:center;gap:8px;color:#fff;background:linear-gradient(135deg,#2563eb,#4f46e5);box-shadow:0 10px 22px rgba(37,99,235,.18)}.error-primary:hover{color:#fff;transform:translateY(-1px);box-shadow:0 14px 28px rgba(37,99,235,.24)}.error-secondary{display:inline-flex;align-items:center;gap:8px;border:1px solid #dfe5ee;color:#344054;background:#fff}.error-secondary:hover{border-color:#bfd1f5;color:#2563eb;background:#f8fbff}.error-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:15px 28px;border-top:1px solid #edf1f6;color:#98a2b3;font-size:10px}.error-footer span{display:inline-flex;align-items:center;gap:6px}.error-footer i{color:#16a34a}
@media(max-width:767px){.error-page{min-height:calc(100vh - 84px)}.error-brand-row{padding:18px 16px}.error-content{grid-template-columns:1fr;gap:26px;padding:28px 16px 34px}.error-visual{min-height:250px}.error-copy{max-width:none}.error-number{font-size:5rem}.error-copy h1{font-size:2rem}.error-footer{padding:13px 16px;align-items:flex-start;flex-direction:column}.error-code{font-size:9px}}
</style>
