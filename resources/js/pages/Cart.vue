<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { products, formatPrice } from '@/data/products';
import { getCartStorageKey, migrateLegacyCart, readCart, writeCart, type CartItem } from '@/lib/cart';

const page = usePage();
const authUser = computed(() => (page.props as any).auth?.user ?? null);
const userId = computed<number | null>(() => authUser.value?.id ? Number(authUser.value.id) : null);
const cart = ref<CartItem[]>([]);
const notice = ref('');

function loadCart() {
    if (!userId.value) {
        cart.value = [];
        router.visit('/login?redirect=%2Fcart');
        return;
    }

    migrateLegacyCart(userId.value);
    cart.value = readCart(userId.value);
}

function syncCart() {
    if (!userId.value) return;
    writeCart(userId.value, cart.value);
}

function getStock(id: number) {
    return products.find((product) => product.id === id)?.stock ?? 99;
}

function increase(item: CartItem) {
    const stock = getStock(item.id);
    if (item.quantity >= stock) {
        showNotice(`Sản phẩm chỉ còn ${stock} sản phẩm.`);
        return;
    }
    item.quantity++;
    syncCart();
}

function decrease(item: CartItem) {
    if (item.quantity <= 1) {
        removeItem(item.id);
        return;
    }
    item.quantity--;
    syncCart();
}

function setQuantity(item: CartItem, value: string | number) {
    item.quantity = Math.max(1, Math.min(getStock(item.id), Math.floor(Number(value) || 1)));
    syncCart();
}

function removeItem(id: number) {
    const item = cart.value.find((entry) => entry.id === id);
    cart.value = cart.value.filter((entry) => entry.id !== id);
    syncCart();
    if (item) showNotice(`Đã xóa “${item.name}” khỏi giỏ hàng.`);
}

function clearCart() {
    if (!cart.value.length) return;
    cart.value = [];
    syncCart();
    showNotice('Đã xóa toàn bộ sản phẩm khỏi giỏ hàng.');
}

function showNotice(message: string) {
    notice.value = message;
    window.setTimeout(() => { notice.value = ''; }, 2200);
}

const itemCount = computed(() => cart.value.reduce((sum, item) => sum + item.quantity, 0));
const subtotal = computed(() => cart.value.reduce((sum, item) => sum + item.price * item.quantity, 0));
const shipping = computed(() => cart.value.length ? 30000 : 0);
const total = computed(() => subtotal.value + shipping.value);

function handleImageError(event: Event) {
    (event.target as HTMLImageElement).style.visibility = 'hidden';
}

onMounted(loadCart);
</script>

<template>
<Head title="Giỏ hàng"/>
<div class="cart-page"><div class="container cart-container">
<nav class="cart-breadcrumb"><Link href="/">Trang chủ</Link><i class="bi bi-chevron-right"/><span>Giỏ hàng</span></nav>
<div v-if="notice" class="cart-notice" role="status"><i class="bi bi-check-circle-fill"/><span>{{ notice }}</span></div>
<div class="cart-heading"><div><span class="cart-kicker">TECHSTORE · SHOPPING CART</span><h1>Giỏ hàng</h1><p v-if="itemCount">Bạn đang có <strong>{{ itemCount }}</strong> sản phẩm trong giỏ hàng của tài khoản này.</p><p v-else>Kiểm tra sản phẩm trước khi tiến hành đặt hàng.</p></div><button v-if="cart.length" type="button" class="clear-cart-btn" @click="clearCart"><i class="bi bi-trash3"/> Xóa giỏ hàng</button></div>
<div v-if="cart.length" class="row g-4 align-items-start">
<section class="col-lg-8"><div class="cart-card"><div class="cart-card-header"><span>Sản phẩm</span><span>{{ itemCount }} sản phẩm</span></div>
<article v-for="item in cart" :key="item.id" class="cart-item"><div class="cart-product-image"><img :src="item.image" :alt="item.name" @error="handleImageError"/><span class="cart-image-fallback"><i class="bi bi-laptop"/></span></div><div class="cart-product-info"><div class="cart-product-brand">{{ products.find((product)=>product.id===item.id)?.brand ?? 'TECHSTORE' }}</div><Link :href="`/products/${products.find((product)=>product.id===item.id)?.slug ?? ''}`" class="cart-product-name">{{ item.name }}</Link><div class="cart-product-price">{{ formatPrice(item.price) }}</div></div><div class="cart-quantity"><button type="button" @click="decrease(item)">−</button><input :value="item.quantity" type="number" min="1" :max="getStock(item.id)" @change="setQuantity(item,($event.target as HTMLInputElement).value)"/><button type="button" @click="increase(item)">+</button></div><div class="cart-line-total">{{ formatPrice(item.price*item.quantity) }}</div><button type="button" class="remove-cart-btn" @click="removeItem(item.id)" title="Xóa sản phẩm"><i class="bi bi-trash3"/></button></article>
<div class="cart-card-footer"><Link href="/products" class="continue-shopping"><i class="bi bi-arrow-left"/> Tiếp tục mua sắm</Link></div></div></section>
<aside class="col-lg-4"><div class="cart-summary-card"><div class="summary-title">Tóm tắt đơn hàng</div><div class="summary-row"><span>Tạm tính</span><strong>{{ formatPrice(subtotal) }}</strong></div><div class="summary-row"><span>Phí vận chuyển</span><strong>{{ formatPrice(shipping) }}</strong></div><div class="summary-divider"/><div class="summary-total"><span>Tổng cộng</span><strong>{{ formatPrice(total) }}</strong></div><p class="summary-note"><i class="bi bi-truck"/> Phí vận chuyển cố định 30.000 ₫.</p><Link href="/checkout" class="checkout-btn">Tiến hành đặt hàng <i class="bi bi-arrow-right"/></Link></div><div class="cart-service-card"><div><i class="bi bi-truck"/><span><strong>Giao hàng toàn quốc</strong><small>Đóng gói an toàn, theo dõi đơn.</small></span></div><div><i class="bi bi-shield-check"/><span><strong>Thanh toán an toàn</strong><small>Hỗ trợ COD và PayPal.</small></span></div><div><i class="bi bi-headset"/><span><strong>Hỗ trợ mua hàng</strong><small>Tư vấn trước và sau khi đặt hàng.</small></span></div></div></aside>
</div>
<section v-else class="empty-cart-card"><div class="empty-cart-icon"><i class="bi bi-bag-x"/></div><span class="cart-kicker">SHOPPING CART</span><h2>Giỏ hàng đang trống</h2><p>Hãy khám phá laptop, linh kiện và phụ kiện để chọn sản phẩm phù hợp.</p><Link href="/products" class="empty-cart-btn">Khám phá sản phẩm <i class="bi bi-arrow-right"/></Link></section>
</div></div>
</template>

