<?php

namespace App\Services;

use App\Models\ImportBatch;
use App\Models\PurchaseHistory;
use Illuminate\Support\Facades\DB;

class HistoryImporter
{
    /** Append new rows and refresh matching source dates, or replace all history. */
    public function import(array $rows, string $fileName, ?string $sheet, string $mode = 'append'): array
    {
        return DB::transaction(function () use ($rows, $fileName, $sheet, $mode) {
            $updated = 0;
            if ($mode === 'replace') {
                PurchaseHistory::query()->delete();
                ImportBatch::query()->delete();
            } elseif ($mode === 'append') {
                $dates = collect($rows)->pluck('purchase_date')->filter()->unique()->all();
                if ($dates !== []) {
                    $batches = ImportBatch::query()
                        ->where('file_name', $fileName)
                        ->where('sheet_name', $sheet)
                        ->get();

                    if ($batches->isNotEmpty()) {
                        $batchIds = $batches->modelKeys();
                        $updated = PurchaseHistory::query()
                            ->whereIn('import_batch_id', $batchIds)
                            ->whereIn('purchase_date', $dates)
                            ->delete();

                        foreach ($batches as $existingBatch) {
                            $existingBatch->update([
                                'rows_count' => PurchaseHistory::query()
                                    ->where('import_batch_id', $existingBatch->id)
                                    ->count(),
                            ]);
                        }
                    }
                }
            }

            $batch = ImportBatch::create(['file_name' => $fileName, 'sheet_name' => $sheet, 'rows_count' => 0]);

            $seen = [];
            $payload = [];
            $now = now();
            foreach ($rows as $r) {
                $key = md5(implode('|', [
                    $r['date_text'], $r['normalized_name'], $r['qty'], $r['rate'], $r['amount'],
                    mb_strtolower((string) $r['supplier']),
                ]));
                $seen[$key] = ($seen[$key] ?? 0) + 1;   // identical rows in same file are kept

                $payload[] = [
                    'import_batch_id' => $batch->id,
                    'purchase_date' => $r['purchase_date'],
                    'date_text' => $r['date_text'],
                    'item_name' => $r['item_name'],
                    'normalized_name' => $r['normalized_name'],
                    'qty' => $r['qty'],
                    'unit' => $r['unit'],
                    'rate' => $r['rate'],
                    'amount' => $r['amount'],
                    'supplier' => $r['supplier'],
                    'department' => $r['department'],
                    'source' => $r['source'],
                    'row_hash' => md5($key.'#'.$seen[$key]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($payload, 50) as $chunk) {
                PurchaseHistory::insertOrIgnore($chunk);   // unique row_hash = skip duplicates
            }

            $added = PurchaseHistory::where('import_batch_id', $batch->id)->count();
            $batch->update(['rows_count' => $added]);

            return ['added' => $added, 'updated' => $updated, 'skipped' => count($rows) - $added];
        });
    }
}
