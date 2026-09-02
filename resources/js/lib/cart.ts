import { syncAllLastOrdersToHistory } from '@/lib/orders';

export type CartItem = {
    id: number;
    name: string;
    price: number;
    image: string;
    quantity: number;
};

const LEGACY_CART_KEY = 'techstore_cart';

export function getCartStorageKey(userId: number | string | null | undefined): string {
    return userId ? `techstore_cart_user_${String(userId)}` : 'techstore_cart_guest';
}

function normalizeCart(value: unknown): CartItem[] {
    if (!Array.isArray(value)) return [];

    return value
        .filter((item: any) => item && Number(item.id) > 0 && Number(item.quantity) > 0)
        .map((item: any) => ({
            id: Number(item.id),
            name: String(item.name ?? 'Sản phẩm'),
            price: Number(item.price ?? 0),
            image: String(item.image ?? ''),
            quantity: Math.max(1, Math.floor(Number(item.quantity))),
        }));
}

export function readCart(userId: number | string | null | undefined): CartItem[] {
    try {
        const raw = localStorage.getItem(getCartStorageKey(userId));
        return normalizeCart(raw ? JSON.parse(raw) : []);
    } catch {
        return [];
    }
}

export function writeCart(userId: number | string | null | undefined, cart: CartItem[]): void {
    localStorage.setItem(getCartStorageKey(userId), JSON.stringify(normalizeCart(cart)));
    window.dispatchEvent(new Event('techstore-cart-updated'));
}

/**
 * Older product pages used the shared `techstore_cart` key. Move that data
 * into the authenticated customer's private cart and merge by product ID.
 */
export function migrateLegacyCart(userId: number | string | null | undefined): void {
    if (!userId) {
        localStorage.removeItem(LEGACY_CART_KEY);
        return;
    }

    try {
        const legacyRaw = localStorage.getItem(LEGACY_CART_KEY);
        if (!legacyRaw) return;

        const legacy = normalizeCart(JSON.parse(legacyRaw));
        if (!legacy.length) {
            localStorage.removeItem(LEGACY_CART_KEY);
            return;
        }

        const current = readCart(userId);
        for (const item of legacy) {
            const existing = current.find((entry) => entry.id === item.id);
            if (existing) existing.quantity += item.quantity;
            else current.push(item);
        }

        localStorage.setItem(getCartStorageKey(userId), JSON.stringify(current));
        localStorage.removeItem(LEGACY_CART_KEY);
    } catch {
        localStorage.removeItem(LEGACY_CART_KEY);
    }
}

if (typeof window !== 'undefined') {
    window.addEventListener('techstore-cart-updated', syncAllLastOrdersToHistory);
}
