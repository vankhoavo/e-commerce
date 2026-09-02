<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { formatPrice } from '@/data/products';

type CartItem = { id: number; name: string; price: number; image: string; quantity: number };

const cart = ref<CartItem[]>([]);
const submitted = ref(false);
const orderCode = ref('');
const form = ref({ name: '', phone: '', email: '', address: '', note: '', payment: 'cod' });

function loadCart() {
    try {
        const raw = JSON.parse(localStorage.getItem('techstore_cart') ?? '[]');
        cart.value = Array.isArray(raw) ? raw.filter((item) => item && Number(item.quantity) > 0) : [];
    } catch { cart.value = []; }
}

const itemCount = computed(() => cart.value.reduce((sum, item) => sum + item.quantity, 0));
const subtotal = computed(() => cart.value.reduce((sum, item) => sum + item.price * item.quantity, 0));
const shipping = computed(() => subtotal.value ? 0 : 0);
const total = computed(() => subtotal.value + shipping.value);

function submitOrder() {
    if (!cart.value.length) return;
    orderCode.value = `TS${Date.now().toString().slice(-8)}`;
    const order = {
        code: orderCode.value,
        createdAt: new Date().toISOString(),
        customer: { ...form.value },
        items: cart.value,
        subtotal: subtotal.value,
        shipping: shipping.value,
        total: total.value,
        status: 'Chờ xử lý',
    };
    localStorage.setItem('techstore_last_order', JSON.stringify(order));
    localStorage.removeItem('techstore_cart');
    window.dispatchEvent(new Event('techstore-cart-updated'));
    submitted.value = true;
    window.scrollTo({ top: 0, behavior: 'smooth' });
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
                <p>Đơn hàng <strong>{{ orderCode }}</strong> đã được ghi nhận và đang chờ xử lý.</p>
                <div class="success-summary"><span>Tổng thanh toán</span><strong>{{ formatPrice(total) }}</strong></div>
                <Link href="/products" class="success-btn">Tiếp tục mua sắm <i class="bi bi-arrow-right"/></Link>
            </section>

            <template v-else-if="cart.length">
                <div class="checkout-heading"><div><span class="checkout-kicker">TECHSTORE · CHECKOUT</span><h1>Thanh toán</h1><p>Hoàn tất thông tin để đặt hàng.</p></div><span class="checkout-count"><i class="bi bi-bag"/> {{ itemCount }} sản phẩm</span></div>

                <form class="row g-4" @submit.prevent="submitOrder">
                    <section class="col-lg-7">
                        <div class="checkout-card">
                            <div class="checkout-card-title"><span>1</span><div><strong>Thông tin nhận hàng</strong><small>Nhập thông tin để TechStore liên hệ giao hàng.</small></div></div>
                            <div class="row g-3">
                                <div class="col-md-6"><label>Họ và tên <b>*</b></label><input v-model="form.name" required type="text" autocomplete="name" placeholder="Nguyễn Văn A"/></div>
                                <div class="col-md-6"><label>Số điện thoại <b>*</b></label><input v-model="form.phone" required type="tel" autocomplete="tel" pattern="[0-9+ .-]{8,20}" placeholder="0901 234 567"/></div>
                                <div class="col-12"><label>Email</label><input v-model="form.email" type="email" autocomplete="email" placeholder="email@example.com"/></div>
                                <div class="col-12"><label>Địa chỉ nhận hàng <b>*</b></label><textarea v-model="form.address" required rows="3" autocomplete="street-address" placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành phố"/></div>
                                <div class="col-12"><label>Ghi chú</label><textarea v-model="form.note" rows="2" placeholder="Ví dụ: Giao giờ hành chính..."/></div>
                            </div>
                        </div>

                        <div class="checkout-card">
                            <div class="checkout-card-title"><span>2</span><div><strong>Phương thức thanh toán</strong><small>Chọn cách thanh toán phù hợp.</small></div></div>
                            <label class="payment-option" :class="{ active: form.payment === 'cod' }"><input v-model="form.payment" value="cod" type="radio"/><span class="payment-option-icon"><i class="bi bi-cash-stack"/></span><span><strong>Thanh toán khi nhận hàng</strong><small>Thanh toán trực tiếp khi nhận được sản phẩm.</small></span></label>
                            <label class="payment-option" :class="{ active: form.payment === 'bank' }"><input v-model="form.payment" value="bank" type="radio"/><span class="payment-option-icon"><i class="bi bi-bank"/></span><span><strong>Chuyển khoản ngân hàng</strong><small>Thông tin chuyển khoản sẽ được cung cấp sau khi đặt hàng.</small></span></label>
                        </div>
                    </section>

                    <aside class="col-lg-5">
                        <div class="checkout-order-card">
                            <div class="order-card-title">Đơn hàng của bạn</div>
                            <div v-for="item in cart" :key="item.id" class="order-item"><div class="order-image"><img :src="item.image" :alt="item.name"/></div><div class="order-info"><strong>{{ item.name }}</strong><small>Số lượng: {{ item.quantity }}</small></div><b>{{ formatPrice(item.price * item.quantity) }}</b></div>
                            <div class="order-divider"/>
                            <div class="order-row"><span>Tạm tính</span><strong>{{ formatPrice(subtotal) }}</strong></div>
                            <div class="order-row"><span>Phí vận chuyển</span><strong class="free">Miễn phí</strong></div>
                            <div class="order-divider"/>
                            <div class="order-total"><span>Tổng cộng</span><strong>{{ formatPrice(total) }}</strong></div>
                            <button type="submit" class="place-order-btn">Đặt hàng <i class="bi bi-check2-circle"/></button>
                            <Link href="/cart" class="back-cart"><i class="bi bi-arrow-left"/> Quay lại giỏ hàng</Link>
                        </div>
                        <div class="checkout-safe"><i class="bi bi-shield-check"/><span><strong>Thông tin an toàn</strong><small>Thông tin đặt hàng chỉ được sử dụng để xử lý đơn hàng.</small></span></div>
                    </aside>
                </form>
            </template>

            <section v-else class="checkout-empty"><div><i class="bi bi-bag-x"/></div><h2>Không có sản phẩm để thanh toán</h2><p>Giỏ hàng của bạn đang trống.</p><Link href="/products" class="success-btn">Quay lại mua sắm <i class="bi bi-arrow-right"/></Link></section>
        </div>
    </div>
</template>

<style>
.checkout-page{min-height:100%;background:#f6f8fc}.checkout-container{max-width:1180px;padding:24px 20px 70px}.checkout-breadcrumb{display:flex;align-items:center;gap:9px;margin-bottom:20px;color:#98a2b3;font-size:.72rem;font-weight:650}.checkout-breadcrumb a{color:#667085}.checkout-breadcrumb a:hover{color:#2563eb}.checkout-breadcrumb i{color:#c3cad5;font-size:.55rem}.checkout-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin-bottom:22px}.checkout-kicker{color:#2563eb;font-size:.61rem;font-weight:900;letter-spacing:.13em}.checkout-heading h1{margin:5px 0 4px;color:#101828;font-size:2rem;font-weight:900;letter-spacing:-.04em}.checkout-heading p{margin:0;color:#667085;font-size:.78rem}.checkout-count{display:inline-flex;align-items:center;gap:6px;padding:8px 11px;border:1px solid #e1e7ef;border-radius:9px;color:#667085;background:#fff;font-size:.68rem;font-weight:800}.checkout-card,.checkout-order-card,.checkout-safe,.checkout-success,.checkout-empty{border:1px solid #e2e7ef;border-radius:18px;background:#fff;box-shadow:0 8px 28px rgba(16,24,40,.045)}.checkout-card{padding:20px;margin-bottom:16px}.checkout-card-title{display:flex;align-items:center;gap:11px;margin-bottom:18px}.checkout-card-title>span{display:grid;flex:0 0 31px;width:31px;height:31px;place-items:center;border-radius:9px;color:#2563eb;background:#eff6ff;font-size:.72rem;font-weight:900}.checkout-card-title strong,.checkout-card-title small{display:block}.checkout-card-title strong{color:#101828;font-size:.86rem;font-weight:900}.checkout-card-title small{margin-top:2px;color:#98a2b3;font-size:.62rem}.checkout-card label:not(.payment-option){display:block;margin-bottom:5px;color:#344054;font-size:.66rem;font-weight:800}.checkout-card label b{color:#dc2626}.checkout-card input:not([type=radio]),.checkout-card textarea{display:block;width:100%;padding:10px 11px;border:1px solid #dfe4ec;border-radius:9px;outline:0;color:#101828;background:#fff;font-size:.72rem;transition:.18s ease}.checkout-card input:not([type=radio]){height:40px}.checkout-card textarea{resize:vertical}.checkout-card input:not([type=radio]):focus,.checkout-card textarea:focus{border-color:#9bbaf8;box-shadow:0 0 0 3px #eff6ff}.payment-option{display:flex;align-items:center;gap:11px;padding:13px;border:1px solid #e1e7ef;border-radius:11px;cursor:pointer;transition:.18s ease}.payment-option+.payment-option{margin-top:9px}.payment-option.active{border-color:#9bbaf8;background:#f8fbff;box-shadow:0 0 0 2px #eff6ff}.payment-option input{accent-color:#2563eb}.payment-option-icon{display:grid;flex:0 0 37px;width:37px;height:37px;place-items:center;border-radius:9px;color:#2563eb;background:#eff6ff;font-size:1rem}.payment-option strong,.payment-option small{display:block}.payment-option strong{color:#344054;font-size:.7rem;font-weight:850}.payment-option small{margin-top:2px;color:#98a2b3;font-size:.59rem;line-height:1.4}.checkout-order-card{padding:20px}.order-card-title{margin-bottom:15px;color:#101828;font-size:.92rem;font-weight:900}.order-item{display:grid;grid-template-columns:50px minmax(0,1fr) auto;align-items:center;gap:9px;margin:11px 0}.order-image{width:50px;height:50px;overflow:hidden;border:1px solid #e7ebf0;border-radius:9px;background:#f6f8fb}.order-image img{width:100%;height:100%;object-fit:cover}.order-info{min-width:0}.order-info strong{display:block;overflow:hidden;color:#344054;font-size:.66rem;font-weight:800;line-height:1.35;text-overflow:ellipsis;white-space:nowrap}.order-info small{display:block;margin-top:3px;color:#98a2b3;font-size:.57rem}.order-item>b{color:#101828;font-size:.66rem;font-weight:850;text-align:right}.order-divider{height:1px;margin:15px 0;background:#edf0f4}.order-row{display:flex;justify-content:space-between;margin:10px 0;color:#667085;font-size:.69rem}.order-row strong{color:#344054}.order-row .free{color:#15803d}.order-total{display:flex;align-items:center;justify-content:space-between;gap:10px}.order-total span{color:#344054;font-size:.75rem;font-weight:850}.order-total strong{color:#dc2626;font-size:1.2rem;font-weight:950}.place-order-btn{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;height:45px;margin-top:17px;border:0;border-radius:10px;color:#fff;background:#2563eb;box-shadow:0 8px 18px rgba(37,99,235,.18);font-size:.74rem;font-weight:850;cursor:pointer}.place-order-btn:hover{background:#1d4ed8}.back-cart{display:flex;align-items:center;justify-content:center;gap:6px;margin-top:11px;color:#667085;font-size:.65rem;font-weight:750}.back-cart:hover{color:#2563eb}.checkout-safe{display:flex;gap:9px;margin-top:13px;padding:13px 15px}.checkout-safe>i{color:#2563eb;font-size:1rem}.checkout-safe strong,.checkout-safe small{display:block}.checkout-safe strong{color:#344054;font-size:.65rem}.checkout-safe small{margin-top:2px;color:#98a2b3;font-size:.57rem;line-height:1.4}.checkout-success,.checkout-empty{display:flex;align-items:center;flex-direction:column;justify-content:center;min-height:460px;padding:45px 20px;text-align:center}.success-icon,.checkout-empty>div{display:grid;width:76px;height:76px;place-items:center;margin-bottom:17px;border-radius:20px;color:#15803d;background:#f0fdf4;font-size:2rem}.checkout-success h1{margin:7px 0 6px;color:#101828;font-size:1.55rem;font-weight:900}.checkout-success p,.checkout-empty p{margin:0 0 17px;color:#667085;font-size:.75rem}.checkout-success p strong{color:#2563eb}.success-summary{display:flex;align-items:center;gap:12px;margin-bottom:19px;padding:10px 15px;border:1px solid #edf0f4;border-radius:10px;background:#fbfcfe;color:#667085;font-size:.67rem}.success-summary strong{color:#dc2626;font-size:.9rem}.success-btn{display:inline-flex;align-items:center;gap:8px;padding:11px 17px;border-radius:10px;color:#fff;background:#2563eb;font-size:.72rem;font-weight:850;box-shadow:0 8px 18px rgba(37,99,235,.17)}.success-btn:hover{color:#fff;background:#1d4ed8}.checkout-empty h2{margin:7px 0 5px;color:#101828;font-size:1.35rem;font-weight:900}.checkout-empty>div{color:#2563eb;background:#eff6ff}.checkout-empty p{margin-bottom:18px}@media(max-width:575.98px){.checkout-container{padding:18px 12px 45px}.checkout-heading{align-items:flex-start;flex-direction:column}.checkout-heading h1{font-size:1.7rem}.checkout-card,.checkout-order-card{padding:17px}.checkout-count{width:100%;justify-content:center}}
</style>
