<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Member;
use App\Models\MemberOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LegacySpreadsheetImportService
{
    public function preview(array $sheets): array
    {
        $rows = [];
        $stats = ['valid' => 0, 'duplicates' => 0, 'failed' => 0];
        $seen = [];

        foreach ($sheets as $sheetName => $sheetRows) {
            $lastName = null;

            foreach (array_values($sheetRows) as $index => $row) {
                if ($index === 0 && $this->looksLikeHeader($row)) {
                    continue;
                }

                $name = trim((string) ($row['NAMA'] ?? $row['nama'] ?? $row[0] ?? ''));
                $batchNumber = trim((string) ($row['BATCH'] ?? $row['batch'] ?? $row[1] ?? ''));

                if ($name !== '') {
                    $lastName = $name;
                }

                if (! $lastName || $batchNumber === '') {
                    $stats['failed']++;
                    $rows[] = compact('sheetName', 'index', 'name', 'batchNumber') + ['status' => 'failed'];

                    continue;
                }

                $key = Str::lower($lastName).'|'.$batchNumber;

                if (isset($seen[$key])) {
                    $stats['duplicates']++;
                    $rows[] = [
                        'sheet' => $sheetName,
                        'row' => $index + 1,
                        'member_name' => $lastName,
                        'batch_number' => $batchNumber,
                        'status' => 'duplicate',
                    ];

                    continue;
                }

                $seen[$key] = true;
                $stats['valid']++;
                $rows[] = [
                    'sheet' => $sheetName,
                    'row' => $index + 1,
                    'member_name' => $lastName,
                    'batch_number' => $batchNumber,
                    'status' => 'valid',
                ];
            }
        }

        return ['stats' => $stats, 'rows' => $rows];
    }

    public function import(array $sheets): array
    {
        $preview = $this->preview($sheets);

        DB::transaction(function () use ($preview) {
            $affectedBatches = [];
            foreach ($preview['rows'] as $row) {
                if (($row['status'] ?? null) !== 'valid') {
                    continue;
                }

                $member = Member::firstOrCreate(
                    ['display_name' => $row['member_name']],
                    ['member_code' => $this->makeMemberCode($row['member_name'])]
                );

                $batch = Batch::firstOrCreate(['batch_number' => $row['batch_number']]);
                $batch = Batch::query()->lockForUpdate()->findOrFail($batch->id);
                $affectedBatches[$batch->id] = $batch;

                MemberOrder::firstOrCreate(
                    ['member_id' => $member->id, 'batch_id' => $batch->id],
                    ['order_code' => 'ORD-'.$member->member_code.'-'.$batch->batch_number]
                );
            }
            foreach ($affectedBatches as $batch) {
                app(BatchEmsTaxService::class)->sync($batch);
            }
        });

        return $preview;
    }

    private function looksLikeHeader(array $row): bool
    {
        $name = Str::lower(trim((string) ($row['NAMA'] ?? $row['nama'] ?? $row[0] ?? '')));
        $batch = Str::lower(trim((string) ($row['BATCH'] ?? $row['batch'] ?? $row[1] ?? '')));

        return $name === 'nama' || $batch === 'batch';
    }

    private function makeMemberCode(string $name): string
    {
        $base = Str::upper(Str::slug($name, ''));
        $base = $base !== '' ? Str::limit($base, 12, '') : 'MEMBER';
        $candidate = $base;
        $counter = 1;

        while (Member::where('member_code', $candidate)->exists()) {
            $candidate = $base.'-'.$counter++;
        }

        return $candidate;
    }
}
