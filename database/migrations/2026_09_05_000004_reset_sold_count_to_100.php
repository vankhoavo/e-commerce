<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Đưa tổng số lượng đã bán mẫu về đúng 100 đơn vị.
     * Không xóa hoặc sửa lịch sử orders/order_items.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $products = DB::table('admin_products')
                ->select(['id', 'sold_count'])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($products->isEmpty()) {
                return;
            }

            $currentTotal = (int) $products->sum(static fn ($product): int => (int) $product->sold_count);

            if ($currentTotal <= 0) {
                DB::table('admin_products')->update(['sold_count' => 0]);
                DB::table('admin_products')->where('id', $products->first()->id)->update(['sold_count' => 100]);
                return;
            }

            $allocated = 0;
            foreach ($products as $product) {
                $newSold = intdiv((int) $product->sold_count * 100, $currentTotal);
                DB::table('admin_products')->where('id', $product->id)->update(['sold_count' => $newSold]);
                $allocated += $newSold;
            }

            $remaining = 100 - $allocated;
            if ($remaining > 0) {
                foreach ($products as $product) {
                    if ($remaining <= 0) {
                        break;
                    }
                    DB::table('admin_products')->where('id', $product->id)->increment('sold_count');
                    $remaining--;
                }
            }
        });
    }

    public function down(): void
    {
        // Không khôi phục số liệu bán cũ vì migration không lưu bản sao dữ liệu lịch sử.
    }
};
