<?php

namespace Database\Seeders;

use App\Models\ImportBatch;
use App\Models\PurchaseHistory;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PurchaseHistoryDemoSeeder extends Seeder
{
    public function run(): void
    {
        $batch = ImportBatch::firstOrCreate(
            ['file_name' => 'demo-history-jan-oct-2026.csv', 'sheet_name' => 'Demo data'],
            ['rows_count' => 0],
        );

        $purchaseRows = [];
        for ($monthNumber = 1; $monthNumber <= 10; $monthNumber++) {
            $purchaseDays = $monthNumber === 10 ? [2, 4, 6] : [5, 18, 25];
            $paperRate = 350 + ($monthNumber * 5);
            $paperPurchases = [
                ['day' => $purchaseDays[0], 'qty' => 4 + ($monthNumber % 3), 'rate' => $paperRate],
                ['day' => $purchaseDays[1], 'qty' => 2, 'rate' => $paperRate + 12],
            ];

            foreach ($paperPurchases as $purchase) {
                $purchaseRows[] = [
                    'purchase_date' => Carbon::create(2026, $monthNumber, $purchase['day']),
                    'item_name' => 'Demo A4 Paper',
                    'qty' => $purchase['qty'],
                    'unit' => 'ream',
                    'rate' => $purchase['rate'],
                ];
            }

            $purchaseRows[] = [
                'purchase_date' => Carbon::create(2026, $monthNumber, $purchaseDays[2]),
                'item_name' => 'Demo Ink Cartridge',
                'qty' => 1 + ($monthNumber % 2),
                'unit' => 'piece',
                'rate' => 475 + ($monthNumber * 7),
            ];
        }

        foreach ($purchaseRows as $index => $purchase) {
            $purchaseDate = $purchase['purchase_date'];

            PurchaseHistory::updateOrCreate(
                ['row_hash' => md5('purchase-checker-demo-2026-'.$index)],
                [
                    'import_batch_id' => $batch->id,
                    'purchase_date' => $purchaseDate->toDateString(),
                    'date_text' => $purchaseDate->format('d.m.Y'),
                    'item_name' => $purchase['item_name'],
                    'qty' => $purchase['qty'],
                    'unit' => $purchase['unit'],
                    'rate' => $purchase['rate'],
                    'amount' => round($purchase['qty'] * $purchase['rate'], 2),
                    'supplier' => 'Demo Supplier',
                    'department' => 'Testing',
                    'source' => 'DEMO DATA',
                ],
            );
        }

        $batch->update([
            'rows_count' => PurchaseHistory::where('import_batch_id', $batch->id)->count(),
        ]);
    }
}
