<?php

namespace App\Http\Controllers;

use App\Models\Official;
use App\Models\Member;
use App\Models\Position;
use App\Models\User;
use OpenSpout\Common\Entity\Row;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

class SettingsController extends Controller
{
    public function positions(): View
    {
        $this->syncOfficialPositions();

        return view('settings-positions', [
            'positions' => Position::withCount(['users', 'officials'])->orderBy('name')->get(),
        ]);
    }

    public function storePosition(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:positions,code'],
            'name' => ['required', 'string', 'max:100'],
            'approval_scope' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Position::create([
            'code' => strtoupper(str_replace(' ', '_', $validated['code'])),
            'name' => $validated['name'],
            'approval_scope' => $validated['approval_scope'] ?: null,
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return redirect()->route('settings.positions')->with('success', 'Jabatan berhasil ditambahkan.');
    }

    public function updatePosition(Request $request, Position $position): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('positions', 'code')->ignore($position->id)],
            'name' => ['required', 'string', 'max:100'],
            'approval_scope' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $position->update([
            'code' => strtoupper(str_replace(' ', '_', $validated['code'])),
            'name' => $validated['name'],
            'approval_scope' => $validated['approval_scope'] ?: null,
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()->route('settings.positions')->with('success', 'Jabatan berhasil diperbarui.');
    }

    public function officials(Request $request): View
    {
        $positions = $this->syncOfficialPositions();
        $filters = [
            'search' => trim((string) $request->string('search')),
            'status' => (string) $request->string('status'),
        ];
        $officials = $this->officialQuery($request)->orderBy('name')->get();

        return view('settings-officials', [
            'officials' => $officials,
            'members' => Member::orderBy('name')->get(),
            'positions' => $positions,
            'filters' => $filters,
        ]);
    }

    public function exportOfficialsExcel(Request $request)
    {
        $officials = $this->officialQuery($request)->orderBy('name')->get();

        $directory = storage_path('app/exports');
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $fileName = 'pengurus-koperasi-' . now()->format('Ymd-His') . '.xlsx';
        $filePath = $directory . DIRECTORY_SEPARATOR . $fileName;

        $writer = new XlsxWriter();
        $writer->openToFile($filePath);
        $writer->addRow(Row::fromValues([
            'Nama Pengurus',
            'Login',
            'Jabatan',
            'Email',
            'Telepon',
            'Status',
            'Mulai Jabatan',
            'Akhir Jabatan',
        ]));

        foreach ($officials as $official) {
            $writer->addRow(Row::fromValues([
                $official->name,
                $official->login ?: ($official->user?->login ?? ''),
                $official->position?->name ?? '',
                $official->email ?? '',
                $official->phone ?? '',
                $official->is_active ? 'Aktif' : 'Nonaktif',
                optional($official->start_date)?->format('Y-m-d') ?? '',
                optional($official->end_date)?->format('Y-m-d') ?? '',
            ]));
        }

        $writer->close();

        return response()->download($filePath, $fileName)->deleteFileAfterSend(true);
    }

    public function storeOfficial(Request $request): RedirectResponse
    {
        $allowedPositionIds = $this->syncOfficialPositions()->pluck('id')->all();

        $validated = $request->validate([
            'position_id' => ['required', Rule::in($allowedPositionIds)],
            'member_id' => ['nullable', 'exists:members,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'login' => ['required', 'string', 'max:100', 'unique:officials,login'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:50'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $existingEmailUser = ! empty($validated['email'])
            ? User::query()->where('email', $validated['email'])->first()
            : null;
        if ($existingEmailUser && ! $this->canReuseOfficialAccount($existingEmailUser, null, $validated['member_id'] ?? null)) {
            throw ValidationException::withMessages([
                'email' => 'Email login ini sudah dipakai akun lain.',
            ]);
        }

        $existingLoginUser = User::query()->where('login', $validated['login'])->first();
        if ($existingLoginUser && ! $this->canReuseOfficialAccount($existingLoginUser, null, $validated['member_id'] ?? null)) {
            throw ValidationException::withMessages([
                'login' => 'Login ini sudah dipakai akun lain.',
            ]);
        }

        $official = Official::create([
            ...$this->extractOfficialPayload($validated),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);
        $this->syncOfficialUser($official, $validated);

        return redirect()->route('settings.officials')->with('success', 'Data pengurus berhasil ditambahkan.');
    }

    public function updateOfficial(Request $request, Official $official): RedirectResponse
    {
        $allowedPositionIds = $this->syncOfficialPositions()->pluck('id')->all();
        $official->loadMissing('user', 'member');
        $userId = $official->user?->id;

        $validated = $request->validate([
            'position_id' => ['required', Rule::in($allowedPositionIds)],
            'member_id' => ['nullable', 'exists:members,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'login' => ['required', 'string', 'max:100', Rule::unique('officials', 'login')->ignore($official->id)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:50'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $existingEmailUser = ! empty($validated['email'])
            ? User::query()
                ->where('email', $validated['email'])
                ->when($userId, fn ($query) => $query->whereKeyNot($userId))
                ->first()
            : null;
        if ($existingEmailUser && ! $this->canReuseOfficialAccount($existingEmailUser, $official, $validated['member_id'] ?? null)) {
            throw ValidationException::withMessages([
                'email' => 'Email login ini sudah dipakai akun lain.',
            ]);
        }

        $existingLoginUser = User::query()
            ->where('login', $validated['login'])
            ->when($userId, fn ($query) => $query->whereKeyNot($userId))
            ->first();
        if ($existingLoginUser && ! $this->canReuseOfficialAccount($existingLoginUser, $official, $validated['member_id'] ?? null)) {
            throw ValidationException::withMessages([
                'login' => 'Login ini sudah dipakai akun lain.',
            ]);
        }

        $official->update([
            ...$this->extractOfficialPayload($validated),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);
        $this->syncOfficialUser($official, $validated);

        return redirect()->route('settings.officials')->with('success', 'Data pengurus berhasil diperbarui.');
    }

    public function users(): View
    {
        $this->syncOfficialPositions();

        return view('settings-users', [
            'users' => User::with(['official.member', 'position'])->orderBy('name')->get(),
            'officials' => Official::with(['position', 'member'])->where('is_active', true)->orderBy('name')->get(),
            'positions' => Position::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'login' => ['required', 'string', 'max:100', 'unique:users,login'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', Rule::in(['admin', 'finance', 'staff', 'member'])],
            'official_id' => ['nullable', 'exists:officials,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        User::create([
            'name' => $validated['name'],
            'login' => $validated['login'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'member_id' => null,
            'official_id' => $validated['official_id'] ?? null,
            'position_id' => $validated['position_id'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return redirect()->route('settings.users')->with('success', 'User login berhasil ditambahkan.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'login' => ['required', 'string', 'max:100', Rule::unique('users', 'login')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', Rule::in(['admin', 'finance', 'staff', 'member'])],
            'official_id' => ['nullable', 'exists:officials,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'login' => $validated['login'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'member_id' => null,
            'official_id' => $validated['official_id'] ?? null,
            'position_id' => $validated['position_id'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);

        return redirect()->route('settings.users')->with('success', 'User login berhasil diperbarui.');
    }

    private function syncOfficialPositions(): Collection
    {
        $legacyCodes = [
            'ADMIN' => ['ADMIN_KOPERASI'],
            'KETUA_KOPERASI' => ['KETUA'],
        ];

        foreach (Position::OFFICIAL_OPTIONS as $index => $definition) {
            $position = Position::query()
                ->where('code', $definition['code'])
                ->first();

            if (!$position && isset($legacyCodes[$definition['code']])) {
                $position = Position::query()
                    ->whereIn('code', $legacyCodes[$definition['code']])
                    ->first();
            }

            $attributes = [
                'code' => $definition['code'],
                'name' => $definition['name'],
                'approval_scope' => null,
                'description' => 'Jabatan pengurus koperasi.',
                'is_active' => true,
            ];

            if ($position) {
                $position->fill($attributes)->save();
                continue;
            }

            Position::query()->create($attributes);
        }

        $codes = collect(Position::OFFICIAL_OPTIONS)->pluck('code');
        $caseOrder = $codes
            ->values()
            ->map(fn (string $code, int $index) => "WHEN '{$code}' THEN {$index}")
            ->implode(' ');

        return Position::query()
            ->whereIn('code', $codes)
            ->where('is_active', true)
            ->orderByRaw("CASE code {$caseOrder} ELSE 999 END")
            ->get();
    }

    private function extractOfficialPayload(array $validated): array
    {
        unset($validated['password'], $validated['password_confirmation']);

        return $validated;
    }

    private function syncOfficialUser(Official $official, array $validated): void
    {
        $official->loadMissing('user', 'member');

        $user = $official->user
            ?? ($official->member_id ? User::query()->where('member_id', $official->member_id)->first() : null)
            ?? User::query()->firstOrNew(['login' => $validated['login']]);

        if ($user->exists && $user->official_id && (int) $user->official_id !== (int) $official->id) {
            throw ValidationException::withMessages([
                'login' => 'Login ini sudah dipakai pengurus lain.',
            ]);
        }

        if ($user->exists && $user->member_id && $official->member_id && (int) $user->member_id !== (int) $official->member_id) {
            throw ValidationException::withMessages([
                'login' => 'Login ini sudah dipakai anggota lain.',
            ]);
        }

        $payload = [
            'name' => $official->name,
            'login' => $validated['login'],
            'email' => $validated['email'] ?: ($user->email ?: 'official-' . $official->id . '@koperasi.local'),
            'role' => $this->resolveOfficialRole($official),
            'official_id' => $official->id,
            'member_id' => $official->member_id,
            'position_id' => $official->position_id,
            'is_active' => (bool) $official->is_active,
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        if (! $user->exists && empty($payload['password'])) {
            throw ValidationException::withMessages([
                'password' => 'Password wajib diisi untuk akun baru.',
            ]);
        }

        $user->fill($payload)->save();
    }

    private function resolveOfficialRole(Official $official): string
    {
        return match ($official->position?->code) {
            'ADMINISTRATOR' => 'admin',
            'ADMIN' => 'admin',
            'FINANCE' => 'finance',
            default => 'staff',
        };
    }

    private function canReuseOfficialAccount(User $user, ?Official $official, ?int $memberId): bool
    {
        if ($official && (int) $user->official_id === (int) $official->id) {
            return true;
        }

        return $memberId !== null && (int) $user->member_id === (int) $memberId;
    }

    private function officialQuery(Request $request)
    {
        $search = trim((string) $request->string('search'));
        $status = (string) $request->string('status');

        return Official::with(['position', 'user', 'member'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($officialQuery) use ($search) {
                    $officialQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('login', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('position', fn ($positionQuery) => $positionQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when(in_array($status, ['active', 'inactive'], true), function ($query) use ($status) {
                $query->where('is_active', $status === 'active');
            });
    }
}
