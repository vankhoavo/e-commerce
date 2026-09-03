<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { formatPrice } from '@/data/products';
import { getCartStorageKey, migrateLegacyCart, readCart, type CartItem } from '@/lib/cart';

type PaypalSdkInstance = {
    findEligibleMethods: (options?: { currencyCode?: string }) => Promise<{ isEligible: (method: string) => boolean }>;
    createPayPalOneTimePaymentSession: (options: Record<string, unknown>) => unknown;
};

declare global {
    interface Window {
        paypal?: { createInstance: (options: Record<string, unknown>) => Promise<PaypalSdkInstance> };
    }
}

type Province = { code: number; name: string; wards?: Ward[] };
type Ward = { code: number; name: string; province_code?: number };
type VatInvoiceForm = {
    requested: boolean;
    companyName: string;
    taxCode: string;
    address: string;
    email: string;
};

const page = usePage();
const authUser = computed(() => (page.props as any).auth?.user ?? null);
const userId = computed<number | null>(() => authUser.value?.id ? Number(authUser.value.id) : null);
const cart = ref<CartItem[]>([]);
const submitted = ref(false);
const orderCode = ref('');
const shippingError = ref('');
const vatError = ref('');
const orderError = ref('');
const paypalError = ref('');
const paypalLoading = ref(false);
const paypalReady = ref(false);
const paypalEligible = ref(false);
const paypalContainer = ref<HTMLElement | null>(null);
const paypalButton = ref<HTMLElement | null>(null);
const paypalSession = ref<{ start: (options: Record<string, unknown>, order: Promise<{ orderId: string }>) => Promise<unknown> } | null>(null);
const paypalClientId = String(import.meta.env.VITE_PAYPAL_CLIENT_ID ?? '').trim();

const vatProvinces = ref<Province[]>([]);
const vatWards = ref<Ward[]>([]);
const vatSelectedProvince = ref('');
const vatSelectedWard = ref('');
const vatAddressDetail = ref('');
const vatAddressLoading = ref(false);
const vatAddressError = ref('');

const form = ref({
    name: '',
    phone: '',
    email: '',
    address: '',
    note: '',
    payment: 'cod',
    vatInvoice: {
        requested: false,
        companyName: '',
        taxCode: '',
        address: '',
        email: '',
    } as VatInvoiceForm,
});

const COD_SHIPPING_FEE = 30000;
const VND_TO_USD = 25000;

function loadCart(): void {
    if (!userId.value) {
        cart.value = [];
        return;
    }

    migrateLegacyCart(userId.value);
    cart.value = readCart(userId.value);
    form.value.name = authUser.value?.name ?? '';
    form.value.phone = authUser.value?.phone ?? '';
    form.value.email = authUser.value?.email ?? '';
    form.value.address = authUser.value?.address ?? '';
}

async function loadVatWards(provinceName: string): Promise<void> {
    vatWards.value = [];
    vatSelectedWard.value = '';
    if (!provinceName) {
        syncVatAddress();
        return;
    }
    const province = vatProvinces.value.find((item) => item.name === provinceName);
    if (!province) return;
    if (province.wards?.length) {
        vatWards.value = province.wards;
        syncVatAddress();
        return;
    }
    try {
        const response = await fetch(`https://provinces.open-api.vn/api/v2/p/${province.code}?depth=2`);
        if (!response.ok) throw new Error('address-api');
        const data = await response.json();
        vatWards.value = Array.isArray(data.wards) ? data.wards : [];
    } catch {
        vatAddressError.value = 'Không thể tải danh sách phường/xã. Vui lòng thử lại.';
    }
    syncVatAddress();
}

async function loadVatAddressOptions(): Promise<void> {
    vatAddressLoading.value = true;
    vatAddressError.value = '';
    try {
        const response = await fetch('https://provinces.open-api.vn/api/v2/?depth=2');
        if (!response.ok) throw new Error('address-api');
        const data = await response.json();
        vatProvinces.value = Array.isArray(data) ? data : [];
    } catch {
        vatAddressError.value = 'Không thể tải danh sách tỉnh/thành phố. Vui lòng thử lại.';
    } finally {
        vatAddressLoading.value = false;
    }
}

function syncVatAddress(): void {
    const parts = [vatAddressDetail.value.trim(), vatSelectedWard.value.trim(), vatSelectedProvince.value.trim()].filter(Boolean);
    form.value.vatInvoice.address = parts.join(', ');
}

watch([vatSelectedProvince, vatSelectedWard, vatAddressDetail], syncVatAddress);

