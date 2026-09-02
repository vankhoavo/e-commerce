<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { formatPrice } from '@/data/products';
import { getCartStorageKey, migrateLegacyCart, readCart, type CartItem } from '@/lib/cart';

const page = usePage();
const authUser = computed(() => (page.props as any).auth?.user ?? null);
const userId = computed<number | null>(() => authUser.value?.id ? Number(authUser.value.id) : null);
const cart = ref<CartItem[]>([]);
const submitted = ref(false);
const orderCode = ref('');
const shippingError = ref('');
const demoPaypalOpen = ref(false);
const demoPaypalProcessing = ref(false);
const demoPaypalError = ref('');
const form = ref({ name: '', phone: '', email: '', address: '', note: '', payment: 'cod' });

const SHIPPING_FEE = 30000;
const DEMO_USD_TO_VND = 1;

function loadCart() {
    if (!userId.value) {
        cart.value = [];
        return;
    }

    migrateLegacyCart(userId.value);
    cart.value = readCart(userId.value);
    form.value.name = authUser.value?.name ?? '';
    form.value.email = authUser.value?.email ?? '';
}

const itemCount = computed(() => cart.value.reduce((sum, item) => sum + item.quantity, 0));
const subtotal = computed(() => cart.value.reduce((sum, item) => sum + item.price * item.quantity, 0));
const shippingFee = computed(() => cart.value.length ? SHIPPING_FEE : 0);
const totalShipping = computed(() => shippingFee.value);
const total = computed(() => subtotal.value + totalShipping.value);
const demoPaypalUsd = computed(() => total.value / DEMO_USD_TO_VND);

function validateShipping() {
    if (!form.value.name.trim() || !form.value.phone.trim() || !form.value.address.trim()) {
        shippingError.value = 'Vui lòng nhập đầy đủ họ tên, số điện thoại và địa chỉ nhận hàng.';
        return false;
    }
    shippingError.value = '';
    return true;
}

function clearUserCart() {
    if (!userId.value) return;
    localStorage.removeItem(getCartStorageKey(userId.value));
    window.dispatchEvent(new Event('techstore-cart-updated'));
}

