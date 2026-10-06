<?php

namespace Tests\Feature;

use App\Models\PurchaseHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class HistoryImportRefreshTest extends TestCase
{
    use RefreshDatabase;

    public function test_reimporting_a_file_refreshes_included_dates_and_keeps_other_dates(): void
    {
        $this->travelTo('2026-10-10 12:00:00');

        $this->post(route('history.import.run'), [
            'file' => UploadedFile::fake()->createWithContent(
                'october.csv',
                "Date,Item,Qty,Rate,Amount,Supplier\n02.10.2026,Paper,2,10,20,Vendor\n03.10.2026,Ink,1,5,5,Vendor\n",
            ),
            'mode' => 'append',
        ])->assertRedirect(route('history.index'));

        $this->post(route('history.import.run'), [
            'file' => UploadedFile::fake()->createWithContent(
                'october.csv',
                "Date,Item,Qty,Rate,Amount,Supplier\n02.10.2026,Paper,5,12,60,Vendor\n",
            ),
            'mode' => 'append',
        ])->assertRedirect(route('history.index'))
            ->assertSessionHas('ok', 'Import done: 1 added, 1 previous rows refreshed, 0 duplicates skipped.');

        $paper = PurchaseHistory::query()
            ->whereDate('purchase_date', '2026-10-02')
            ->where('item_name', 'Paper')
            ->sole();

        $this->assertSame(5.0, $paper->qty);
        $this->assertSame(12.0, $paper->rate);
        $this->assertDatabaseHas('purchase_histories', [
            'purchase_date' => '2026-10-03',
            'item_name' => 'Ink',
        ]);
        $this->assertDatabaseCount('purchase_histories', 2);
    }

    public function test_history_rows_are_separated_by_month_and_year_headings(): void
    {
        PurchaseHistory::create([
            'purchase_date' => '2026-01-04',
            'item_name' => 'Paper',
            'qty' => 2,
            'rate' => 10,
            'amount' => 20,
        ]);
        PurchaseHistory::create([
            'purchase_date' => '2026-02-01',
            'item_name' => 'Ink',
            'qty' => 1,
            'rate' => 5,
            'amount' => 5,
        ]);

        $this->get(route('history.index'))
            ->assertSeeInOrder(['January 2026', 'February 2026']);
    }

    public function test_repeat_purchases_from_different_imports_are_kept_and_shown_in_checks(): void
    {
        $this->travelTo('2026-10-10 12:00:00');

        foreach ([
            ['august.csv', '01.08.2026'],
            ['september.csv', '01.09.2026'],
            ['september-copy.csv', '01.09.2026'],
        ] as [$fileName, $date]) {
            $this->post(route('history.import.run'), [
                'file' => UploadedFile::fake()->createWithContent(
                    $fileName,
                    "Date,Item,Qty,Rate,Amount,Supplier\n{$date},Paper,2,10,20,Vendor\n",
                ),
                'mode' => 'append',
            ])->assertRedirect(route('history.index'));
        }

        $this->assertDatabaseCount('purchase_histories', 3);

        $response = $this->post(route('check.run'), [
            'file' => UploadedFile::fake()->createWithContent(
                'requisition.csv',
                "Date,Item,Qty,Rate,Amount\n,Paper,1,12,12\n",
            ),
        ])->assertRedirect();

        $token = basename(parse_url($response->headers->get('Location'), PHP_URL_PATH));

        $this->get(route('check.show', $token))
            ->assertOk()
            ->assertSee('Bought before ×3')
            ->assertSee('01.08.2026')
            ->assertSee('01.09.2026');
    }
}
