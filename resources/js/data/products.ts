export type Product = {
    id: number;
    slug: string;
    name: string;
    category: string;
    categorySlug: 'laptop' | 'laptop-components' | 'laptop-accessories';
    brand: string;
    price: number;
    oldPrice?: number;
    image: string;
    gallery: string[];
    badge?: string;
    rating: number;
    sold: number;
    stock: number;
    shortDescription: string;
    description: string;
    specs: Record<string, string>;
    source?: string;
};

const imageFor = (name: string) =>
    `https://placehold.co/900x700/f4f7fb/172033?text=${encodeURIComponent(name)}`;

const laptopSeeds = [
    ['macbook-neo-13-a18-pro-8-256', 'MacBook Neo 13 inch A18 Pro 8GB/256GB', 'Apple', 18790000, 19490000, 'Mới'],
    ['macbook-neo-13-a18-pro-8-512', 'MacBook Neo 13 inch A18 Pro 8GB/512GB', 'Apple', 21290000, 21990000, 'Mới'],
    ['macbook-air-13-m5-16-512', 'MacBook Air 13 inch M5 16GB/512GB', 'Apple', 35590000, 36590000, 'Bán chạy'],
    ['macbook-air-13-m5-24-512', 'MacBook Air 13 inch M5 24GB/512GB', 'Apple', 40990000, 41990000, 'Mới'],
    ['macbook-air-15-m5-16-512', 'MacBook Air 15 inch M5 16GB/512GB', 'Apple', 41490000, 41990000, 'Bán chạy'],
    ['macbook-air-15-m5-24-512', 'MacBook Air 15 inch M5 24GB/512GB', 'Apple', 46290000, 47490000, 'Mới'],
    ['macbook-air-15-m5-16-1tb', 'MacBook Air 15 inch M5 16GB/1TB', 'Apple', 49690000, 50190000, ''],
    ['macbook-pro-14-m5-pro-24-1tb', 'MacBook Pro 14 inch M5 Pro 24GB/1TB', 'Apple', 66990000, 68990000, 'Pro'],
    ['macbook-pro-14-m5-pro-24-2tb', 'MacBook Pro 14 inch M5 Pro 24GB/2TB', 'Apple', 81790000, 82690000, 'Cao cấp'],
    ['macbook-pro-16-m5-pro-24-1tb', 'MacBook Pro 16 inch M5 Pro 24GB/1TB', 'Apple', 80990000, 81990000, 'Pro'],
    ['macbook-pro-16-m5-max-48-2tb', 'MacBook Pro 16 inch M5 Max 48GB/2TB', 'Apple', 133990000, 134990000, 'Cao cấp'],
    ['hp-15-fc0023au-r5', 'HP 15 fc0023AU R5 7520U 16GB/512GB', 'HP', 19890000, 23990000, 'Giảm giá'],
    ['dell-15-dc15255-r5', 'Dell 15 DC15255 R5 7530U 16GB/512GB', 'Dell', 23990000, 25690000, 'Bảo hành 2 năm'],
    ['dell-15-dc15250-i7', 'Dell 15 DC15250 i7 1355U 16GB/512GB', 'Dell', 24490000, 25990000, ''],
    ['asus-vivobook-s14-s3407va', 'ASUS Vivobook S14 S3407VA Core 5 210H 16GB/512GB', 'ASUS', 20190000, 22990000, 'Bán chạy'],
    ['lenovo-ideapad-slim-3-15arp10', 'Lenovo IdeaPad Slim 3 15ARP10 R5 7535HS 16GB/512GB', 'Lenovo', 19990000, 26490000, 'Giảm giá'],
    ['msi-modern-15-f1mg', 'MSI Modern 15 F1MG Core 5 120U 16GB/512GB', 'MSI', 18990000, 22390000, 'Mới'],
    ['acer-nitro-v15-anv15', 'Acer Nitro V 15 i5 13420H 16GB/512GB RTX 4050', 'Acer', 26390000, 27490000, 'Gaming'],
    ['asus-tuf-gaming-a15', 'ASUS TUF Gaming A15 R7 170 16GB/512GB RTX 3050', 'ASUS', 25990000, 29990000, 'Gaming'],
    ['msi-katana-15-b13vfk', 'MSI Gaming Katana 15 i7 13620H 16GB/1TB RTX 4060', 'MSI', 28990000, 30390000, 'Gaming'],
    ['lenovo-yoga-slim-7', 'Lenovo Yoga Slim 7 Core Ultra 7 16GB/512GB', 'Lenovo', 29990000, 30990000, 'Mỏng nhẹ'],
    ['hp-245-g10-r5', 'HP 245 G10 R5 7530U 8GB/512GB', 'HP', 18490000, 21390000, ''],
    ['acer-aspire-lite-15', 'Acer Aspire Lite 15 R7 5700U 16GB/512GB', 'Acer', 14990000, 15990000, ''],
    ['asus-vivobook-14-oled', 'ASUS Vivobook 14 OLED i5 12500H 16GB/512GB', 'ASUS', 15690000, 17690000, 'OLED'],
] as const;

