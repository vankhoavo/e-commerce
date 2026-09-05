<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tăng dữ liệu tồn kho mẫu lên quy mô lớn để trang quản trị có số liệu
     * trình diễn thực tế, nhưng vẫn giữ công thức Giá trị tồn kho từ Database.
     */
    public function up(): void
    {
        $targetValue = 1_180_000_000_000; // 1,18 nghìn tỷ đồng, vẫn dưới 1.214.674.169.000 đ

        DB::transaction(function () use ($targetValue): void {
            $products = DB::table('admin_products')
                ->select(['id', 'stock', 'price'])
                ->where('price', '>', 0)
                ->lockForUpdate()
                ->get();

            if ($products->isEmpty()) {
                return;
            }

            $currentValue = $products->sum(
                static fn ($product): int => (int) $product->stock * (int) $product->price
            );

            if ($currentValue <= 0 || $currentValue >= $targetValue) {
                return;
            }

            // Phóng tỷ lệ tồn kho đồng đều để đưa tổng giá trị đến gần mốc mục tiêu.
            $ratio = $targetValue / $currentValue;

            foreach ($products as $product) {
                $newStock = (int) floor((int) $product->stock * $ratio);

                DB::table('admin_products')
                    ->where('id', $product->id)
                    ->update(['stock' => max(0, $newStock)]);
            }

            // Bù phần còn thiếu bằng sản phẩm có giá cao nhất nhưng không vượt mục tiêu.
            $updated = DB::table('admin_products')
                ->select(['id', 'stock', 'price'])
                ->where('price', '>', 0)
                ->orderByDesc('price')
                ->lockForUpdate()
                ->get();

            $newValue = $updated->sum(
                static fn ($product): int => (int) $product->stock * (int) $product->price
            );
            $remaining = $targetValue - $newValue;

            foreach ($updated as $product) {
                $price = (int) $product->price;
                if ($price <= 0 || $remaining < $price) {
                    continue;
                }

                $extraUnits = intdiv($remaining, $price);
                if ($extraUnits <= 0) {
                    continue;
                }

                DB::table('admin_products')
                    ->where('id', $product->id)
                    ->update([
                        'stock' => (int) $product->stock + $extraUnits,
                    ]);

                $remaining -= $extraUnits * $price;
                if ($remaining < $price) {
                    break;
                }
            }
        });
    }

    public function down(): void
    {
        // Không tự động giảm tồn kho khi rollback để tránh làm mất dữ liệu kinh doanh.
    }
};
