<?php

namespace Tests\Feature;

use App\Models\PurchaseHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryMonthReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withSession(['logged_in' => true]);
    }

    public function test_dashboard_month_link_opens_the_full_printable_month_report(): void
    {
        for ($lineNumber = 1; $lineNumber <= 55; $lineNumber++) {
            PurchaseHistory::create([
                'purchase_date' => '2026-01-10',
                'item_name' => 'January demo purchase '.$lineNumber,
                'qty' => 1,
                'rate' => 10,
                'amount' => 10,
            ]);
        }
        PurchaseHistory::create([
            'purchase_date' => '2026-01-11',
            'item_name' => 'Next day purchase',
            'qty' => 1,
            'rate' => 25,
            'amount' => 25,
        ]);
        PurchaseHistory::create([
            'purchase_date' => '2026-02-01',
            'item_name' => 'February only purchase',
            'qty' => 1,
            'rate' => 20,
            'amount' => 20,
        ]);

        $this->get('/')
            ->assertSee(route('history.month', ['year' => '2026', 'month' => '01']), false);

        $this->get(route('history.month', ['year' => '2026', 'month' => '01']))
            ->assertSee('January 2026 purchases')
            ->assertSee('56 purchase items')
            ->assertSee('January demo purchase 55')
            ->assertSee('10.01.2026')
            ->assertSee('55 items · 550.00 BDT')
            ->assertSee('11.01.2026')
            ->assertSee('1 item · 25.00 BDT')
            ->assertSee('Next day purchase')
            ->assertDontSee('February only purchase')
            ->assertSee('575.00')
            ->assertSee('Print', false)
            ->assertSee('window.print()', false);
    }

    public function test_month_report_returns_not_found_for_an_invalid_month(): void
    {
        $this->get('/history/month/2026/13')->assertNotFound();
    }

    public function test_month_report_can_open_and_save_a_new_entry_for_that_month(): void
    {
        $this->get(route('history.month', ['year' => '2026', 'month' => '01']))
            ->assertOk()
            ->assertSee('New entry');

        $this->get(route('history.create', [
            'purchase_date' => '2026-01-01',
            'return_month' => '2026-01',
        ]))
            ->assertOk()
            ->assertSee('name="purchase_date" class="form-control" value="2026-01-01"', false)
            ->assertSee('name="return_month" value="2026-01"', false);

        $this->post(route('history.store'), [
            'return_month' => '2026-01',
            'purchase_date' => '2026-01-15',
            'item_name' => 'Manually added purchase',
            'qty' => 2,
            'rate' => 15,
            'amount' => 30,
        ])->assertRedirect(route('history.month', ['year' => '2026', 'month' => '01']));

        $purchase = PurchaseHistory::query()
            ->whereDate('purchase_date', '2026-01-15')
            ->where('item_name', 'Manually added purchase')
            ->sole();
        $this->assertSame('15.01.2026', $purchase->date_text);
        $this->assertSame(30.0, $purchase->amount);
    }

    public function test_month_report_can_edit_and_delete_an_entry_and_return_to_the_month(): void
    {
        $purchase = PurchaseHistory::create([
            'purchase_date' => '2026-01-15',
            'item_name' => 'Old item name',
            'qty' => 1,
            'amount' => 10,
        ]);

        $this->get(route('history.month', ['year' => '2026', 'month' => '01']))
            ->assertOk()
            ->assertSee(route('history.edit', ['history' => $purchase, 'return_month' => '2026-01']), false)
            ->assertSee('name="return_month" value="2026-01"', false)
            ->assertSee('Delete this purchase item?', false);

        $this->get(route('history.edit', ['history' => $purchase, 'return_month' => '2026-01']))
            ->assertOk()
            ->assertSee('name="return_month" value="2026-01"', false);

        $this->put(route('history.update', $purchase), [
            'return_month' => '2026-01',
            'purchase_date' => '2026-01-16',
            'item_name' => 'Updated item name',
            'amount' => 20,
        ])->assertRedirect(route('history.month', ['year' => '2026', 'month' => '01']));
        $this->assertDatabaseHas('purchase_histories', ['id' => $purchase->id, 'item_name' => 'Updated item name']);

        $this->delete(route('history.destroy', $purchase), ['return_month' => '2026-01'])
            ->assertRedirect(route('history.month', ['year' => '2026', 'month' => '01']));
        $this->assertDatabaseMissing('purchase_histories', ['id' => $purchase->id]);
    }

    public function test_dashboard_lists_every_supplier_not_only_the_top_six(): void
    {
        for ($supplierNumber = 1; $supplierNumber <= 7; $supplierNumber++) {
            PurchaseHistory::create([
                'purchase_date' => '2026-01-10',
                'item_name' => 'Purchase item '.$supplierNumber,
                'supplier' => 'Supplier '.$supplierNumber,
                'amount' => 100 - $supplierNumber,
            ]);
        }

        $response = $this->get(route('home'))->assertOk();

        $response->assertSee('Purchase overview')
            ->assertSee('Spend by department')
            ->assertSee('Monthly purchase cost');
        for ($supplierNumber = 1; $supplierNumber <= 7; $supplierNumber++) {
            $response->assertSee('Supplier '.$supplierNumber);
        }
        $response->assertSee('All suppliers');
    }
}
