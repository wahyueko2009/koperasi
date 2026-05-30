<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MemberController extends Controller
{
    private function canManageMembers(): bool
    {
        return (bool) (auth()->user()?->isAdministrator() || auth()->user()?->isAdmin());
    }

    private function ensureMemberReadable(Member $member): void
    {
        $user = auth()->user();

        abort_unless(
            $this->canManageMembers() || (int) ($user?->member_id ?? 0) === (int) $member->id,
            403,
            'Anda tidak memiliki akses ke data anggota ini.'
        );
    }

    /**
     * Lihat semua anggota
     */
    public function index()
    {
        abort_unless($this->canManageMembers(), 403, 'Akses daftar anggota dibatasi untuk admin.');

        $members = Member::paginate();

        return response()->json([
            'success' => true,
            'data' => $members,
        ]);
    }

    /**
     * Lihat detail anggota
     */
    public function show(Member $member)
    {
        $this->ensureMemberReadable($member);

        $member->load('savings', 'loans', 'memberLedgers');

        return response()->json([
            'success' => true,
            'data' => $member,
        ]);
    }

    /**
     * Buat anggota baru
     */
    public function store(Request $request)
    {
        abort_unless($this->canManageMembers(), 403, 'Pembuatan anggota dibatasi untuk admin.');

        $validated = $request->validate([
            'nik' => 'required|unique:members',
            'name' => 'required|string',
            'email' => 'required|unique:members|email',
            'login' => 'required|unique:members|string|max:100',
            'password' => 'required|string|min:6|confirmed',
            'spouse_name' => 'nullable|string|max:255',
            'npwp' => 'nullable|string|max:32',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'company_unit' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'status' => 'nullable|in:active,inactive,suspended',
        ]);

        try {
            $memberPayload = $validated;
            unset($memberPayload['password'], $memberPayload['password_confirmation']);

            $member = Member::create($memberPayload + [
                'status' => $validated['status'] ?? 'active',
                'membership_type' => 'anggota_biasa',
            ]);

            User::create([
                'member_id' => $member->id,
                'name' => $member->name,
                'login' => $validated['login'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'member',
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Anggota berhasil dibuat',
                'data' => $member,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat anggota: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update anggota
     */
    public function update(Request $request, Member $member)
    {
        abort_unless($this->canManageMembers(), 403, 'Perubahan anggota dibatasi untuk admin.');

        $member->loadMissing('user');

        $validated = $request->validate([
            'nik' => 'unique:members,nik,' . $member->id,
            'name' => 'string',
            'email' => 'email|unique:members,email,' . $member->id,
            'login' => 'string|max:100|unique:members,login,' . $member->id,
            'password' => 'nullable|string|min:6|confirmed',
            'spouse_name' => 'nullable|string|max:255',
            'npwp' => 'nullable|string|max:32',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'company_unit' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'status' => 'in:active,inactive,suspended',
        ]);

        try {
            $memberPayload = $validated;
            unset($memberPayload['password'], $memberPayload['password_confirmation']);
            \DB::transaction(function () use ($validated, $member, $memberPayload) {
                if (isset($validated['email']) && User::query()
                    ->where('email', $validated['email'])
                    ->whereKeyNot($member->user?->id)
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'email' => 'Email login ini sudah dipakai akun lain.',
                    ]);
                }

                if (isset($validated['login']) && User::query()
                    ->where('login', $validated['login'])
                    ->whereKeyNot($member->user?->id)
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'login' => 'Login ini sudah dipakai akun lain.',
                    ]);
                }

                $member->update($memberPayload);

                $member->user()?->update([
                    'name' => $validated['name'] ?? $member->name,
                    'login' => $validated['login'] ?? $member->login,
                    'email' => $validated['email'] ?? $member->email,
                    ...(! empty($validated['password']) ? ['password' => Hash::make($validated['password'])] : []),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Anggota berhasil diupdate',
                'data' => $member,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate anggota: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lihat saldo anggota
     */
    public function balance(Member $member)
    {
        $this->ensureMemberReadable($member);

        $totalSavings = $member->savings()->sum('amount');
        $totalLoans = $member->loans()
            ->where('status', '!=', 'completed')
            ->sum('remaining_balance');
        $totalReceivable = $member->balance_receivable;

        return response()->json([
            'success' => true,
            'data' => [
                'member' => $member,
                'total_savings' => $totalSavings,
                'total_loans' => $totalLoans,
                'total_receivable' => $totalReceivable,
            ],
        ]);
    }
}