const itemCount = computed(() => cart.value.reduce((sum, item) => sum + item.quantity, 0));
const subtotal = computed(() => cart.value.reduce((sum, item) => sum + item.price * item.quantity, 0));
const shippingFee = computed(() => form.value.payment === 'cod' && cart.value.length ? COD_SHIPPING_FEE : 0);
const totalShipping = computed(() => shippingFee.value);
const total = computed(() => subtotal.value + totalShipping.value);
const paypalUsd = computed(() => Math.max(1, Math.round((total.value / VND_TO_USD) * 100) / 100));

function validateShipping(): boolean {
    if (!form.value.name.trim() || !form.value.phone.trim() || !form.value.address.trim()) {
        shippingError.value = 'Vui lòng nhập đầy đủ họ tên, số điện thoại và địa chỉ nhận hàng.';
        return false;
    }

    shippingError.value = '';
    return true;
}

function validateVatInvoice(): boolean {
    if (!form.value.vatInvoice.requested) {
        vatError.value = '';
        return true;
    }

    syncVatAddress();
    const vat = form.value.vatInvoice;
    if (!vat.companyName.trim()) {
        vatError.value = 'Vui lòng nhập tên công ty hoặc đơn vị.';
        return false;
    }
    if (!vat.taxCode.trim()) {
        vatError.value = 'Vui lòng nhập mã số thuế.';
        return false;
    }
    if (!vatSelectedProvince.value || !vatSelectedWard.value || !vatAddressDetail.value.trim()) {
        vatError.value = 'Vui lòng chọn đầy đủ tỉnh/thành phố, phường/xã và nhập số nhà, tên đường.';
        return false;
    }
    if (!vat.email.trim()) {
        vatError.value = 'Vui lòng nhập Email nhận hóa đơn.';
        return false;
    }

    vatError.value = '';
    return true;
}

function clearUserCart(): void {
    if (!userId.value) return;
    localStorage.removeItem(getCartStorageKey(userId.value));
    window.dispatchEvent(new Event('techstore-cart-updated'));
}

async function persistOrder(payment: string, paypalOrderId: string | null = null) {
    const response = await fetch('/orders', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
        },
        body: JSON.stringify({
            customer: {
                name: form.value.name,
                phone: form.value.phone,
                email: form.value.email,
                address: form.value.address,
                note: form.value.note,
            },
            items: cart.value,
            subtotal: subtotal.value,
            shipping: shippingFee.value,
            total_shipping: totalShipping.value,
            total: total.value,
            payment,
            paypal_order_id: paypalOrderId,
            vat_invoice: {
                requested: form.value.vatInvoice.requested,
                company_name: form.value.vatInvoice.companyName,
                tax_code: form.value.vatInvoice.taxCode,
                address: form.value.vatInvoice.address,
                email: form.value.vatInvoice.email,
            },
        }),
    });

    const data = await response.json().catch(() => ({}));
    if (!response.ok || !data.order?.code) throw new Error(data.message ?? 'Không thể lưu đơn hàng.');
    return data.order;
}

