<?php

namespace Tests\Feature\Services;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ExcelReaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_month_only_excel_dates_are_counted_in_the_matching_dashboard_month(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 12:00:00'));
        $file = UploadedFile::fake()->createWithContent(
            'october.csv',
            "Date,Item,Qty,Rate,Amount,Supplier\nOctober,Paper,1,48000,48000,Vendor\n,Solvent,1,93302,93302,\n,OCTOBER TOTAL,416,339.67,141302,\n",
        );

        $this->post(route('history.import.run'), [
            'file' => $file,
            'mode' => 'append',
        ])->assertRedirect(route('history.index'));

        $this->assertDatabaseHas('purchase_histories', [
            'purchase_date' => '2026-10-01',
            'date_text' => 'October',
            'item_name' => 'Paper',
        ]);
        $this->assertDatabaseHas('purchase_histories', [
            'purchase_date' => '2026-10-01',
            'date_text' => 'October',
            'item_name' => 'Solvent',
        ]);
        $this->assertDatabaseMissing('purchase_histories', ['item_name' => 'OCTOBER TOTAL']);

        $this->get(route('home'))->assertViewHas('monthly', fn ($monthly) => $monthly->contains(
            fn ($bucket) => $bucket['year'] === '2026'
                && $bucket['month'] === 'October'
                && $bucket['lines'] === 2
                && number_format($bucket['total'], 2) === '141,302.00',
        ));
    }
}
