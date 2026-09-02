export type OrderStatus = 'Chờ xử lý' | 'Đang giao' | 'Đã giao' | 'Trả hàng' | 'Hủy hàng';

export type TechStoreOrder = {
    code: string;
    createdAt: string;
    customer?: {
        name?: string;
        phone?: string;
        email?: string;
        address?: string;
        note?: string;
        payment?: string;
    };
    items: Array<{
        id: number;
        name: string;
        price: number;
        image: string;
        quantity: number;
    }>;
    subtotal: number;
    shipping: number;
    totalShipping: number;
    total: number;
    payment: string;
    paypalOrderId?: string | null;
    status: OrderStatus;
    cancelledAt?: string | null;
    returnedAt?: string | null;
};

export function getOrderHistoryKey(userId: number | string): string {
    return `techstore_orders_user_${String(userId)}`;
}

function normalizeStatus(value: unknown): OrderStatus {
    if (value === 'Đang giao' || value === 'Đã giao' || value === 'Trả hàng' || value === 'Hủy hàng') return value;
    return 'Chờ xử lý';
}

function normalizeOrder(value: any): TechStoreOrder | null {
    if (!value || !value.code || !Array.isArray(value.items)) return null;

    return {
        ...value,
        code: String(value.code),
        createdAt: String(value.createdAt ?? new Date().toISOString()),
        items: value.items.map((item: any) => ({
            id: Number(item.id),
            name: String(item.name ?? 'Sản phẩm'),
            price: Number(item.price ?? 0),
            image: String(item.image ?? ''),
            quantity: Math.max(1, Math.floor(Number(item.quantity ?? 1))),
        })),
        subtotal: Number(value.subtotal ?? 0),
        shipping: Number(value.shipping ?? 0),
        totalShipping: Number(value.totalShipping ?? value.shipping ?? 0),
        total: Number(value.total ?? 0),
        payment: String(value.payment ?? 'cod'),
        paypalOrderId: value.paypalOrderId ?? null,
        status: normalizeStatus(value.status),
        cancelledAt: value.cancelledAt ?? null,
        returnedAt: value.returnedAt ?? null,
    };
}

export function readOrderHistory(userId: number | string): TechStoreOrder[] {
    try {
        const raw = localStorage.getItem(getOrderHistoryKey(userId));
        if (!raw) return [];
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed.map(normalizeOrder).filter(Boolean) as TechStoreOrder[] : [];
    } catch {
        return [];
    }
}

export function writeOrderHistory(userId: number | string, orders: TechStoreOrder[]): void {
    localStorage.setItem(getOrderHistoryKey(userId), JSON.stringify(orders));
}

export function syncLastOrderToHistory(userId: number | string): TechStoreOrder[] {
    const history = readOrderHistory(userId);
    const raw = localStorage.getItem(`techstore_last_order_user_${String(userId)}`);
    if (!raw) return history;

    try {
        const latest = normalizeOrder(JSON.parse(raw));
        if (!latest || history.some((order) => order.code === latest.code)) return history;
        const next = [latest, ...history];
        writeOrderHistory(userId, next);
        return next;
    } catch {
        return history;
    }
}

export function updateOrderStatus(userId: number | string, orderCode: string, status: OrderStatus): TechStoreOrder[] {
    const next = readOrderHistory(userId).map((order) => {
        if (order.code !== orderCode) return order;
        return {
            ...order,
            status,
            cancelledAt: status === 'Hủy hàng' ? new Date().toISOString() : order.cancelledAt ?? null,
            returnedAt: status === 'Trả hàng' ? new Date().toISOString() : order.returnedAt ?? null,
        };
    });
    writeOrderHistory(userId, next);
    return next;
}

export function syncAllLastOrdersToHistory(): void {
    if (typeof window === 'undefined') return;

    for (let index = 0; index < localStorage.length; index += 1) {
        const key = localStorage.key(index);
        if (!key?.startsWith('techstore_last_order_user_')) continue;
        const userId = key.replace('techstore_last_order_user_', '');
        if (userId) syncLastOrderToHistory(userId);
    }
}
