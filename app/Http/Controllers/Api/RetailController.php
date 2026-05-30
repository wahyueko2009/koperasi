<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RetailItem;
use App\Models\RetailTransaction;
use App\Services\RetailService;
use Illuminate\Http\Request;

class RetailController extends Controller
{
    public function __construct(private RetailService $retailService) {}

    private function canManageRetailFor(?int $memberId): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isAdministrator() || $user?->isAdmin() || $memberId === null || (int) ($user?->member_id ?? 0) === $memberId);
    }

    /**
     * Lihat daftar item retail
     */
    public function items(Request $request)
    {
        $query = RetailItem::where('is_active', true);

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $items = $query->paginate();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Buat transaksi retail baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'nullable|exists:members,id',
            'category' => 'required|in:indomaret,photocopy',
            'items' => 'required|array|min:1',
            'items.*.retail_item_id' => 'required|exists:retail_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.subtotal' => 'required|numeric|min:0',
            'payment_method' => 'nullable|in:cash,salary_cut',
            'notes' => 'nullable|string',
        ]);

        abort_unless($this->canManageRetailFor(isset($validated['member_id']) ? (int) $validated['member_id'] : null), 403, 'Anda tidak dapat mencatat transaksi retail ini.');

        try {
            $transaction = $this->retailService->recordTransaction(
                memberId: $validated['member_id'] ?? null,
                category: $validated['category'],
                items: $validated['items'],
                paymentMethod: $validated['payment_method'] ?? 'cash',
                notes: $validated['notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Transaksi retail berhasil dicatat',
                'data' => $transaction->load('member', 'items.item'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencatat transaksi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lihat riwayat transaksi
     */
    public function transactions(Request $request)
    {
        $query = RetailTransaction::query();

        $requestedMemberId = $request->has('member_id') ? (int) $request->member_id : null;
        abort_unless($this->canManageRetailFor($requestedMemberId), 403, 'Anda tidak dapat melihat transaksi retail ini.');

        if ($request->has('member_id')) {
            $query->where('member_id', $request->member_id);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $transactions = $query->with('member', 'items.item')
            ->latest('transaction_date')
            ->paginate();

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }
}
