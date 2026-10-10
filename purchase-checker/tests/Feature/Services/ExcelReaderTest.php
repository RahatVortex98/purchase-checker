<?php

namespace Tests\Feature\Services;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ExcelReaderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withSession(['logged_in' => true]);
    }

    public function test_currency_columns_and_formatted_amounts_are_read_as_numbers(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'purchases.csv',
            "Date,Item,Qty,Rate (Tk.),Total (Tk.),Department\n01.10.2026,Paper,2,\"Tk. 1,250.50\",\"৳2,501.00\",Production\n",
        );

        $this->post(route('history.import.run'), [
            'file' => $file,
            'mode' => 'append',
        ])->assertRedirect(route('history.index'));

        $this->assertDatabaseHas('purchase_histories', [
            'item_name' => 'Paper',
            'rate' => 1250.5,
            'amount' => 2501,
            'department' => 'Production',
        ]);

        $this->get(route('home'))
            ->assertViewHas('monthly', fn ($monthly) => $monthly->contains(
                fn ($bucket) => $bucket['month'] === 'October' && $bucket['total'] === 2501.0,
            ))
            ->assertViewHas('byDept', fn ($departments) => $departments->contains(
                fn ($department) => $department->department === 'Production' && (float) $department->total === 2501.0,
            ));
    }

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

    public function test_future_purchase_month_is_kept_when_importing_history(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 12:00:00'));
        $contents = "Date,Item,Qty,Rate,Amount,Supplier\nJanuary 2027,Paper,1,10,10,Vendor\n05.01.2027,Ink,2,5,10,Vendor\n";

        $this->postJson(route('history.import.preview'), [
            'file' => UploadedFile::fake()->createWithContent('january-2027.csv', $contents),
        ])->assertOk()
            ->assertJsonPath('future_dates.0.date', '2027-01-01')
            ->assertJsonPath('future_dates.0.count', 1)
            ->assertJsonPath('future_dates.1.date', '2027-01-05')
            ->assertJsonPath('future_dates.1.count', 1);

        $this->post(route('history.import.run'), [
            'file' => UploadedFile::fake()->createWithContent('january-2027.csv', $contents),
            'mode' => 'append',
        ])->assertSessionHasErrors('future_dates');
        $this->assertDatabaseCount('purchase_histories', 0);
        $this->assertDatabaseCount('import_batches', 0);

        $this->post(route('history.import.run'), [
            'file' => UploadedFile::fake()->createWithContent('january-2027.csv', $contents),
            'mode' => 'append',
            'confirm_future_dates' => '1',
        ])->assertRedirect(route('history.index'));

        $this->assertDatabaseHas('purchase_histories', [
            'purchase_date' => '2027-01-01',
            'date_text' => 'January 2027',
            'item_name' => 'Paper',
        ]);
        $this->assertDatabaseHas('purchase_histories', [
            'purchase_date' => '2027-01-05',
            'item_name' => 'Ink',
        ]);

        $this->get(route('history.index'))
            ->assertSee('January 2027')
            ->assertDontSee('Date unknown');
    }
}
