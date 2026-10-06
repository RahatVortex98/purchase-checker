<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\PurchaseHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryImportDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_an_import_removes_its_rows_and_keeps_other_imports(): void
    {
        $deletedBatch = ImportBatch::create([
            'file_name' => 'old.xlsx',
            'sheet_name' => 'October',
            'rows_count' => 1,
        ]);
        $keptBatch = ImportBatch::create([
            'file_name' => 'keep.xlsx',
            'sheet_name' => 'November',
            'rows_count' => 1,
        ]);
        $deletedRow = PurchaseHistory::create([
            'import_batch_id' => $deletedBatch->id,
            'purchase_date' => '2026-10-01',
            'item_name' => 'Paper',
            'amount' => 10,
            'row_hash' => str_repeat('a', 32),
        ]);
        $keptRow = PurchaseHistory::create([
            'import_batch_id' => $keptBatch->id,
            'purchase_date' => '2026-11-01',
            'item_name' => 'Ink',
            'amount' => 20,
            'row_hash' => str_repeat('b', 32),
        ]);

        $this->delete(route('history.import.destroy', $deletedBatch))
            ->assertRedirect(route('history.import'));

        $this->assertDatabaseMissing('import_batches', ['id' => $deletedBatch->id]);
        $this->assertDatabaseMissing('purchase_histories', ['id' => $deletedRow->id]);
        $this->assertDatabaseHas('import_batches', ['id' => $keptBatch->id]);
        $this->assertDatabaseHas('purchase_histories', ['id' => $keptRow->id]);
    }

    public function test_recent_imports_show_a_delete_action(): void
    {
        $batch = ImportBatch::create([
            'file_name' => 'old.xlsx',
            'sheet_name' => 'October',
            'rows_count' => 1,
        ]);

        $this->get(route('history.import'))
            ->assertSee(route('history.import.destroy', $batch), false)
            ->assertSee('Delete this import and its purchase-history rows?', false);
    }
}
