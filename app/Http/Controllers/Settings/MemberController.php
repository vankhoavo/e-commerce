<?php

namespace App\Http\Controllers\Settings;

use App\Models\Order;
use App\Services\MembershipService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberController
{
    public function show(Request $request, MembershipService $membership): Response
    {
        $user = $request->user();
        $total = $membership->totalSpent($user);
        $tier = $membership->tier($user);
        $tiers = MembershipService::TIERS;
        $current = $tiers[$tier];
        $next = $tier === 'bronze' ? $tiers['gold'] : ($tier === 'gold' ? $tiers['diamond'] : null);
        $progress = $next ? min(100, max(0, (($total - $current['minimum']) / max(1, $next['minimum'] - $current['minimum'])) * 100)) : 100;

        return Inertia::render('settings/Member', [
            'membership' => [
                'tier' => $tier,
                'label' => $current['label'],
                'totalSpent' => $total,
                'discountRate' => $current['rate'],
                'nextTier' => $next ? ['label'=>$next['label'],'minimum'=>$next['minimum'],'remaining'=>max(0,$next['minimum']-$total)] : null,
                'progress' => round($progress,1),
                'memberType' => $user->member_type,
            ],
            'weeklyCoupons' => $membership->weeklyCoupons($user),
            'specialEvents' => $membership->specialEvents($user),
            'recentOrders' => Order::query()->where('user_id',$user->id)->whereNotIn('status',['Hủy hàng'])->latest()->limit(5)->get(['code','total','status','created_at'])->map(fn(Order $order)=>['code'=>$order->code,'total'=>(int)$order->total,'status'=>$order->status,'createdAt'=>$order->created_at?->toIso8601String()])->values(),
        ]);
    }
}
