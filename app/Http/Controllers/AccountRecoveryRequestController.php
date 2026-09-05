<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\AccountRecoveryRequest;
use App\Models\User;
use App\Services\AccountRecoveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AccountRecoveryRequestController extends Controller
{
    public function requestEmail(Request $request, AccountRecoveryService $service): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = Str::lower(trim($data['email']));
        $user = User::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user || ! $user->trashed() || $user->role !== UserRole::CUSTOMER) {
            return back()->withErrors(['email' => 'Không tìm thấy tài khoản đã được xóa mềm với email này.']);
        }
        if (! $user->is_active) {
            return back()->withErrors(['email' => 'Tài khoản đang bị khóa và không thể gửi yêu cầu khôi phục.']);
        }

        $pending = AccountRecoveryRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending_otp', 'pending_approval'])
            ->exists();
        if ($pending) return back()->with('status', 'recovery-pending');

        $recovery = $service->createOtpRequest($user, 'email');
        $request->session()->put('account_recovery_request_id', $recovery->id);
        return to_route('account.recovery.verify')->with('status', 'recovery-otp-sent');
    }

    public function showVerify(Request $request): Response|RedirectResponse
    {
        $recovery = $this->sessionRecovery($request);
        if (! $recovery || $recovery->status !== 'pending_otp' || $recovery->otpExpired()) {
            $request->session()->forget('account_recovery_request_id');
            return to_route('account.recovery')->withErrors(['email' => 'Mã OTP đã hết hạn. Vui lòng yêu cầu mã mới.']);
        }
        return Inertia::render('auth/AccountRecoveryVerify', [
            'email' => $this->maskEmail($recovery->email),
            'expiresAt' => $recovery->otp_expires_at->toISOString(),
            'remainingAttempts' => max(0, 5 - $recovery->otp_attempts),
            'status' => session('status'),
        ]);
    }

    public function verify(Request $request, AccountRecoveryService $service): RedirectResponse
    {
        $recovery = $this->sessionRecovery($request);
        if (! $recovery) return to_route('account.recovery');
        $data = $request->validate(['code' => ['required', 'digits:6']]);
        if ($recovery->otpExpired()) return to_route('account.recovery')->withErrors(['email' => 'Mã OTP đã hết hạn.']);
        if ($recovery->otp_attempts >= 5) return to_route('account.recovery')->withErrors(['email' => 'Bạn đã nhập sai OTP quá số lần cho phép.']);
        if (! $service->verifyOtp($recovery, $data['code'])) {
            $fresh = $recovery->fresh();
            return back()->withErrors(['code' => 'Mã OTP không chính xác. Bạn còn '.max(0, 5 - (int) $fresh->otp_attempts).' lần thử.']);
        }
        return to_route('account.recovery.pending')->with('status', 'recovery-submitted');
    }

    public function pending(Request $request): Response|RedirectResponse
    {
        $recovery = $this->sessionRecovery($request);
        if (! $recovery || $recovery->status !== 'pending_approval') return to_route('account.recovery');
        return Inertia::render('auth/AccountRecoveryPending', [
            'name' => $recovery->user?->name,
            'email' => $recovery->email,
            'method' => $recovery->method,
            'status' => session('status'),
        ]);
    }

    public function requestGoogle(Request $request, User $user, AccountRecoveryService $service): JsonResponse
    {
        if (! $user->trashed() || $user->role !== UserRole::CUSTOMER || ! $user->is_active) {
            return response()->json(['ok' => false], 422);
        }
        $recovery = $service->createOtpRequest($user, 'google');
        $request->session()->put('account_recovery_request_id', $recovery->id);
        return response()->json(['ok' => true, 'redirect' => route('account.recovery.pending')]);
    }

    public function index(Request $request): Response
    {
        $this->authorizeReviewer($request);
        $requests = AccountRecoveryRequest::query()->with(['user:id,name,email,google_id,deleted_at', 'approver:id,name,role'])
            ->latest()->paginate(20)->withQueryString();
        return Inertia::render('admin/AccountRecoveryRequests', [
            'requests' => $requests,
            'summary' => [
                'pending' => AccountRecoveryRequest::where('status', 'pending_approval')->count(),
                'approved' => AccountRecoveryRequest::where('status', 'approved')->count(),
                'rejected' => AccountRecoveryRequest::where('status', 'rejected')->count(),
                'restored' => AccountRecoveryRequest::where('status', 'restored')->count(),
            ],
        ]);
    }

    public function approve(Request $request, AccountRecoveryRequest $recovery): RedirectResponse
    {
        $this->authorizeReviewer($request);
        abort_unless($recovery->status === 'pending_approval', 422, 'Yêu cầu này đã được xử lý.');
        $user = $recovery->user()->withTrashed()->lockForUpdate()->first();
        abort_unless($user && $user->trashed(), 422, 'Tài khoản không còn ở trạng thái xóa mềm.');

        DB::transaction(function () use ($recovery, $user, $request): void {
            $user->restore();
            $user->forceFill(['is_active' => true])->save();
            $recovery->update(['status' => 'restored', 'approved_by_user_id' => $request->user()->id, 'approved_at' => now()]);
        });
        return back()->with('success', 'Đã phê duyệt và khôi phục tài khoản khách hàng.');
    }

    public function reject(Request $request, AccountRecoveryRequest $recovery): RedirectResponse
    {
        $this->authorizeReviewer($request);
        abort_unless($recovery->status === 'pending_approval', 422, 'Yêu cầu này đã được xử lý.');
        $data = $request->validate(['review_note' => ['required', 'string', 'max:2000']]);
        $recovery->update(['status' => 'rejected', 'rejected_by_user_id' => $request->user()->id, 'rejected_at' => now(), 'review_note' => $data['review_note']]);
        return back()->with('success', 'Đã từ chối yêu cầu khôi phục tài khoản.');
    }

    private function sessionRecovery(Request $request): ?AccountRecoveryRequest
    {
        $id = $request->session()->get('account_recovery_request_id');
        return $id ? AccountRecoveryRequest::query()->with('user')->find($id) : null;
    }

    private function authorizeReviewer(Request $request): void
    {
        abort_unless($request->user() && $request->user()->is_active && in_array($request->user()->role, [UserRole::ADMIN, UserRole::SENIOR_STAFF], true), 403);
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($local === '' || $domain === '') return $email;
        return substr($local, 0, min(2, strlen($local))).str_repeat('*', max(2, strlen($local) - 2)).'@'.$domain;
    }
}