<style>
.cart-page{min-height:100%;background:#f6f8fc}.cart-container{max-width:1240px;padding:24px 20px 70px}.cart-breadcrumb{display:flex;align-items:center;gap:9px;margin-bottom:20px;color:#98a2b3;font-size:.72rem;font-weight:650}.cart-breadcrumb a{color:#667085}.cart-breadcrumb a:hover{color:#2563eb}.cart-breadcrumb i{color:#c3cad5;font-size:.55rem}.cart-notice{position:fixed;z-index:1100;top:78px;right:24px;display:flex;align-items:center;gap:9px;padding:11px 15px;border:1px solid #bbf7d0;border-radius:11px;color:#166534;background:#f0fdf4;box-shadow:0 12px 28px rgba(16,24,40,.12);font-size:.72rem;font-weight:750}.cart-notice i{font-size:1rem}.cart-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin-bottom:22px}.cart-kicker{color:#2563eb;font-size:.61rem;font-weight:900;letter-spacing:.13em}.cart-heading h1{margin:5px 0 4px;color:#101828;font-size:2rem;font-weight:900;letter-spacing:-.04em}.cart-heading p{margin:0;color:#667085;font-size:.78rem}.cart-heading p strong{color:#344054}.clear-cart-btn{display:inline-flex;align-items:center;gap:7px;padding:9px 12px;border:1px solid #e2e7ef;border-radius:9px;color:#667085;background:#fff;font-size:.68rem;font-weight:800}.clear-cart-btn:hover{border-color:#fecaca;color:#dc2626;background:#fffafa}.cart-card,.cart-summary-card,.cart-service-card,.empty-cart-card{border:1px solid #e2e7ef;border-radius:18px;background:#fff;box-shadow:0 8px 28px rgba(16,24,40,.045)}.cart-card{overflow:hidden}.cart-card-header{display:flex;justify-content:space-between;padding:16px 18px;border-bottom:1px solid #edf0f4;color:#344054;font-size:.7rem;font-weight:850}.cart-card-header span:last-child{color:#98a2b3;font-weight:700}.cart-item{display:grid;grid-template-columns:78px minmax(0,1fr) 128px 125px 34px;align-items:center;gap:14px;padding:17px 18px;border-bottom:1px solid #edf0f4}.cart-product-image{position:relative;width:78px;height:78px;overflow:hidden;border:1px solid #e5e9f0;border-radius:12px;background:#f6f8fb}.cart-product-image img{position:relative;z-index:1;width:100%;height:100%;object-fit:cover}.cart-image-fallback{position:absolute;inset:0;display:grid;place-items:center;color:#2563eb;background:#eff6ff;font-size:1.5rem}.cart-product-info{min-width:0}.cart-product-brand{margin-bottom:3px;color:#98a2b3;font-size:.57rem;font-weight:850;letter-spacing:.1em}.cart-product-name{display:block;overflow:hidden;color:#172033;font-size:.8rem;font-weight:800;line-height:1.4;text-overflow:ellipsis;white-space:nowrap}.cart-product-name:hover{color:#2563eb}.cart-product-price{margin-top:6px;color:#dc2626;font-size:.76rem;font-weight:850}.cart-quantity{display:flex;align-items:center;height:36px;overflow:hidden;border:1px solid #dfe4ec;border-radius:9px;background:#fff}.cart-quantity button{width:34px;height:100%;border:0;color:#475467;background:#f8fafc;font-size:1rem;cursor:pointer}.cart-quantity button:hover{color:#2563eb;background:#eff6ff}.cart-quantity input{width:58px;height:100%;padding:0;border:0;outline:0;text-align:center;color:#101828;background:#fff;font-size:.72rem;font-weight:800}.cart-quantity input::-webkit-inner-spin-button,.cart-quantity input::-webkit-outer-spin-button{margin:0;-webkit-appearance:none}.cart-line-total{color:#101828;text-align:right;font-size:.78rem;font-weight:900}.remove-cart-btn{display:grid;width:32px;height:32px;place-items:center;border:1px solid #e6eaf0;border-radius:8px;color:#98a2b3;background:#fff;cursor:pointer}.remove-cart-btn:hover{border-color:#fecaca;color:#dc2626;background:#fffafa}.cart-card-footer{display:flex;justify-content:space-between;padding:15px 18px;border-top:1px solid #edf0f4;background:#fbfcfe}.continue-shopping{display:inline-flex;align-items:center;gap:7px;color:#2563eb;font-size:.7rem;font-weight:850}.continue-shopping:hover{color:#1d4ed8}.cart-summary-card{padding:20px}.summary-title{margin-bottom:17px;color:#101828;font-size:.92rem;font-weight:900}.summary-row{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:12px 0;color:#667085;font-size:.72rem}.summary-row strong{color:#344054;font-weight:800}.summary-divider{height:1px;margin:17px 0;background:#edf0f4}.summary-total{display:flex;align-items:center;justify-content:space-between;gap:12px}.summary-total span{color:#344054;font-size:.76rem;font-weight:850}.summary-total strong{color:#dc2626;font-size:1.22rem;font-weight:950}.summary-note{display:flex;gap:7px;margin:13px 0 15px;color:#98a2b3;font-size:.61rem;line-height:1.5}.summary-note i{color:#2563eb;font-size:.85rem}.checkout-btn{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;height:45px;border:0;border-radius:10px;color:#fff;background:#2563eb;font-size:.75rem;font-weight:850;box-shadow:0 8px 18px rgba(37,99,235,.17);text-decoration:none}.checkout-btn:hover{color:#fff;background:#1d4ed8}.cart-service-card{margin-top:13px;padding:7px 17px}.cart-service-card>div{display:flex;align-items:center;gap:11px;padding:11px 0}.cart-service-card>div+div{border-top:1px solid #edf0f4}.cart-service-card>div>i{display:grid;flex:0 0 34px;width:34px;height:34px;place-items:center;border-radius:9px;color:#2563eb;background:#eff6ff}.cart-service-card strong,.cart-service-card small{display:block}.cart-service-card strong{color:#344054;font-size:.68rem;font-weight:850}.cart-service-card small{margin-top:2px;color:#98a2b3;font-size:.59rem}.empty-cart-card{display:flex;align-items:center;flex-direction:column;justify-content:center;min-height:440px;padding:50px 20px;text-align:center}.empty-cart-icon{display:grid;width:76px;height:76px;place-items:center;margin-bottom:17px;border-radius:20px;color:#2563eb;background:#eff6ff;font-size:2rem}.empty-cart-card h2{margin:7px 0 5px;color:#101828;font-size:1.45rem;font-weight:900;letter-spacing:-.03em}.empty-cart-card p{max-width:480px;margin:0 0 20px;color:#667085;font-size:.75rem;line-height:1.6}.empty-cart-btn{display:inline-flex;align-items:center;gap:8px;padding:11px 17px;border-radius:10px;color:#fff;background:#2563eb;font-size:.72rem;font-weight:850;box-shadow:0 8px 18px rgba(37,99,235,.17)}.empty-cart-btn:hover{color:#fff;background:#1d4ed8}@media(max-width:991.98px){.cart-item{grid-template-columns:70px minmax(0,1fr) 110px 32px}.cart-product-image{width:70px;height:70px}.cart-line-total{grid-column:2;text-align:left;margin-top:-6px}.remove-cart-btn{grid-column:4;grid-row:1}.cart-quantity{grid-column:3;grid-row:1}.cart-product-info{min-width:0}}@media(max-width:575.98px){.cart-container{padding:18px 12px 45px}.cart-heading{align-items:flex-start;flex-direction:column}.cart-heading h1{font-size:1.7rem}.clear-cart-btn{width:100%;justify-content:center}.cart-item{grid-template-columns:58px minmax(0,1fr) 32px;gap:10px;padding:14px}.cart-product-image{width:58px;height:58px}.cart-product-name{white-space:normal}.cart-quantity{grid-column:2;grid-row:2;width:112px}.cart-line-total{grid-column:2;grid-row:3;margin:0;text-align:left}.remove-cart-btn{grid-column:3;grid-row:1}.cart-card-header{padding:14px}.cart-summary-card{padding:17px}.cart-notice{right:12px;left:12px;top:70px}}
</style>
