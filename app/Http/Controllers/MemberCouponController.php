<?php

namespace App\Http\Controllers;

use App\Services\MembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class MemberCouponController
{
    public function validateCode(Request $request, MembershipService $membership):JsonResponse
    {
        $data=$request->validate(['code'=>['required','string','max:50'],'subtotal'=>['required','integer','min:0']]);
        $coupon=$membership->couponFor($request->user(),$data['code']);
        if(!$coupon)return response()->json(['message'=>'Mã giảm giá không tồn tại, chưa đến giờ hoặc không dành cho hạng thành viên của bạn.'],422);
        $discount=(int)round($data['subtotal']*$coupon['discount']/100);
        return response()->json(['coupon'=>$coupon,'discount'=>min($discount,$data['subtotal'])]);
    }
}