async function saveOrder(payment: string, paypalOrderId: string | null = null): Promise<void> {
    orderError.value = '';
    if (!validateShipping() || !validateVatInvoice()) return;

    try {
        const order = await persistOrder(payment, paypalOrderId);
        orderCode.value = order.code;
        clearUserCart();
        submitted.value = true;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (error) {
        orderError.value = error instanceof Error ? error.message : 'Không thể lưu đơn hàng.';
        throw error;
    }
}

async function submitCod(): Promise<void> {
    if (!cart.value.length) return;
    try {
        await saveOrder('cod');
    } catch {
        // Error is displayed in the checkout form.
    }
}

function csrfToken(): string {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

function loadPaypalSdk(): Promise<void> {
    if (window.paypal) return Promise.resolve();
    if (!paypalClientId) return Promise.reject(new Error('TechStore chưa có PayPal Sandbox Client ID.'));

    return new Promise((resolve, reject) => {
        const existing = document.querySelector<HTMLScriptElement>('script[data-techstore-paypal-v6]');
        if (existing) {
            existing.addEventListener('load', () => resolve(), { once: true });
            existing.addEventListener('error', () => reject(new Error('Không tải được PayPal Sandbox.')), { once: true });
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://www.sandbox.paypal.com/web-sdk/v6/core';
        script.async = true;
        script.dataset.techstorePaypalV6 = 'true';
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Không tải được PayPal Sandbox.'));
        document.head.appendChild(script);
    });
}

async function createPaypalOrder(): Promise<{ orderId: string }> {
    if (!validateShipping() || !validateVatInvoice()) throw new Error(shippingError.value || vatError.value);

    const response = await fetch('/paypal/orders', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({ amount_vnd: total.value }),
    });

    const data = await response.json().catch(() => ({}));
    if (!response.ok || !data.id) throw new Error(data.message ?? 'Không thể tạo đơn PayPal Sandbox.');
    return { orderId: String(data.id) };
}

async function capturePaypalOrder({ orderId }: { orderId: string }): Promise<void> {
    const response = await fetch(`/paypal/orders/${encodeURIComponent(orderId)}/capture`, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
    });
    const data = await response.json().catch(() => ({}));

    if (!response.ok || data.status !== 'COMPLETED') {
        throw new Error(data.message ?? 'PayPal Sandbox chưa xác nhận thanh toán.');
    }

    await saveOrder('paypal-sandbox', orderId);
    paypalLoading.value = false;
}

async function setupPaypal(): Promise<void> {
    if (paypalReady.value || !paypalClientId || form.value.payment !== 'paypal') return;

    paypalError.value = '';
    try {
        await loadPaypalSdk();
        if (!window.paypal) throw new Error('PayPal Sandbox SDK chưa sẵn sàng.');

        const sdk = await window.paypal.createInstance({
            clientId: paypalClientId,
            components: ['paypal-payments'],
            pageType: 'checkout',
        });
        const eligibility = await sdk.findEligibleMethods({ currencyCode: 'USD' });
        paypalEligible.value = eligibility.isEligible('paypal');
        paypalReady.value = true;

        if (!paypalEligible.value) {
            paypalError.value = 'PayPal Sandbox không xác định được phương thức PayPal phù hợp với môi trường hiện tại.';
            return;
        }

        await nextTick();
        if (!paypalContainer.value || paypalButton.value) return;

        paypalSession.value = sdk.createPayPalOneTimePaymentSession({
            onApprove: capturePaypalOrder,
            onCancel: () => {
                paypalLoading.value = false;
                paypalError.value = 'Bạn đã hủy thanh toán PayPal Sandbox.';
            },
            onError: (error: unknown) => {
                paypalLoading.value = false;
                console.error(error);
                paypalError.value = 'PayPal Sandbox gặp lỗi. Vui lòng thử lại.';
            },
        }) as typeof paypalSession.value;

        const button = document.createElement('paypal-button');
        button.setAttribute('type', 'pay');
        button.addEventListener('click', async () => {
            if (!validateShipping() || !validateVatInvoice() || !paypalSession.value) return;

            paypalLoading.value = true;
            paypalError.value = '';
            try {
                await paypalSession.value.start({ presentationMode: 'auto' }, createPaypalOrder());
            } catch (error) {
                paypalLoading.value = false;
                paypalError.value = error instanceof Error ? error.message : 'Không thể mở PayPal Sandbox.';
            }
        });

        paypalButton.value = button;
        paypalContainer.value.appendChild(button);
    } catch (error) {
        paypalError.value = error instanceof Error ? error.message : 'Không thể khởi tạo PayPal Sandbox.';
    }
}

function selectPayment(payment: string): void {
    form.value.payment = payment;
    if (payment === 'paypal') void nextTick(setupPaypal);
}

watch(() => form.value.payment, (payment) => {
    if (payment === 'paypal') void nextTick(setupPaypal);
});

onMounted(async () => {
    loadCart();
    await loadVatAddressOptions();
});
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
                <div v-if="form.payment === 'paypal-sandbox'" class="sandbox-success"><i class="bi bi-shield-check"/><span>Thanh toán đã được xác nhận trên <strong>PayPal Sandbox</strong>. Đây là giao dịch thử nghiệm, không chuyển tiền thật.</span></div>
                <Link href="/products" class="success-btn">Tiếp tục mua sắm <i class="bi bi-arrow-right"/></Link>
            </section>

            <template v-else-if="cart.length">
                <div class="checkout-heading"><div><span class="checkout-kicker">TECHSTORE · THANH TOÁN</span><h1>Đặt hàng &amp; vận chuyển</h1><p>Thông tin cá nhân đã lưu sẽ được tự động điền vào biểu mẫu.</p></div><span class="checkout-count"><i class="bi bi-bag"/> {{ itemCount }} sản phẩm</span></div>

                <form class="row g-4" @submit.prevent="submitCod">
                    <section class="col-lg-7">
                        <div class="checkout-card">
                            <div class="checkout-card-title"><span>1</span><div><strong>Thông tin nhận hàng</strong><small>Tên, số điện thoại và địa chỉ dùng cho giao hàng.</small></div></div>
                            <div class="saved-profile-note"><i class="bi bi-person-check-fill"/><span>Đã tự động điền từ <strong>Thông tin cá nhân</strong>. Bạn vẫn có thể chỉnh sửa cho riêng đơn hàng này.</span></div>
                            <div class="row g-3">
                                <div class="col-md-6"><label>Họ và tên <b>*</b></label><input v-model="form.name" required type="text" autocomplete="name" placeholder="Nguyễn Văn A"/></div>
                                <div class="col-md-6"><label>Số điện thoại <b>*</b></label><input v-model="form.phone" required type="tel" autocomplete="tel" pattern="[0-9+ .-]{8,20}" placeholder="0901 234 567"/></div>
                                <div class="col-12"><label>Email</label><input v-model="form.email" type="email" autocomplete="email" placeholder="email@example.com"/></div>
                                <div class="col-12"><label>Địa chỉ nhận hàng <b>*</b></label><textarea v-model="form.address" required rows="3" autocomplete="street-address" placeholder="Số nhà, đường, phường/xã, tỉnh/thành phố"/></div>
                                <div class="col-12"><label>Ghi chú</label><textarea v-model="form.note" rows="2" placeholder="Ví dụ: Giao giờ hành chính..."/></div>
                            </div>
                            <div v-if="shippingError" class="checkout-error"><i class="bi bi-exclamation-circle"/> {{ shippingError }}</div>
                        </div>

                        <div class="checkout-card vat-card">
                            <div class="checkout-card-title"><span><i class="bi bi-receipt-cutoff"/></span><div><strong>Xuất hóa đơn VAT</strong><small>Bạn có thể chọn xuất hóa đơn VAT cho đơn hàng này.</small></div></div>
                            <label class="vat-toggle-row">
                                <input v-model="form.vatInvoice.requested" type="checkbox" @change="vatError = ''" />
                                <span class="vat-toggle-ui" aria-hidden="true"><i class="bi bi-check"/></span>
                                <span class="vat-toggle-copy"><strong>Tôi muốn xuất hóa đơn VAT</strong><small>Không thêm phí vào đơn hàng. Thông tin xuất hóa đơn sẽ được lưu cùng đơn.</small></span>
                            </label>

                            <div v-if="form.vatInvoice.requested" class="vat-fields">
                                <div class="vat-info-banner"><i class="bi bi-info-circle-fill"/><span>Vui lòng nhập chính xác thông tin doanh nghiệp/đơn vị để lập hóa đơn. Địa chỉ sử dụng đơn vị hành chính Việt Nam sau sáp nhập từ 01/07/2025.</span></div>
                                <div class="row g-3">
                                    <div class="col-12"><label>Tên công ty / đơn vị <b>*</b></label><input v-model="form.vatInvoice.companyName" type="text" maxlength="255" placeholder="Công ty TNHH TechStore"/></div>
                                    <div class="col-md-6"><label>Mã số thuế <b>*</b></label><input v-model="form.vatInvoice.taxCode" type="text" maxlength="32" placeholder="0101234567"/></div>
                                    <div class="col-md-6"><label>Email nhận hóa đơn <b>*</b></label><input v-model="form.vatInvoice.email" type="email" maxlength="255" placeholder="ketoan@congty.vn"/></div>
                                    <div class="col-md-6"><label>Tỉnh / Thành phố <b>*</b></label><select v-model="vatSelectedProvince" :disabled="vatAddressLoading" class="vat-address-select"><option value="">{{ vatAddressLoading ? 'Đang tải...' : 'Chọn tỉnh / thành phố' }}</option><option v-for="province in vatProvinces" :key="province.code" :value="province.name">{{ province.name }}</option></select></div>
                                    <div class="col-md-6"><label>Phường / Xã <b>*</b></label><select v-model="vatSelectedWard" :disabled="!vatSelectedProvince || vatAddressLoading" class="vat-address-select"><option value="">{{ vatSelectedProvince ? 'Chọn phường / xã' : 'Chọn tỉnh trước' }}</option><option v-for="ward in vatWards" :key="ward.code" :value="ward.name">{{ ward.name }}</option></select></div>
                                    <div class="col-12"><label>Số nhà, tên đường <b>*</b></label><input v-model="vatAddressDetail" type="text" maxlength="250" placeholder="Ví dụ: 25 Nguyễn Văn Linh" autocomplete="street-address"/></div>
                                </div>
                                <div v-if="form.vatInvoice.address" class="vat-address-preview"><i class="bi bi-geo-alt-fill"/><span>{{ form.vatInvoice.address }}</span></div>
                                <div v-if="vatAddressError" class="checkout-error"><i class="bi bi-exclamation-circle"/> {{ vatAddressError }}</div>
                                <div v-if="vatError" class="checkout-error"><i class="bi bi-exclamation-circle"/> {{ vatError }}</div>
                            </div>
                        </div>

                        <div v-if="orderError" class="checkout-error checkout-error-global"><i class="bi bi-exclamation-circle"/> {{ orderError }}</div>
                    </section>

                    <aside class="col-lg-5">
                        <div class="checkout-order-card">
                            <div class="order-card-title">Tóm tắt đơn hàng</div>
                            <div v-for="item in cart" :key="item.id" class="order-item"><div class="order-image"><img :src="item.image" :alt="item.name"/></div><div class="order-info"><strong>{{ item.name }}</strong><small>Số lượng: {{ item.quantity }}</small></div><b>{{ formatPrice(item.price * item.quantity) }}</b></div>
                            <div class="order-divider"/><div class="order-row"><span>Tạm tính</span><strong>{{ formatPrice(subtotal) }}</strong></div><div class="order-row"><span>Phí vận chuyển</span><strong>{{ formatPrice(shippingFee) }}</strong></div><div class="order-row shipping-total"><span>Tổng phí vận chuyển</span><strong>{{ formatPrice(totalShipping) }}</strong></div>

                            <div class="payment-block">
                                <div class="payment-block-heading"><div><span>Phương thức thanh toán</span><small>Chọn một phương thức</small></div><i class="bi bi-shield-check"/></div>
                                <button type="button" class="payment-option" :class="{ active: form.payment === 'cod' }" @click="selectPayment('cod')"><span class="payment-option-icon cod-icon"><i class="bi bi-cash-stack"/></span><span class="payment-option-copy"><strong>Thanh toán khi nhận hàng</strong><small>COD · Phí vận chuyển 30.000 ₫.</small></span><span class="payment-radio" :class="{ checked: form.payment === 'cod' }"/></button>
                                <button type="button" class="payment-option" :class="{ active: form.payment === 'paypal' }" @click="selectPayment('paypal')"><span class="payment-option-icon paypal-icon"><i class="bi bi-paypal"/></span><span class="payment-option-copy"><strong>PayPal Sandbox</strong><small>Thanh toán trước · Miễn phí vận chuyển.</small></span><span class="payment-radio" :class="{ checked: form.payment === 'paypal' }"/></button>

                                <div v-if="form.payment === 'paypal'" class="paypal-sandbox-panel">
                                    <div class="sandbox-badge"><i class="bi bi-shield-check"/> PAYPAL SANDBOX</div>
                                    <div class="sandbox-amount"><span>Số tiền thử nghiệm</span><strong>$ {{ paypalUsd.toFixed(2) }} USD</strong><small>Quy đổi thử nghiệm: 1 USD ≈ {{ VND_TO_USD.toLocaleString('vi-VN') }} VND</small></div>
                                    <div class="sandbox-info"><i class="bi bi-info-circle"/><span>PayPal Sandbox mô phỏng giao dịch, không chuyển tiền thật.</span></div>
                                    <div ref="paypalContainer" class="paypal-button-container"/>
                                    <div v-if="!paypalClientId" class="paypal-config-warning"><i class="bi bi-gear"/> Chưa cấu hình <code>VITE_PAYPAL_CLIENT_ID</code>.</div>
                                    <div v-if="paypalLoading" class="paypal-processing"><span class="spinner-border spinner-border-sm"/> Đang xác nhận thanh toán Sandbox...</div>
                                    <div v-if="paypalError" class="checkout-error"><i class="bi bi-exclamation-circle"/> {{ paypalError }}</div>
                                </div>
                            </div>

                            <div class="order-divider"/><div class="order-total"><span>Tổng thanh toán</span><strong>{{ formatPrice(total) }}</strong></div>
                            <button v-if="form.payment === 'cod'" type="submit" class="place-order-btn">Tiến hành đặt hàng <i class="bi bi-check2-circle"/></button>
                            <div v-else class="paypal-summary-hint"><i class="bi bi-shield-check"/> Thanh toán trước qua PayPal Sandbox · Miễn phí vận chuyển.</div>
                            <Link href="/cart" class="back-cart"><i class="bi bi-arrow-left"/> Quay lại giỏ hàng</Link>
                        </div>
                    </aside>
                </form>
            </template>

            <section v-else class="checkout-empty"><div><i class="bi bi-bag-x"/></div><h2>Không có sản phẩm để thanh toán</h2><p>Giỏ hàng của tài khoản đang trống.</p><Link href="/products" class="success-btn">Quay lại mua sắm <i class="bi bi-arrow-right"/></Link></section>
        </div>
    </div>
</template>

<style>
.checkout-page{min-height:100%;background:#f6f8fc}.checkout-container{max-width:1180px;padding:24px 20px 70px}.checkout-breadcrumb{display:flex;align-items:center;gap:9px;margin-bottom:20px;color:#98a2b3;font-size:.72rem;font-weight:650}.checkout-breadcrumb a{color:#667085;text-decoration:none}.checkout-breadcrumb a:hover{color:#2563eb}.checkout-breadcrumb i{color:#c3cad5;font-size:.55rem}.checkout-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin-bottom:22px}.checkout-kicker{color:#2563eb;font-size:.61rem;font-weight:900;letter-spacing:.13em}.checkout-heading h1{margin:5px 0 4px;color:#101828;font-size:2rem;font-weight:900;letter-spacing:-.04em}.checkout-heading p{margin:0;color:#667085;font-size:.78rem}.checkout-count{display:inline-flex;align-items:center;gap:6px;padding:8px 11px;border:1px solid #e1e7ef;border-radius:9px;color:#667085;background:#fff;font-size:.68rem;font-weight:800}.checkout-card,.checkout-order-card,.checkout-success,.checkout-empty{border:1px solid #e2e7ef;border-radius:18px;background:#fff;box-shadow:0 8px 28px rgba(16,24,40,.045)}.checkout-card{padding:20px;margin-bottom:16px}.checkout-card-title{display:flex;align-items:center;gap:11px;margin-bottom:14px}.checkout-card-title>span{display:grid;flex:0 0 31px;width:31px;height:31px;place-items:center;border-radius:9px;color:#2563eb;background:#eff6ff;font-size:.72rem;font-weight:900}.checkout-card-title strong,.checkout-card-title small{display:block}.checkout-card-title strong{color:#101828;font-size:.86rem;font-weight:900}.checkout-card-title small{margin-top:2px;color:#98a2b3;font-size:.62rem}.saved-profile-note{display:flex;gap:8px;margin-bottom:17px;padding:9px 11px;border:1px solid #dbeafe;border-radius:10px;color:#475467;background:#f8fbff;font-size:.62rem;line-height:1.45}.saved-profile-note i{color:#2563eb}.saved-profile-note strong{color:#1d4ed8}.checkout-card label{display:block;margin-bottom:5px;color:#344054;font-size:.66rem;font-weight:800}.checkout-card label b{color:#dc2626}.checkout-card input:not([type="checkbox"]),.checkout-card textarea,.vat-address-select{display:block;width:100%;padding:10px 11px;border:1px solid #dfe4ec;border-radius:9px;outline:0;color:#101828;background:#fff;font-size:.72rem;transition:.18s ease}.checkout-card input:not([type="checkbox"]),.vat-address-select{height:40px}.checkout-card textarea{resize:vertical}.checkout-card input:not([type="checkbox"]):focus,.checkout-card textarea:focus,.vat-address-select:focus{border-color:#9bbaf8;box-shadow:0 0 0 3px #eff6ff}.vat-address-select{appearance:auto}.checkout-error{display:flex;align-items:flex-start;gap:7px;margin-top:10px;padding:9px 10px;border:1px solid #fecaca;border-radius:9px;color:#b42318;background:#fff7f7;font-size:.62rem;line-height:1.45}.checkout-error-global{margin-top:0;margin-bottom:16px}.vat-card{padding-bottom:18px}.vat-toggle-row{display:grid!important;grid-template-columns:20px 36px minmax(0,1fr);align-items:center;gap:9px;margin:0!important;padding:12px;border:1px solid #e4e7ec;border-radius:12px;background:#fbfcfe;cursor:pointer}.vat-toggle-row>input{position:absolute;opacity:0;pointer-events:none}.vat-toggle-ui{position:relative;width:36px;height:20px;border-radius:99px;background:#d0d5dd;transition:.2s}.vat-toggle-ui i{position:absolute;top:2px;left:2px;display:grid;width:16px;height:16px;place-items:center;border-radius:50%;color:#fff;background:#fff;font-size:0;box-shadow:0 1px 3px rgba(16,24,40,.2);transition:.2s}.vat-toggle-row>input:checked~.vat-toggle-ui{background:#2563eb}.vat-toggle-row>input:checked~.vat-toggle-ui i{left:18px;color:#2563eb;font-size:10px}.vat-toggle-copy strong,.vat-toggle-copy small{display:block}.vat-toggle-copy strong{color:#101828;font-size:.7rem;font-weight:900}.vat-toggle-copy small{margin-top:2px;color:#98a2b3;font-size:.58rem;line-height:1.45}.vat-fields{margin-top:12px;padding-top:12px;border-top:1px solid #edf0f4}.vat-info-banner{display:flex;gap:7px;margin-bottom:12px;padding:9px 10px;border-radius:9px;color:#1d4ed8;background:#eff6ff;font-size:.59rem;line-height:1.45}.vat-info-banner i{margin-top:1px}.vat-address-preview{display:flex;align-items:flex-start;gap:7px;margin-top:10px;padding:9px 10px;border:1px solid #dbeafe;border-radius:10px;color:#1d4ed8;background:#eff6ff;font-size:.62rem;line-height:1.5}.vat-address-preview i{margin-top:1px}.checkout-order-card{padding:20px}.order-card-title{margin-bottom:15px;color:#101828;font-size:.92rem;font-weight:900}.order-item{display:grid;grid-template-columns:50px minmax(0,1fr) auto;align-items:center;gap:9px;margin:11px 0}.order-image{width:50px;height:50px;overflow:hidden;border:1px solid #e7ebf0;border-radius:9px;background:#f6f8fb}.order-image img{width:100%;height:100%;object-fit:cover}.order-info{min-width:0}.order-info strong{display:block;overflow:hidden;color:#344054;font-size:.66rem;font-weight:800;line-height:1.35;text-overflow:ellipsis;white-space:nowrap}.order-info small{display:block;margin-top:3px;color:#98a2b3;font-size:.57rem}.order-item>b{color:#101828;font-size:.66rem;font-weight:850;text-align:right}.order-divider{height:1px;margin:15px 0;background:#edf0f4}.order-row{display:flex;justify-content:space-between;gap:10px;margin:10px 0;color:#667085;font-size:.69rem}.order-row strong{color:#344054}.shipping-total{padding:9px 0;border-top:1px dashed #e1e7ef;border-bottom:1px dashed #e1e7ef}.shipping-total span,.shipping-total strong{font-weight:850}.payment-block{margin-top:16px;padding:14px;border:1px solid #e5e9f0;border-radius:14px;background:#fbfcfe}.payment-block-heading{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:11px}.payment-block-heading span,.payment-block-heading small{display:block}.payment-block-heading span{color:#101828;font-size:.78rem;font-weight:900}.payment-block-heading small{margin-top:2px;color:#98a2b3;font-size:.58rem}.payment-block-heading>i{display:grid;width:30px;height:30px;place-items:center;border-radius:9px;color:#2563eb;background:#eff6ff;font-size:.82rem}.payment-option{display:grid;grid-template-columns:42px minmax(0,1fr) 20px;align-items:center;gap:11px;width:100%;padding:12px;border:1px solid #e1e7ef;border-radius:12px;color:inherit;background:#fff;text-align:left;cursor:pointer;transition:.18s ease}.payment-option+.payment-option{margin-top:9px}.payment-option:hover{border-color:#b9cdf3;box-shadow:0 5px 15px rgba(16,24,40,.04)}.payment-option.active{border-color:#8fb5ff;background:#f7faff;box-shadow:0 0 0 2px #eaf2ff}.payment-option-icon{display:grid;width:42px;height:42px;place-items:center;border-radius:11px;font-size:1.05rem}.cod-icon{color:#16815a;background:#ecfdf3}.paypal-icon{color:#003087;background:#eef5ff}.payment-option-copy{min-width:0}.payment-option-copy strong,.payment-option-copy small{display:block}.payment-option-copy strong{color:#172033;font-size:.71rem;font-weight:900;line-height:1.35}.payment-option-copy small{margin-top:3px;color:#98a2b3;font-size:.59rem;line-height:1.45}.payment-radio{display:block;width:17px;height:17px;border:2px solid #cbd5e1;border-radius:50%;background:#fff}.payment-radio.checked{border:5px solid #2563eb}.paypal-sandbox-panel{margin-top:10px;padding:13px;border:1px solid #cfe0ff;border-radius:12px;background:linear-gradient(180deg,#f8fbff,#fff)}.sandbox-badge{display:inline-flex;align-items:center;gap:5px;margin-bottom:10px;padding:5px 8px;border-radius:999px;color:#2563eb;background:#eaf2ff;font-size:.54rem;font-weight:900;letter-spacing:.05em}.sandbox-amount{padding:10px;border:1px solid #dce6f8;border-radius:10px;background:#fff;text-align:center}.sandbox-amount span,.sandbox-amount small{display:block;color:#98a2b3;font-size:.56rem}.sandbox-amount strong{display:block;margin:3px 0;color:#003087;font-size:1.05rem;font-weight:950}.sandbox-info{display:flex;gap:6px;margin:10px 0;padding:9px;border-radius:9px;color:#475467;background:#f8fafc;font-size:.57rem;line-height:1.5}.sandbox-info i{color:#2563eb}.paypal-button-container{min-height:44px;margin-top:8px}.paypal-button-container paypal-button{display:block;width:100%}.paypal-processing{display:flex;align-items:center;justify-content:center;gap:7px;margin-top:8px;color:#003087;font-size:.59rem;font-weight:800}.paypal-config-warning{margin-top:8px;padding:8px;border-radius:8px;color:#9a3412;background:#fff7ed;font-size:.58rem}.paypal-config-warning code{font-size:.56rem}.order-total{display:flex;align-items:center;justify-content:space-between;gap:10px}.order-total span{color:#344054;font-size:.75rem;font-weight:850}.order-total strong{color:#dc2626;font-size:1.2rem;font-weight:950}.place-order-btn{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;height:45px;margin-top:17px;border:0;border-radius:10px;color:#fff;background:#2563eb;box-shadow:0 8px 18px rgba(37,99,235,.18);font-size:.74rem;font-weight:850;cursor:pointer}.place-order-btn:hover{background:#1d4ed8}.paypal-summary-hint{display:flex;align-items:center;justify-content:center;gap:7px;margin-top:15px;padding:10px;border-radius:9px;color:#003087;background:#eef5ff;font-size:.6rem;font-weight:750;text-align:center}.back-cart{display:flex;align-items:center;justify-content:center;gap:6px;margin-top:11px;color:#667085;font-size:.65rem;font-weight:750;text-decoration:none}.back-cart:hover{color:#2563eb}.checkout-success,.checkout-empty{display:flex;align-items:center;flex-direction:column;justify-content:center;min-height:460px;padding:45px 20px;text-align:center}.success-icon,.checkout-empty>div{display:grid;width:76px;height:76px;place-items:center;margin-bottom:17px;border-radius:20px;color:#15803d;background:#f0fdf4;font-size:2rem}.checkout-success h1{margin:7px 0 6px;color:#101828;font-size:1.55rem;font-weight:900}.checkout-success p,.checkout-empty p{margin:0 0 17px;color:#667085;font-size:.75rem}.checkout-success p strong{color:#2563eb}.success-summary{display:flex;align-items:center;gap:12px;margin-bottom:12px;padding:10px 15px;border:1px solid #edf0f4;border-radius:10px;background:#fbfcfe;color:#667085;font-size:.67rem}.success-summary strong{color:#dc2626;font-size:.9rem}.sandbox-success{display:flex;align-items:center;gap:7px;max-width:520px;margin-bottom:18px;padding:10px 13px;border:1px solid #bfdbfe;border-radius:10px;color:#1e40af;background:#eff6ff;font-size:.64rem;line-height:1.45}.sandbox-success i{font-size:1rem}.success-btn{display:inline-flex;align-items:center;gap:8px;padding:11px 17px;border-radius:10px;color:#fff;background:#2563eb;font-size:.72rem;font-weight:850;text-decoration:none;box-shadow:0 8px 18px rgba(37,99,235,.17)}.success-btn:hover{color:#fff;background:#1d4ed8}.checkout-empty h2{margin:7px 0 5px;color:#101828;font-size:1.35rem;font-weight:900}.checkout-empty>div{color:#2563eb;background:#eff6ff}.checkout-empty p{margin-bottom:18px}@media(max-width:575px){.checkout-container{padding:18px 12px 45px}.checkout-heading{align-items:flex-start;flex-direction:column}.checkout-heading h1{font-size:1.7rem}.checkout-card,.checkout-order-card{padding:17px}.checkout-count{width:100%;justify-content:center}.payment-block{padding:11px}.payment-option{grid-template-columns:40px minmax(0,1fr) 19px;padding:11px;gap:9px}.payment-option-icon{width:40px;height:40px}.payment-option-copy strong{font-size:.68rem}.payment-option-copy small{font-size:.56rem}.vat-toggle-row{grid-template-columns:20px 36px minmax(0,1fr)}}
</style>
