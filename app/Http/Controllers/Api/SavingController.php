<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Saving;
use App\Services\SavingService;
use Illuminate\Http\Request;

class SavingController extends Controller
{
    public function __construct(private SavingService $savingService) {}

    private function canManageMemberFinance(int $memberId): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isAdministrator() || $user?->isAdmin() || (int) ($user?->member_id ?? 0) === $memberId);
    }

    /**
     * Catat simpanan baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'saving_type_id' => 'required|exists:saving_types,id',
            'amount' => 'required|numeric|min:1000',
            'payment_method' => 'nullable|in:cash,transfer,salary_cut',
            'notes' => 'nullable|string',
        ]);

        abort_unless($this->canManageMemberFinance((int) $validated['member_id']), 403, 'Anda tidak dapat mencatat simpanan untuk anggota ini.');

        $member = Member::findOrFail((int) $validated['member_id']);
        if ($member->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => "Anggota {$member->name} tidak bisa mencatat simpanan karena statusnya " . ucfirst($member->status) . '.',
            ], 422);
        }

        try {
            $saving = $this->savingService->recordSaving(
                memberId: $validated['member_id'],
                savingTypeId: $validated['saving_type_id'],
                amount: $validated['amount'],
                paymentMethod: $validated['payment_method'] ?? 'cash',
                notes: $validated['notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Simpanan berhasil dicatat',
                'data' => $saving->load('member', 'savingType'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencatat simpanan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lihat riwayat simpanan
     */
    public function memberSavings($memberId)
    {
        abort_unless($this->canManageMemberFinance((int) $memberId), 403, 'Anda tidak dapat melihat simpanan anggota ini.');

        $member = Member::findOrFail($memberId);
        $savings = Saving::where('member_id', $memberId)
            ->with('savingType')
            ->latest('deposit_date')
            ->paginate();

        return response()->json([
            'success' => true,
            'member' => $member,
            'data' => $savings,
        ]);
    }
}