const componentSeeds = [
    ['ram-laptop-kingston-8gb-ddr4', 'RAM Laptop Kingston 8GB DDR4 3200MHz', 'Kingston', 549000, 699000],
    ['ram-laptop-kingston-16gb-ddr4', 'RAM Laptop Kingston 16GB DDR4 3200MHz', 'Kingston', 1090000, 1290000],
    ['ram-laptop-crucial-16gb-ddr5', 'RAM Laptop Crucial 16GB DDR5 5600MHz', 'Crucial', 1390000, 1590000],
    ['ram-laptop-samsung-32gb-ddr5', 'RAM Laptop Samsung 32GB DDR5 5600MHz', 'Samsung', 2490000, 2790000],
    ['ssd-wd-sn770-500gb', 'SSD WD Black SN770 500GB NVMe PCIe 4.0', 'WD', 1090000, 1290000],
    ['ssd-samsung-990-evo-1tb', 'SSD Samsung 990 EVO 1TB NVMe', 'Samsung', 1890000, 2190000],
    ['ssd-crucial-p3-plus-1tb', 'SSD Crucial P3 Plus 1TB NVMe', 'Crucial', 1590000, 1890000],
    ['ssd-kingston-nv3-1tb', 'SSD Kingston NV3 1TB NVMe', 'Kingston', 1390000, 1690000],
    ['laptop-battery-dell-compatible', 'Pin Laptop Dell tương thích chính hãng', 'Dell', 1490000, 1690000],
    ['laptop-battery-hp-compatible', 'Pin Laptop HP tương thích chính hãng', 'HP', 1390000, 1590000],
    ['laptop-fan-asus-compatible', 'Quạt tản nhiệt Laptop ASUS tương thích', 'ASUS', 690000, 790000],
    ['laptop-fan-lenovo-compatible', 'Quạt tản nhiệt Laptop Lenovo tương thích', 'Lenovo', 690000, 790000],
    ['laptop-keyboard-dell-compatible', 'Bàn phím thay thế Laptop Dell', 'Dell', 890000, 1090000],
    ['laptop-keyboard-hp-compatible', 'Bàn phím thay thế Laptop HP', 'HP', 790000, 990000],
    ['laptop-ssd-upgrade-2tb', 'SSD Laptop 2TB NVMe PCIe 4.0', 'WD', 3290000, 3690000],
] as const;

const accessorySeeds = [
    ['logitech-mx-anywhere-3s', 'Chuột Logitech MX Anywhere 3S', 'Logitech', 1690000, 1990000],
    ['logitech-mx-master-3s', 'Chuột Logitech MX Master 3S', 'Logitech', 2390000, 2690000],
    ['apple-magic-mouse-usbc', 'Apple Magic Mouse USB-C', 'Apple', 1890000, 2290000],
    ['logitech-mx-keys-s', 'Bàn phím Logitech MX Keys S', 'Logitech', 2390000, 2690000],
    ['apple-magic-keyboard-touch-id', 'Apple Magic Keyboard Touch ID', 'Apple', 3990000, 4290000],
    ['anker-735-65w', 'Sạc Anker 735 GaN 65W', 'Anker', 1090000, 1490000],
    ['anker-prime-100w', 'Sạc Anker Prime GaN 100W', 'Anker', 1990000, 2490000],
    ['apple-usbc-70w', 'Củ sạc Apple USB-C 70W', 'Apple', 1475000, 1590000],
    ['ugreen-hub-6in1', 'Hub UGREEN USB-C 6 trong 1', 'UGREEN', 890000, 1090000],
    ['ugreen-hub-9in1', 'Hub UGREEN USB-C 9 trong 1', 'UGREEN', 1390000, 1690000],
    ['havit-laptop-stand-st7304', 'Giá đỡ Laptop/MacBook Havit ST7304', 'Havit', 171000, 190000],
    ['rapoo-laptop-stand-cf300', 'Giá đỡ Laptop/MacBook Rapoo CF300', 'Rapoo', 486000, 540000],
    ['tomtoc-sleeve-13', 'Túi chống sốc Laptop 13 inch Tomtoc', 'Tomtoc', 690000, 790000],
    ['tomtoc-sleeve-14', 'Túi chống sốc Laptop 14 inch Tomtoc', 'Tomtoc', 790000, 890000],
    ['tomtoc-sleeve-16', 'Túi chống sốc Laptop 16 inch Tomtoc', 'Tomtoc', 890000, 990000],
    ['tomtoc-backpack-16', 'Balo Laptop 16 inch Tomtoc', 'Tomtoc', 1490000, 1790000],
    ['baseus-mousepad', 'Tấm lót chuột Baseus', 'Baseus', 90000, 100000],
    ['logitech-brio-100', 'Webcam Logitech Brio 100 FHD', 'Logitech', 699000, 999000],
] as const;

