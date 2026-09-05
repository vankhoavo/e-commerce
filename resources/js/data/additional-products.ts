export type AdditionalProduct = {
    id: number;
    slug: string;
    name: string;
    category: 'Laptop' | 'Linh kiện laptop' | 'Phụ kiện laptop';
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

const laptopImages = [
    'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=900&q=80',
    'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=900&q=80',
    'https://images.unsplash.com/photo-1525547719571-a2d4ac8945e2?auto=format&fit=crop&w=900&q=80',
    'https://images.unsplash.com/photo-1531297484001-80022131f5a1?auto=format&fit=crop&w=900&q=80',
];

const accessoryImages = [
    'https://images.unsplash.com/photo-1527814050087-3793815479db?auto=format&fit=crop&w=900&q=80',
    'https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=900&q=80',
    'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=900&q=80',
    'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=900&q=80',
];

const componentImages = [
    'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=900&q=80',
    'https://images.unsplash.com/photo-1591799264318-7e6ef8ddb7ea?auto=format&fit=crop&w=900&q=80',
    'https://images.unsplash.com/photo-1555617981-dac3880eac6e?auto=format&fit=crop&w=900&q=80',
    ...laptopImages.slice(0, 1),
];

const slugify = (value: string) =>
    value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');

const imagesFor = (kind: 'laptop' | 'component' | 'accessory', index: number) => {
    const source = kind === 'laptop' ? laptopImages : kind === 'component' ? componentImages : accessoryImages;
    const a = source[index % source.length];
    const b = source[(index + 1) % source.length];
    const c = source[(index + 2) % source.length];
    return [a, b, c];
};

const laptopBrands = [
    ['ASUS', ['Vivobook 14', 'Vivobook 15', 'Vivobook S 14', 'Vivobook S 15', 'TUF Gaming A15', 'TUF Gaming F15', 'ROG Strix G16', 'Zenbook 14']],
    ['Acer', ['Aspire Lite 14', 'Aspire Lite 15', 'Swift Go 14', 'Swift Go 16', 'Nitro V 15', 'Nitro V 16', 'Aspire 5', 'TravelMate P2']],
    ['Lenovo', ['IdeaPad Slim 3 14', 'IdeaPad Slim 3 15', 'IdeaPad Slim 5 14', 'IdeaPad Slim 5 16', 'ThinkBook 14', 'ThinkBook 16', 'LOQ 15', 'Legion 5']],
    ['HP', ['15', '14', 'ProBook 440', 'ProBook 450', 'Pavilion 14', 'Pavilion 15', 'Victus 15', 'Victus 16']],
    ['Dell', ['Inspiron 14', 'Inspiron 15', 'Vostro 14', 'Vostro 15', 'Latitude 3440', 'Latitude 3540', 'G15', 'XPS 13']],
    ['MSI', ['Modern 14', 'Modern 15', 'Prestige 14', 'Prestige 16', 'Thin 15', 'Katana 15', 'Cyborg 15', 'Sword 16']],
    ['LG', ['gram 14', 'gram 15', 'gram 16', 'gram Style 14']],
    ['Huawei', ['MateBook D 14', 'MateBook D 15', 'MateBook 14', 'MateBook 16']],
];

const laptopCpu = [
    ['Core 5 120U', 16990000], ['Core 5 210H', 18990000], ['Core i5-1335U', 17990000],
    ['Core i5-13420H', 21990000], ['Core i7-13620H', 26990000], ['Core Ultra 5 225H', 24990000],
    ['Core Ultra 7 258V', 32990000], ['Ryzen 5 7530U', 16990000], ['Ryzen 5 8645HS', 22990000],
    ['Ryzen 7 8845HS', 27990000], ['Ryzen 7 7735HS', 23990000], ['Ryzen AI 7 350', 31990000],
];

const laptopMemory = ['8GB/512GB', '16GB/512GB', '16GB/1TB', '24GB/1TB', '32GB/1TB'];
const laptopSeeds: Array<[string, string, string, number, number, string?]> = Array.from({ length: 300 }, (_, index) => {
    const [brand, models] = laptopBrands[index % laptopBrands.length];
    const model = models[Math.floor(index / laptopBrands.length) % models.length];
    const [cpu, basePrice] = laptopCpu[index % laptopCpu.length];
    const memory = laptopMemory[index % laptopMemory.length];
    const screen = [14, 15.6, 16][index % 3];
    const gaming = /Gaming|Nitro|TUF|ROG|LOQ|Legion|Katana|Cyborg|Sword|G15|Victus/.test(model);
    const price = basePrice + (index % 7) * 350000 + (gaming ? 2500000 : 0);
    const name = `${brand} ${model} ${screen}\" ${cpu} ${memory}${gaming ? ' RTX Gaming' : ''}`;
    return [
        `laptop-${slugify(name)}-${index + 58}`,
        name,
        brand,
        price,
        price + 1200000,
        gaming ? 'Gaming' : index % 4 === 0 ? 'Bán chạy' : undefined,
    ];
});

const componentTypes = [
    ['RAM DDR4 8GB 3200MHz', ['Kingston', 'Crucial', 'Lexar', 'Samsung', 'ADATA'], 549000],
    ['RAM DDR4 16GB 3200MHz', ['Kingston', 'Crucial', 'Lexar', 'Samsung', 'ADATA'], 1090000],
    ['RAM DDR5 8GB 4800MHz', ['Kingston', 'Crucial', 'Lexar', 'Samsung', 'ADATA'], 890000],
    ['RAM DDR5 16GB 5600MHz', ['Kingston', 'Crucial', 'Lexar', 'Samsung', 'ADATA'], 1390000],
    ['RAM DDR5 32GB 5600MHz', ['Kingston', 'Crucial', 'Lexar', 'Samsung', 'ADATA'], 2490000],
    ['SSD NVMe 500GB PCIe 4.0', ['WD', 'Samsung', 'Kingston', 'Crucial', 'Lexar'], 1090000],
    ['SSD NVMe 1TB PCIe 4.0', ['WD', 'Samsung', 'Kingston', 'Crucial', 'Lexar'], 1590000],
    ['SSD NVMe 2TB PCIe 4.0', ['WD', 'Samsung', 'Kingston', 'Crucial', 'Lexar'], 2990000],
    ['SSD NVMe 1TB PCIe 3.0', ['WD', 'Kingston', 'Crucial', 'Lexar', 'TeamGroup'], 1290000],
    ['SSD NVMe 2TB PCIe 3.0', ['WD', 'Kingston', 'Crucial', 'Lexar', 'TeamGroup'], 2590000],
    ['Pin Laptop', ['Dell', 'HP', 'Lenovo', 'ASUS', 'Acer'], 990000],
    ['Quạt tản nhiệt Laptop', ['Dell', 'HP', 'Lenovo', 'ASUS', 'Acer'], 590000],
    ['Bàn phím Laptop thay thế', ['Dell', 'HP', 'Lenovo', 'ASUS', 'Acer'], 790000],
    ['Touchpad Laptop thay thế', ['Dell', 'HP', 'Lenovo', 'ASUS', 'Acer'], 690000],
    ['Cáp màn hình Laptop', ['Dell', 'HP', 'Lenovo', 'ASUS', 'Acer'], 490000],
    ['Cáp sạc DC Laptop', ['Dell', 'HP', 'Lenovo', 'ASUS', 'Acer'], 390000],
    ['Bản lề Laptop', ['Dell', 'HP', 'Lenovo', 'ASUS', 'Acer'], 450000],
    ['Wi-Fi Card Laptop', ['Intel', 'MediaTek', 'Realtek', 'Qualcomm', 'Broadcom'], 490000],
    ['Keo tản nhiệt Laptop', ['Arctic', 'Noctua', 'Cooler Master', 'Thermal Grizzly', 'DeepCool'], 190000],
    ['Đế tản nhiệt thay thế', ['Cooler Master', 'DeepCool', 'Havit', 'Thermaltake', 'Rapoo'], 390000],
] as const;

const componentSeeds: Array<[string, string, string, number, number, string?]> = Array.from({ length: 100 }, (_, index) => {
    const [type, brands, basePrice] = componentTypes[index % componentTypes.length];
    const brand = brands[Math.floor(index / componentTypes.length) % brands.length];
    const price = basePrice + (index % 5) * 70000;
    const name = `${type} ${brand} - phiên bản ${String(index + 1).padStart(3, '0')}`;
    return [`linh-kien-${slugify(type)}-${slugify(brand)}-${index + 1}`, name, brand, price, price + 150000, index % 5 === 0 ? 'Nâng cấp' : undefined];
});

const accessoryTypes = [
    ['Chuột không dây Laptop', ['Logitech', 'Rapoo', 'Microsoft', 'HP', 'Dell', 'Lenovo', 'ASUS', 'Baseus'], 390000],
    ['Chuột Bluetooth Laptop', ['Logitech', 'Rapoo', 'Microsoft', 'HP', 'Dell', 'Lenovo', 'ASUS', 'Baseus'], 490000],
    ['Chuột công thái học Laptop', ['Logitech', 'Rapoo', 'Microsoft', 'HP', 'Dell', 'Lenovo', 'ASUS', 'Baseus'], 790000],
    ['Bàn phím Bluetooth Laptop', ['Logitech', 'Rapoo', 'Microsoft', 'Keychron', 'Dell', 'Lenovo', 'ASUS', 'Baseus'], 790000],
    ['Bàn phím cơ nhỏ gọn Laptop', ['Logitech', 'Rapoo', 'Keychron', 'Akko', 'Leobog', 'DareU', 'ASUS', 'AULA'], 990000],
    ['Hub USB-C Laptop', ['UGREEN', 'Anker', 'Baseus', 'Belkin', 'Satechi', 'ORICO', 'TP-Link', 'Hama'], 690000],
    ['Hub USB-C HDMI Laptop', ['UGREEN', 'Anker', 'Baseus', 'Belkin', 'Satechi', 'ORICO', 'TP-Link', 'Hama'], 890000],
    ['Củ sạc GaN Laptop', ['Anker', 'UGREEN', 'Baseus', 'Belkin', 'Apple', 'Aukey', 'Satechi', 'Momax'], 890000],
    ['Cáp USB-C Laptop', ['Anker', 'UGREEN', 'Baseus', 'Belkin', 'Apple', 'Aukey', 'Satechi', 'Essager'], 190000],
    ['Giá đỡ Laptop', ['Havit', 'UGREEN', 'Baseus', 'Orico', 'Nillkin', 'Rapoo', 'Satechi', 'Twelve South'], 390000],
    ['Túi chống sốc Laptop', ['Tomtoc', 'WiWU', 'Targus', 'Incase', 'Ugreen', 'Baseus', 'Native Union', 'Tucano'], 490000],
    ['Balo Laptop', ['Tomtoc', 'Targus', 'Incase', 'Tucano', 'Mark Ryden', 'Arctic Hunter', 'WiWU', 'Bange'], 890000],
    ['Kính bảo vệ màn hình Laptop', ['Nillkin', 'JCPAL', 'Belkin', 'ESR', 'Moshi', 'Baseus', 'Spigen', 'UNIQ'], 290000],
    ['Miếng dán chống xước Laptop', ['JCPAL', 'Nillkin', 'Moshi', 'ESR', 'Baseus', 'Spigen', 'UNIQ', 'WiWU'], 190000],
    ['Webcam Full HD cho Laptop', ['Logitech', 'Rapoo', 'A4Tech', 'HP', 'Lenovo', 'Dell', 'Razer', 'Anker'], 590000],
    ['Tai nghe Bluetooth cho Laptop', ['Sony', 'JBL', 'Anker', 'Logitech', 'Baseus', 'Soundcore', 'Edifier', 'Sennheiser'], 790000],
    ['Loa Bluetooth cho Laptop', ['JBL', 'Sony', 'Anker', 'Soundcore', 'Edifier', 'Marshall', 'Harman Kardon', 'Baseus'], 890000],
    ['Tấm lót chuột Laptop', ['Logitech', 'Razer', 'SteelSeries', 'Baseus', 'UGREEN', 'HyperX', 'A4Tech', 'Cooler Master'], 150000],
    ['Bộ vệ sinh Laptop', ['3M', 'Baseus', 'UGREEN', 'Nillkin', 'Hama', 'JCPAL', 'Momax', 'WiWU'], 120000],
    ['Khóa chống trộm Laptop', ['Kensington', 'Targus', 'UGREEN', 'Baseus', 'Lenovo', 'Dell', 'HP', 'Acer'], 390000],
] as const;

const accessorySeeds: Array<[string, string, string, number, number, string?]> = Array.from({ length: 100 }, (_, index) => {
    const [type, brands, basePrice] = accessoryTypes[index % accessoryTypes.length];
    const brand = brands[Math.floor(index / accessoryTypes.length) % brands.length];
    const price = basePrice + (index % 6) * 60000;
    const name = `${type} ${brand} - phiên bản ${String(index + 1).padStart(3, '0')}`;
    return [`phu-kien-${slugify(type)}-${slugify(brand)}-${index + 1}`, name, brand, price, price + 120000, index % 6 === 0 ? 'Mới' : undefined];
});

const makeProduct = (
    seed: readonly [string, string, string, number, number, string?],
    index: number,
    category: AdditionalProduct['category'],
    categorySlug: AdditionalProduct['categorySlug'],
    kind: 'laptop' | 'component' | 'accessory',
): AdditionalProduct => {
    const [slug, name, brand, price, oldPrice, badge] = seed;
    const gallery = imagesFor(kind, index);
    const isLaptop = categorySlug === 'laptop';
    const specs = isLaptop
        ? {
            CPU: /Apple/.test(brand) ? 'Apple Silicon' : 'Intel Core / AMD Ryzen',
            RAM: ['8GB', '16GB', '24GB', '32GB'][index % 4],
            'Ổ cứng': index % 3 === 0 ? '1TB NVMe SSD' : '512GB NVMe SSD',
            'Màn hình': `${[14, 15.6, 16][index % 3]} inch`,
            'Hệ điều hành': 'Windows 11',
        }
        : categorySlug === 'laptop-components'
            ? { 'Loại': 'Linh kiện laptop', 'Tương thích': 'Laptop tương thích theo model', 'Bảo hành': '12 tháng' }
            : { 'Loại': 'Phụ kiện laptop', 'Tương thích': 'Laptop / MacBook', 'Bảo hành': '12 tháng' };

    return {
        id: index + 58,
        slug,
        name,
        category,
        categorySlug,
        brand,
        price,
        oldPrice,
        image: gallery[0],
        gallery,
        ...(badge ? { badge } : {}),
        rating: Number((4.5 + (index % 5) / 10).toFixed(1)),
        sold: 15 + ((index * 29) % 240),
        stock: 5 + ((index * 13) % 40),
        shortDescription: isLaptop
            ? 'Laptop chính hãng cho học tập, công việc, lập trình, sáng tạo nội dung và giải trí.'
            : categorySlug === 'laptop-components'
                ? 'Linh kiện nâng cấp hoặc thay thế dành cho laptop, cần đối chiếu model trước khi mua.'
                : 'Phụ kiện laptop giúp bảo vệ, kết nối và nâng cao trải nghiệm sử dụng hằng ngày.',
        description: `${name}. Đây là dữ liệu mở rộng của danh mục TechStore, có hình ảnh, giá tham khảo và thông tin kỹ thuật để phục vụ giao diện bán hàng thử nghiệm.`,
        specs,
        source: 'Danh mục mở rộng TechStore',
    };
};

export const additionalProducts: AdditionalProduct[] = [
    ...laptopSeeds.map((seed, index) => makeProduct(seed, index, 'Laptop', 'laptop', 'laptop')),
    ...componentSeeds.map((seed, index) => makeProduct(seed, index + 300, 'Linh kiện laptop', 'laptop-components', 'component')),
    ...accessorySeeds.map((seed, index) => makeProduct(seed, index + 400, 'Phụ kiện laptop', 'laptop-accessories', 'accessory')),
];

if (additionalProducts.length !== 500) {
    throw new Error(`Danh mục mở rộng phải có đúng 500 sản phẩm, hiện có ${additionalProducts.length}.`);
}
