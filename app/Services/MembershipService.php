<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class MembershipService
{
    public const TIERS = [
        'bronze' => ['label'=>'Hạng Đồng','minimum'=>0,'rate'=>5],
        'gold' => ['label'=>'Hạng Vàng','minimum'=>5000000,'rate'=>7],
        'diamond' => ['label'=>'Hạng Kim cương','minimum'=>20000000,'rate'=>10],
    ];

    public function totalSpent(User $user): int
    {
        $emails = array_values(array_filter([Str::lower(trim((string) $user->email)), ...Order::query()->where('user_id',$user->id)->pluck('customer_email')->map(fn($v)=>Str::lower(trim((string)$v)))->all()]));
        $phones = array_values(array_filter([trim((string)$user->phone), ...Order::query()->where('user_id',$user->id)->pluck('customer_phone')->map(fn($v)=>trim((string)$v))->all()]));
        $query = Order::query()->whereNotIn('status',['Hủy hàng'])->where(function($q) use ($emails,$phones,$user){
            $q->where('user_id',$user->id);
            if ($emails) $q->orWhereIn('customer_email',$emails);
            if ($phones) $q->orWhereIn('customer_phone',$phones);
        });
        return (int) $query->sum('total');
    }

    public function tier(User $user): string
    {
        $total = $this->totalSpent($user);
        return $total >= self::TIERS['diamond']['minimum'] ? 'diamond' : ($total >= self::TIERS['gold']['minimum'] ? 'gold' : 'bronze');
    }

    public function weeklyCoupons(User $user, ?Carbon $now = null): array
    {
        $now ??= now();
        if (! in_array($now->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY], true)) return [];
        $slot = intdiv(((int)$now->hour), 6);
        $tier = $this->tier($user);
        $types = ['linh-kien','laptop','phu-kien'];
        $seed = $now->copy()->startOfWeek()->format('Y-m-d').'|'.$slot.'|'.$tier.'|techstore';
        $offset = hexdec(substr(sha1($seed),0,6)) % 3;
        $selected = [$types[$offset], $types[($offset + 1) % 3]];
        $discount = self::TIERS[$tier]['rate'];
        return array_map(function(string $type) use ($now,$tier,$discount): array {
            $code = 'TS'.strtoupper(substr($type,0,2)).strtoupper(substr(sha1($now->format('Ymd').$type.$tier),0,5));
            return ['code'=>$code,'type'=>$type,'discount'=>$discount,'starts_at'=>$now->copy()->startOfHour()->toIso8601String(),'ends_at'=>$now->copy()->startOfHour()->addHour()->toIso8601String(),'label'=>$type==='laptop'?'Laptop':($type==='linh-kien'?'Linh kiện':'Phụ kiện')];
        }, $selected);
    }

    public function specialEvents(User $user, ?Carbon $now = null): array
    {
        $now ??= now();
        if (! in_array(strtolower((string)$user->member_type), ['student','teacher'], true)) return [];
        $year = $now->year;
        return [
            ['date'=>"{$year}-09-05",'title'=>'Tựu trường / Khai giảng','code'=>'TSEDU'.substr((string)$year,-2),'note'=>'Ưu đãi Student/Teacher quanh ngày 5/9.'],
            ['date'=>"{$year}-11-20",'title'=>'Ngày Nhà giáo Việt Nam','code'=>'TSGVN'.substr((string)$year,-2),'note'=>'Ưu đãi dành cho Teacher và chương trình giáo dục.'],
        ];
    }
}