let nextId = 1;

const buildProduct = (
    seed: readonly [string, string, string, number, number?, string?],
    category: Product['category'],
    categorySlug: Product['categorySlug'],
): Product => {
    const [slug, name, brand, price, oldPrice, badge] = seed;
    const isLaptop = categorySlug === 'laptop';
    const isComponent = categorySlug === 'laptop-components';
    const shortDescription = isLaptop
        ? 'Laptop chính hãng cho học tập, công việc, sáng tạo nội dung và giải trí.'
        : isComponent
            ? 'Linh kiện nâng cấp và thay thế dành riêng cho laptop, ưu tiên tính tương thích.'
            : 'Phụ kiện laptop và MacBook giúp bảo vệ, kết nối và nâng cao trải nghiệm sử dụng.';

    const specs = isLaptop
        ? { CPU: brand === 'Apple' ? 'Apple Silicon' : 'Intel Core / AMD Ryzen', RAM: '16GB', 'Ổ cứng': '512GB NVMe SSD', 'Màn hình': '14-15.6 inch', 'Hệ điều hành': brand === 'Apple' ? 'macOS' : 'Windows 11' }
        : isComponent
            ? { 'Loại': 'Linh kiện laptop', 'Tương thích': 'Laptop tương thích', 'Bảo hành': '12 tháng' }
            : { 'Loại': 'Phụ kiện laptop', 'Tương thích': 'Laptop / MacBook', 'Bảo hành': '12 tháng' };

    return {
        id: nextId++, slug, name, category, categorySlug, brand, price,
        ...(oldPrice ? { oldPrice } : {}),
        image: imageFor(name), gallery: [imageFor(name)],
        ...(badge ? { badge } : {}), rating: 4.7 + (nextId % 4) / 10,
        sold: 20 + ((nextId * 37) % 180), stock: 5 + ((nextId * 11) % 35),
        shortDescription, description: `${name}. Sản phẩm được xây dựng cho hệ thống TechStore và có thông tin kỹ thuật rõ ràng để khách hàng dễ lựa chọn.`, specs,
    };
};

export const products: Product[] = [
    ...laptopSeeds.map((item) => buildProduct(item, 'Laptop', 'laptop')),
    ...componentSeeds.map((item) => buildProduct(item, 'Linh kiện laptop', 'laptop-components')),
    ...accessorySeeds.map((item) => buildProduct(item, 'Phụ kiện laptop', 'laptop-accessories')),
];

export const categories = [
    { name: 'Laptop', slug: 'laptop', icon: 'bi-laptop', count: products.filter((p) => p.categorySlug === 'laptop').length, tone: 'primary' },
    { name: 'Linh kiện laptop', slug: 'laptop-components', icon: 'bi-cpu', count: products.filter((p) => p.categorySlug === 'laptop-components').length, tone: 'purple' },
    { name: 'Phụ kiện laptop', slug: 'laptop-accessories', icon: 'bi-keyboard', count: products.filter((p) => p.categorySlug === 'laptop-accessories').length, tone: 'orange' },
];

export const featuredProducts = products.filter((product) => product.categorySlug === 'laptop').slice(0, 6);

export function getProduct(slug: string) {
    return products.find((product) => product.slug === slug);
}

export function formatPrice(price: number) {
    return new Intl.NumberFormat('vi-VN').format(price) + 'đ';
}
