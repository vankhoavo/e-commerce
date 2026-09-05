<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\AccountDeletionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AccountDeletionRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->role === UserRole::CUSTOMER, 403);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $pending = AccountDeletionRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($pending) {
            return back()->with('status', 'account-deletion-request-pending');
        }

        AccountDeletionRequest::create([
            'user_id' => $user->id,
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        return back()->with('status', 'account-deletion-requested');
    }

    public function index(): Response
    {
        $requests = AccountDeletionRequest::query()
            ->with([
                'user:id,name,email,phone,address,address_province,address_ward,address_detail,is_active,email_verified_at',
                'reviewer:id,name',
            ])
            ->latest('requested_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/AccountDeletionRequests', [
            'requests' => $requests,
            'summary' => [
                'pending' => AccountDeletionRequest::where('status', 'pending')->count(),
                'approved' => AccountDeletionRequest::where('status', 'approved')->count(),
                'rejected' => AccountDeletionRequest::where('status', 'rejected')->count(),
            ],
        ]);
    }

    public function approve(Request $request, AccountDeletionRequest $deletionRequest): RedirectResponse
    {
        abort_unless($deletionRequest->status === 'pending', 422, 'Yêu cầu này đã được xử lý.');

        $data = $request->validate([
            'review_note' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($deletionRequest, $data, $request): void {
            $user = $deletionRequest->user()->lockForUpdate()->first();
            abort_unless($user, 422, 'Tài khoản khách hàng không còn tồn tại.');

            $deletionRequest->update([
                'status' => 'approved',
                'reviewed_by_user_id' => $request->user()->id,
                'reviewed_at' => now(),
                'review_note' => $data['review_note'] ?? null,
            ]);

            $user->delete();
        });

        return back()->with('success', 'Đã duyệt yêu cầu và xóa mềm tài khoản khách hàng.');
    }

    public function reject(Request $request, AccountDeletionRequest $deletionRequest): RedirectResponse
    {
        abort_unless($deletionRequest->status === 'pending', 422, 'Yêu cầu này đã được xử lý.');

        $data = $request->validate([
            'review_note' => ['required', 'string', 'max:2000'],
        ]);

        $deletionRequest->update([
            'status' => 'rejected',
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => $data['review_note'],
        ]);

        return back()->with('success', 'Đã từ chối yêu cầu xóa tài khoản.');
    }
}
