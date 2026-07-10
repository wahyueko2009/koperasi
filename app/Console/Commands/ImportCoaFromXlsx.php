<?php

namespace App\Console\Commands;

use App\Models\ChartOfAccount;
use App\Models\GeneralLedger;
use App\Models\GlAccountGroup;
use App\Models\GlAccountMapping;
use App\Models\JournalEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\XLSX\Reader;
use RuntimeException;

class ImportCoaFromXlsx extends Command
{
    protected $signature = 'app:import-coa-xlsx
        {path : Absolute path ke file XLSX COA}
        {--replace : Hapus COA, mapping, jurnal, dan ledger GL yang ada sebelum import}';

    protected $description = 'Import chart of accounts dari file XLSX dan bentuk hierarchy/group otomatis';

    public function handle(): int
    {
        $path = (string) $this->argument('path');

        if (! is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $rows = $this->readRows($path);

        if ($rows->isEmpty()) {
            $this->error('File COA tidak berisi data yang bisa diimpor.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($rows) {
            if ($this->option('replace')) {
                $this->resetExistingGlData();
            }

            $preparedRows = $this->prepareRows($rows);
            $groups = $this->syncGroups($preparedRows);
            $this->syncAccounts($preparedRows, $groups);
        });

        $this->info('Import COA selesai.');
        $this->line('Jumlah akun aktif: ' . ChartOfAccount::query()->count());
        $this->line('Jumlah kelompok akun: ' . GlAccountGroup::query()->count());

        return self::SUCCESS;
    }

    private function readRows(string $path): Collection
    {
        $reader = new Reader();
        $reader->open($path);

        $rows = collect();

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $index => $row) {
                if ($index === 1) {
                    continue;
                }

                $cells = $row->toArray();
                $code = trim((string) ($cells[0] ?? ''));
                $name = trim((string) ($cells[1] ?? ''));

                if ($code === '' && $name === '') {
                    continue;
                }

                $rows->push([
                    'code' => $code,
                    'name' => $name,
                ]);
            }
        }

        $reader->close();

        return $rows;
    }

    private function resetExistingGlData(): void
    {
        GlAccountMapping::query()->delete();
        GeneralLedger::query()->delete();
        JournalEntry::query()->delete();

        ChartOfAccount::query()
            ->orderByDesc('code')
            ->get()
            ->each
            ->delete();

        GlAccountGroup::query()->delete();
    }

    private function prepareRows(Collection $rows): Collection
    {
        $rowsByCode = $rows->keyBy('code');

        return $rows->map(function (array $row) use ($rowsByCode) {
            $type = $this->resolveAccountType($row['code']);
            $children = $rowsByCode->filter(fn (array $candidate) => $this->resolveParentCode($candidate['code'], $rowsByCode) === $row['code']);
            $isHeader = $children->isNotEmpty();
            $parentCode = $this->resolveParentCode($row['code'], $rowsByCode);
            $groupCode = $this->resolveGroupCode($row['code']);

            return [
                'code' => $row['code'],
                'name' => $row['name'],
                'account_type' => $type,
                'normal_balance' => $this->resolveNormalBalance($type, $row['name']),
                'parent_code' => $parentCode,
                'group_code' => $groupCode,
                'is_header' => $isHeader,
                'is_active' => true,
            ];
        });
    }

    private function syncGroups(Collection $preparedRows): Collection
    {
        $groups = collect();

        $preparedRows
            ->filter(fn (array $row) => preg_match('/^[1-9]\.000\.000$/', $row['code']) === 1)
            ->each(function (array $row) use ($groups) {
                $group = GlAccountGroup::query()->updateOrCreate(
                    ['code' => $row['code']],
                    [
                        'name' => $row['name'],
                        'account_type' => $row['account_type'],
                        'normal_balance' => $row['normal_balance'],
                        'description' => 'Import dari file COA XLSX.',
                        'is_active' => true,
                    ]
                );

                $groups->put($group->code, $group);
            });

        return $groups;
    }

    private function syncAccounts(Collection $preparedRows, Collection $groups): void
    {
        $created = collect();

        $preparedRows
            ->sortBy(fn (array $row) => [$this->depth($row['code']), $row['code']])
            ->each(function (array $row) use ($groups, $created) {
                $parentId = null;

                if ($row['parent_code']) {
                    $parent = $created->get($row['parent_code']) ?? ChartOfAccount::query()->where('code', $row['parent_code'])->first();

                    if (! $parent) {
                        throw new RuntimeException("Parent COA tidak ditemukan untuk {$row['code']} ({$row['parent_code']}).");
                    }

                    $parentId = $parent->id;
                }

                $group = $groups->get($row['group_code']);

                $account = ChartOfAccount::query()->updateOrCreate(
                    ['code' => $row['code']],
                    [
                        'name' => $row['name'],
                        'account_type' => $row['account_type'],
                        'account_group_id' => $group?->id,
                        'normal_balance' => $row['normal_balance'],
                        'parent_id' => $parentId,
                        'is_header' => $row['is_header'],
                        'description' => null,
                        'is_active' => $row['is_active'],
                    ]
                );

                $created->put($account->code, $account);
            });
    }

    private function resolveAccountType(string $code): string
    {
        return match (substr($code, 0, 1)) {
            '0', '1', '2', '3', '4' => 'asset',
            '5', '6' => 'liability',
            '7' => 'equity',
            '8' => 'income',
            '9' => 'expense',
            default => throw new RuntimeException("Digit akun tidak dikenali: {$code}"),
        };
    }

    private function resolveNormalBalance(string $type, string $name): string
    {
        $normalized = strtolower($name);

        if ($type === 'asset' && (
            str_contains($normalized, 'akum.') ||
            str_contains($normalized, 'akumulasi') ||
            str_contains($normalized, 'penyisihan')
        )) {
            return 'credit';
        }

        return in_array($type, ['asset', 'expense'], true) ? 'debit' : 'credit';
    }

    private function resolveGroupCode(string $code): ?string
    {
        if ($code === '0.000.000') {
            return null;
        }

        return substr($code, 0, 1) . '.000.000';
    }

    private function resolveParentCode(string $code, Collection $rowsByCode): ?string
    {
        if ($code === '0.000.000') {
            return null;
        }

        [$major, $middle, $minor] = array_map('intval', explode('.', $code));
        $candidates = [];

        if ($minor !== 0) {
            $candidates[] = sprintf('%d.%03d.000', $major, $middle);
        }

        if ($middle !== 0 && $middle % 10 !== 0) {
            $candidates[] = sprintf('%d.%03d.000', $major, (int) floor($middle / 10) * 10);
        }

        if ($middle !== 0 && $middle % 100 !== 0) {
            $candidates[] = sprintf('%d.%03d.000', $major, (int) floor($middle / 100) * 100);
        }

        $candidates[] = sprintf('%d.000.000', $major);

        if (in_array($major, [1, 2, 3, 4], true)) {
            $candidates[] = '0.000.000';
        }

        foreach (array_unique($candidates) as $candidate) {
            if ($candidate !== $code && $rowsByCode->has($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function depth(string $code): int
    {
        if ($code === '0.000.000') {
            return 0;
        }

        [$major, $middle, $minor] = array_map('intval', explode('.', $code));

        if ($minor !== 0) {
            return 3;
        }

        if ($middle % 10 !== 0) {
            return 2;
        }

        if ($middle % 100 !== 0) {
            return 1;
        }

        return 0;
    }
}