function saveOrder(payment: string, paypalOrderId?: string) {
    orderCode.value = `TS${Date.now().toString().slice(-8)}`;
    const order = {
        code: orderCode.value,
        createdAt: new Date().toISOString(),
        customer: { ...form.value, payment },
        items: cart.value,
        subtotal: subtotal.value,
        shipping: shippingFee.value,
        totalShipping: totalShipping.value,
        total: total.value,
        payment,
        paypalOrderId: paypalOrderId ?? null,
        status: payment === 'paypal-demo' ? 'Đã thanh toán mô phỏng' : 'Chờ xử lý',
    };

    if (userId.value) {
        localStorage.setItem(`techstore_last_order_user_${userId.value}`, JSON.stringify(order));
    }
    clearUserCart();
    submitted.value = true;
    demoPaypalOpen.value = false;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function submitCod() {
    if (!cart.value.length || !validateShipping()) return;
    saveOrder('cod');
}

function openDemoPaypal() {
    demoPaypalError.value = '';
    if (!validateShipping()) return;
    demoPaypalOpen.value = true;
}

function cancelDemoPaypal() {
    if (demoPaypalProcessing.value) return;
    demoPaypalOpen.value = false;
    demoPaypalError.value = '';
}

function confirmDemoPaypal() {
    if (!validateShipping()) return;
    demoPaypalProcessing.value = true;
    demoPaypalError.value = '';

    window.setTimeout(() => {
        try {
            const demoId = `PAYPAL-DEMO-${Date.now().toString().slice(-10)}`;
            saveOrder('paypal-demo', demoId);
        } catch {
            demoPaypalError.value = 'Không thể hoàn tất thanh toán mô phỏng. Vui lòng thử lại.';
        } finally {
            demoPaypalProcessing.value = false;
        }
    }, 700);
}

onMounted(loadCart);
</script>

<template>
    <Head title="Thanh toán" />
    <div class="checkout-page">
        <div class="container checkout-container">
            <nav class="checkout-breadcrumb"><Link href="/">Trang chủ</Link><i class="bi bi-chevron-right"/><Link href="/cart">Giỏ hàng</Link><i class="bi bi-chevron-right"/><span>Thanh toán</span></nav>

            <section v-if="submitted" class="checkout-success">
                <div class="success-icon"><i class="bi bi-check-lg"/></div>
                <span class="checkout-kicker">ĐẶT HÀNG THÀNH CÔNG</span>
                <h1>Cảm ơn bạn đã mua hàng!</h1>
                <p>Đơn hàng <strong>{{ orderCode }}</strong> đã được ghi nhận.</p>
                <div class="success-summary"><span>Tổng thanh toán</span><strong>{{ formatPrice(total) }}</strong></div>
                <Link href="/products" class="success-btn">Tiếp tục mua sắm <i class="bi bi-arrow-right"/></Link>
            </section>

            <template v-else-if="cart.length">
                <div class="checkout-heading"><div><span class="checkout-kicker">TECHSTORE · CHECKOUT</span><h1>Đặt hàng &amp; vận chuyển</h1><p>Điền thông tin nhận hàng và chọn phương thức thanh toán.</p></div><span class="checkout-count"><i class="bi bi-bag"/> {{ itemCount }} sản phẩm</span></div>

                <form class="row g-4" @submit.prevent="submitCod">
                    <section class="col-lg-7">
                        <div class="checkout-card">
                            <div class="checkout-card-title"><span>1</span><div><strong>Thông tin nhận hàng</strong><small>Đơn hàng sẽ được giao đến thông tin bên dưới.</small></div></div>
                            <div class="row g-3">
                                <div class="col-md-6"><label>Họ và tên <b>*</b></label><input v-model="form.name" required type="text" autocomplete="name" placeholder="Nguyễn Văn A"/></div>
                                <div class="col-md-6"><label>Số điện thoại <b>*</b></label><input v-model="form.phone" required type="tel" autocomplete="tel" pattern="[0-9+ .-]{8,20}" placeholder="0901 234 567"/></div>
                                <div class="col-12"><label>Email</label><input v-model="form.email" type="email" autocomplete="email" placeholder="email@example.com"/></div>
                                <div class="col-12"><label>Địa chỉ nhận hàng <b>*</b></label><textarea v-model="form.address" required rows="3" autocomplete="street-address" placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành phố"/></div>
                                <div class="col-12"><label>Ghi chú</label><textarea v-model="form.note" rows="2" placeholder="Ví dụ: Giao giờ hành chính..."/></div>
                            </div>
                            <div v-if="shippingError" class="checkout-error"><i class="bi bi-exclamation-circle"/> {{ shippingError }}</div>
                        </div>
                    </section>

                    <aside class="col-lg-5">
                        <div class="checkout-order-card">
                            <div class="order-card-title">Tóm tắt đơn hàng</div>
                            <div v-for="item in cart" :key="item.id" class="order-item"><div class="order-image"><img :src="item.image" :alt="item.name"/></div><div class="order-info"><strong>{{ item.name }}</strong><small>Số lượng: {{ item.quantity }}</small></div><b>{{ formatPrice(item.price * item.quantity) }}</b></div>
                            <div class="order-divider"/>
                            <div class="order-row"><span>Tạm tính</span><strong>{{ formatPrice(subtotal) }}</strong></div>
                            <div class="order-row"><span>Phí vận chuyển</span><strong>{{ formatPrice(shippingFee) }}</strong></div>
                            <div class="order-row shipping-total"><span>Tổng phí vận chuyển</span><strong>{{ formatPrice(totalShipping) }}</strong></div>

                            <div class="payment-block">
                                <div class="payment-block-heading"><div><span>Phương thức thanh toán</span><small>Chọn một phương thức</small></div><i class="bi bi-shield-check"/></div>

                                <label class="payment-option" :class="{ active: form.payment === 'cod' }">
                                    <span class="payment-option-icon cod-icon"><i class="bi bi-cash-stack"/></span>
                                    <span class="payment-option-copy"><strong>Thanh toán khi nhận hàng</strong><small>COD · Không phát sinh thêm phí.</small></span>
                                    <input v-model="form.payment" value="cod" type="radio" aria-label="Thanh toán khi nhận hàng"/>
                                </label>

                                <label class="payment-option" :class="{ active: form.payment === 'paypal' }">
                                    <span class="payment-option-icon paypal-icon"><i class="bi bi-paypal"/></span>
                                    <span class="payment-option-copy"><strong>PayPal mô phỏng</strong><small>Chỉ giả lập · Không dùng thẻ và không trừ tiền thật.</small></span>
                                    <input v-model="form.payment" value="paypal" type="radio" aria-label="PayPal mô phỏng"/>
                                </label>

                                <div v-if="form.payment === 'paypal'" class="demo-paypal-panel">
                                    <div class="demo-paypal-badge"><i class="bi bi-flask"/> CHẾ ĐỘ GIẢ LẬP</div>
                                    <div class="demo-paypal-row"><span>Số tiền đơn hàng</span><strong>{{ formatPrice(total) }}</strong></div>
                                    <div class="demo-paypal-row"><span>Tỷ giá mô phỏng</span><strong>1 USD = 1 VND</strong></div>
                                    <div class="demo-paypal-row demo-paypal-usd"><span>PayPal hiển thị</span><strong>$ {{ demoPaypalUsd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} USD</strong></div>
                                    <p><i class="bi bi-info-circle"/> Đây chỉ là giao dịch giả lập nội bộ của TechStore. Không kết nối PayPal thật, không yêu cầu thẻ ngân hàng và không phát sinh tiền thật.</p>
                                    <button type="button" class="demo-paypal-btn" @click="openDemoPaypal"><i class="bi bi-paypal"/> Tiếp tục thanh toán mô phỏng</button>
                                </div>
                            </div>

                            <div class="order-divider"/>
                            <div class="order-total"><span>Tổng thanh toán</span><strong>{{ formatPrice(total) }}</strong></div>
                            <button v-if="form.payment === 'cod'" type="submit" class="place-order-btn">Tiến hành đặt hàng <i class="bi bi-check2-circle"/></button>
                            <div v-else class="paypal-summary-hint"><i class="bi bi-shield-check"/> PayPal đang ở chế độ giả lập, không có giao dịch thật.</div>
                            <Link href="/cart" class="back-cart"><i class="bi bi-arrow-left"/> Quay lại giỏ hàng</Link>
                        </div>
                        <div class="checkout-safe"><i class="bi bi-shield-check"/><span><strong>Thông tin an toàn</strong><small>Thông tin nhận hàng được dùng để xử lý và giao đơn.</small></span></div>
                    </aside>
                </form>
            </template>

            <section v-else class="checkout-empty"><div><i class="bi bi-bag-x"/></div><h2>Không có sản phẩm để thanh toán</h2><p>Giỏ hàng của tài khoản đang trống.</p><Link href="/products" class="success-btn">Quay lại mua sắm <i class="bi bi-arrow-right"/></Link></section>
        </div>

        <div v-if="demoPaypalOpen" class="demo-paypal-overlay" @click.self="cancelDemoPaypal">
            <section class="demo-paypal-modal" role="dialog" aria-modal="true" aria-labelledby="demo-paypal-title">
                <button type="button" class="demo-paypal-close" aria-label="Đóng" @click="cancelDemoPaypal"><i class="bi bi-x-lg"/></button>
                <div class="demo-paypal-logo"><i class="bi bi-paypal"/></div>
                <span class="demo-paypal-kicker">PAYPAL · SANDBOX MÔ PHỎNG</span>
                <h2 id="demo-paypal-title">Xác nhận thanh toán</h2>
                <p class="demo-paypal-description">Đây là màn hình giả lập. Không kết nối tài khoản PayPal thật và không có khoản tiền nào bị trừ.</p>
                <div class="demo-paypal-amount"><span>Số tiền mô phỏng</span><strong>$ {{ demoPaypalUsd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} USD</strong><small>Quy đổi 1 USD = 1 VND</small></div>
                <div class="demo-paypal-account"><span class="demo-account-avatar"><i class="bi bi-person-fill"/></span><div><strong>{{ form.email || 'Khách hàng TechStore' }}</strong><small>Người mua mô phỏng</small></div><i class="bi bi-check-circle-fill"/></div>
                <div v-if="demoPaypalError" class="checkout-error"><i class="bi bi-exclamation-circle"/> {{ demoPaypalError }}</div>
                <button type="button" class="demo-confirm-btn" :disabled="demoPaypalProcessing" @click="confirmDemoPaypal"><span v-if="demoPaypalProcessing" class="spinner-border spinner-border-sm"/><i v-else class="bi bi-check2-circle"/> {{ demoPaypalProcessing ? 'Đang xử lý mô phỏng...' : 'Xác nhận thanh toán' }}</button>
                <button type="button" class="demo-cancel-btn" :disabled="demoPaypalProcessing" @click="cancelDemoPaypal">Hủy</button>
                <div class="demo-paypal-safe"><i class="bi bi-shield-lock-fill"/> Môi trường mô phỏng · 0₫ tiền thật</div>
            </section>
        </div>
    </div>
</template>

<style>
.checkout-page{min-height:100%;background:#f6f8fc}.checkout-container{max-width:1180px;padding:24px 20px 70px}.checkout-breadcrumb{display:flex;align-items:center;gap:9px;margin-bottom:20px;color:#98a2b3;font-size:.72rem;font-weight:650}.checkout-breadcrumb a{color:#667085}.checkout-breadcrumb a:hover{color:#2563eb}.checkout-breadcrumb i{color:#c3cad5;font-size:.55rem}.checkout-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin-bottom:22px}.checkout-kicker{color:#2563eb;font-size:.61rem;font-weight:900;letter-spacing:.13em}.checkout-heading h1{margin:5px 0 4px;color:#101828;font-size:2rem;font-weight:900;letter-spacing:-.04em}.checkout-heading p{margin:0;color:#667085;font-size:.78rem}.checkout-count{display:inline-flex;align-items:center;gap:6px;padding:8px 11px;border:1px solid #e1e7ef;border-radius:9px;color:#667085;background:#fff;font-size:.68rem;font-weight:800}.checkout-card,.checkout-order-card,.checkout-safe,.checkout-success,.checkout-empty{border:1px solid #e2e7ef;border-radius:18px;background:#fff;box-shadow:0 8px 28px rgba(16,24,40,.045)}.checkout-card{padding:20px;margin-bottom:16px}.checkout-card-title{display:flex;align-items:center;gap:11px;margin-bottom:18px}.checkout-card-title>span{display:grid;flex:0 0 31px;width:31px;height:31px;place-items:center;border-radius:9px;color:#2563eb;background:#eff6ff;font-size:.72rem;font-weight:900}.checkout-card-title strong,.checkout-card-title small{display:block}.checkout-card-title strong{color:#101828;font-size:.86rem;font-weight:900}.checkout-card-title small{margin-top:2px;color:#98a2b3;font-size:.62rem}.checkout-card label:not(.payment-option){display:block;margin-bottom:5px;color:#344054;font-size:.66rem;font-weight:800}.checkout-card label b{color:#dc2626}.checkout-card input:not([type=radio]),.checkout-card textarea{display:block;width:100%;padding:10px 11px;border:1px solid #dfe4ec;border-radius:9px;outline:0;color:#101828;background:#fff;font-size:.72rem;transition:.18s ease}.checkout-card input:not([type=radio]){height:40px}.checkout-card textarea{resize:vertical}.checkout-card input:not([type=radio]):focus,.checkout-card textarea:focus{border-color:#9bbaf8;box-shadow:0 0 0 3px #eff6ff}.checkout-error{display:flex;align-items:flex-start;gap:7px;margin-top:12px;padding:10px 11px;border:1px solid #fecaca;border-radius:9px;color:#b42318;background:#fff7f7;font-size:.64rem;line-height:1.45}.checkout-error i{flex:0 0 auto;margin-top:1px}.order-card-title{margin-bottom:15px;color:#101828;font-size:.92rem;font-weight:900}.checkout-order-card{padding:20px}.order-item{display:grid;grid-template-columns:50px minmax(0,1fr) auto;align-items:center;gap:9px;margin:11px 0}.order-image{width:50px;height:50px;overflow:hidden;border:1px solid #e7ebf0;border-radius:9px;background:#f6f8fb}.order-image img{width:100%;height:100%;object-fit:cover}.order-info{min-width:0}.order-info strong{display:block;overflow:hidden;color:#344054;font-size:.66rem;font-weight:800;line-height:1.35;text-overflow:ellipsis;white-space:nowrap}.order-info small{display:block;margin-top:3px;color:#98a2b3;font-size:.57rem}.order-item>b{color:#101828;font-size:.66rem;font-weight:850;text-align:right}.order-divider{height:1px;margin:15px 0;background:#edf0f4}.order-row{display:flex;justify-content:space-between;gap:10px;margin:10px 0;color:#667085;font-size:.69rem}.order-row strong{color:#344054}.shipping-total{padding:9px 0;border-top:1px dashed #e1e7ef;border-bottom:1px dashed #e1e7ef}.shipping-total span,.shipping-total strong{font-weight:850}.payment-block{margin-top:16px;padding:14px;border:1px solid #e5e9f0;border-radius:14px;background:#fbfcfe}.payment-block-heading{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:11px}.payment-block-heading span,.payment-block-heading small{display:block}.payment-block-heading span{color:#101828;font-size:.78rem;font-weight:900}.payment-block-heading small{margin-top:2px;color:#98a2b3;font-size:.58rem}.payment-block-heading>i{display:grid;width:30px;height:30px;place-items:center;border-radius:9px;color:#2563eb;background:#eff6ff;font-size:.82rem}.payment-option{display:grid;grid-template-columns:42px minmax(0,1fr) 20px;align-items:center;gap:11px;width:100%;padding:12px;border:1px solid #e1e7ef;border-radius:12px;background:#fff;cursor:pointer;transition:.18s ease}.payment-option+.payment-option{margin-top:9px}.payment-option:hover{border-color:#b9cdf3;box-shadow:0 5px 15px rgba(16,24,40,.04)}.payment-option.active{border-color:#8fb5ff;background:#f7faff;box-shadow:0 0 0 2px #eaf2ff}.payment-option input{width:18px;height:18px;margin:0;accent-color:#2563eb;cursor:pointer}.payment-option-icon{display:grid;width:42px;height:42px;place-items:center;border-radius:11px;font-size:1.05rem}.cod-icon{color:#16815a;background:#ecfdf3}.paypal-icon{color:#003087;background:#eef5ff}.payment-option-copy{min-width:0}.payment-option-copy strong,.payment-option-copy small{display:block}.payment-option-copy strong{color:#172033;font-size:.71rem;font-weight:900;line-height:1.35}.payment-option-copy small{margin-top:3px;color:#98a2b3;font-size:.59rem;line-height:1.45}.demo-paypal-panel{margin-top:10px;padding:13px;border:1px solid #cfe0ff;border-radius:12px;background:linear-gradient(180deg,#f8fbff,#fff)}.demo-paypal-badge{display:inline-flex;align-items:center;gap:5px;margin-bottom:10px;padding:5px 8px;border-radius:999px;color:#2563eb;background:#eaf2ff;font-size:.54rem;font-weight:900;letter-spacing:.05em}.demo-paypal-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:6px 0;color:#667085;font-size:.62rem}.demo-paypal-row strong{color:#344054;font-size:.64rem}.demo-paypal-usd{margin-top:3px;padding:9px 10px;border-radius:9px;color:#003087;background:#eef5ff}.demo-paypal-usd strong{color:#003087;font-size:.78rem}.demo-paypal-panel p{display:flex;gap:6px;margin:10px 0 11px;color:#667085;font-size:.57rem;line-height:1.5}.demo-paypal-panel p i{color:#2563eb}.demo-paypal-btn{display:flex;align-items:center;justify-content:center;gap:7px;width:100%;height:40px;border:0;border-radius:9px;color:#fff;background:#003087;font-size:.65rem;font-weight:850;cursor:pointer}.demo-paypal-btn:hover{background:#001f5c}.order-total{display:flex;align-items:center;justify-content:space-between;gap:10px}.order-total span{color:#344054;font-size:.75rem;font-weight:850}.order-total strong{color:#dc2626;font-size:1.2rem;font-weight:950}.place-order-btn{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;height:45px;margin-top:17px;border:0;border-radius:10px;color:#fff;background:#2563eb;box-shadow:0 8px 18px rgba(37,99,235,.18);font-size:.74rem;font-weight:850;cursor:pointer}.place-order-btn:hover{background:#1d4ed8}.paypal-summary-hint{display:flex;align-items:center;justify-content:center;gap:7px;margin-top:15px;padding:10px;border-radius:9px;color:#003087;background:#eef5ff;font-size:.6rem;font-weight:750;text-align:center}.back-cart{display:flex;align-items:center;justify-content:center;gap:6px;margin-top:11px;color:#667085;font-size:.65rem;font-weight:750}.back-cart:hover{color:#2563eb}.checkout-safe{display:flex;gap:9px;margin-top:13px;padding:13px 15px}.checkout-safe>i{color:#2563eb;font-size:1rem}.checkout-safe strong,.checkout-safe small{display:block}.checkout-safe strong{color:#344054;font-size:.65rem}.checkout-safe small{margin-top:2px;color:#98a2b3;font-size:.57rem;line-height:1.4}.checkout-success,.checkout-empty{display:flex;align-items:center;flex-direction:column;justify-content:center;min-height:460px;padding:45px 20px;text-align:center}.success-icon,.checkout-empty>div{display:grid;width:76px;height:76px;place-items:center;margin-bottom:17px;border-radius:20px;color:#15803d;background:#f0fdf4;font-size:2rem}.checkout-success h1{margin:7px 0 6px;color:#101828;font-size:1.55rem;font-weight:900}.checkout-success p,.checkout-empty p{margin:0 0 17px;color:#667085;font-size:.75rem}.checkout-success p strong{color:#2563eb}.success-summary{display:flex;align-items:center;gap:12px;margin-bottom:19px;padding:10px 15px;border:1px solid #edf0f4;border-radius:10px;background:#fbfcfe;color:#667085;font-size:.67rem}.success-summary strong{color:#dc2626;font-size:.9rem}.success-btn{display:inline-flex;align-items:center;gap:8px;padding:11px 17px;border-radius:10px;color:#fff;background:#2563eb;font-size:.72rem;font-weight:850;box-shadow:0 8px 18px rgba(37,99,235,.17)}.success-btn:hover{color:#fff;background:#1d4ed8}.checkout-empty h2{margin:7px 0 5px;color:#101828;font-size:1.35rem;font-weight:900}.checkout-empty>div{color:#2563eb;background:#eff6ff}.checkout-empty p{margin-bottom:18px}.demo-paypal-overlay{position:fixed;z-index:2000;inset:0;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(15,23,42,.48);backdrop-filter:blur(5px)}.demo-paypal-modal{position:relative;width:min(430px,100%);padding:28px;border:1px solid #dce6f8;border-radius:22px;background:#fff;box-shadow:0 25px 70px rgba(15,23,42,.24)}.demo-paypal-close{position:absolute;top:14px;right:14px;display:grid;width:32px;height:32px;place-items:center;border:1px solid #e5e9f0;border-radius:9px;color:#667085;background:#fff;cursor:pointer}.demo-paypal-logo{display:grid;width:52px;height:52px;place-items:center;margin-bottom:13px;border-radius:15px;color:#003087;background:#eef5ff;font-size:1.5rem}.demo-paypal-kicker{color:#2563eb;font-size:.56rem;font-weight:900;letter-spacing:.12em}.demo-paypal-modal h2{margin:6px 0 5px;color:#101828;font-size:1.35rem;font-weight:900}.demo-paypal-description{margin:0 0 17px;color:#667085;font-size:.66rem;line-height:1.55}.demo-paypal-amount{padding:15px;border:1px solid #dce6f8;border-radius:13px;background:#f8fbff;text-align:center}.demo-paypal-amount span,.demo-paypal-amount small{display:block;color:#98a2b3;font-size:.59rem}.demo-paypal-amount strong{display:block;margin:4px 0;color:#003087;font-size:1.35rem;font-weight:950}.demo-paypal-account{display:flex;align-items:center;gap:10px;margin-top:12px;padding:11px;border:1px solid #edf0f4;border-radius:11px}.demo-account-avatar{display:grid;width:35px;height:35px;place-items:center;border-radius:50%;color:#2563eb;background:#eff6ff}.demo-paypal-account div{min-width:0;flex:1}.demo-paypal-account strong,.demo-paypal-account small{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.demo-paypal-account strong{color:#344054;font-size:.64rem}.demo-paypal-account small{margin-top:2px;color:#98a2b3;font-size:.55rem}.demo-paypal-account>i{color:#16a34a}.demo-confirm-btn,.demo-cancel-btn{width:100%;height:43px;margin-top:12px;border-radius:9px;font-size:.67rem;font-weight:850;cursor:pointer}.demo-confirm-btn{display:flex;align-items:center;justify-content:center;gap:7px;border:0;color:#fff;background:#003087}.demo-confirm-btn:hover:not(:disabled){background:#001f5c}.demo-confirm-btn:disabled,.demo-cancel-btn:disabled{cursor:not-allowed;opacity:.6}.demo-cancel-btn{border:1px solid #e1e7ef;color:#667085;background:#fff}.demo-paypal-safe{display:flex;align-items:center;justify-content:center;gap:6px;margin-top:12px;color:#98a2b3;font-size:.55rem}.demo-paypal-safe i{color:#16a34a}@media(max-width:575.98px){.checkout-container{padding:18px 12px 45px}.checkout-heading{align-items:flex-start;flex-direction:column}.checkout-heading h1{font-size:1.7rem}.checkout-card,.checkout-order-card{padding:17px}.checkout-count{width:100%;justify-content:center}.payment-block{padding:11px}.payment-option{grid-template-columns:40px minmax(0,1fr) 19px;padding:11px;gap:9px}.payment-option-icon{width:40px;height:40px}.payment-option-copy strong{font-size:.68rem}.payment-option-copy small{font-size:.56rem}.demo-paypal-modal{padding:24px 18px}.demo-paypal-amount strong{font-size:1.18rem}}
</style>
