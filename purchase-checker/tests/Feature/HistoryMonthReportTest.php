<?php

namespace Tests\Feature;

use App\Models\PurchaseHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryMonthReportTest extends TestCase
{
    use RefreshDatabase;

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
            ->assertSee('55 purchase lines')
            ->assertSee('January demo purchase 55')
            ->assertDontSee('February only purchase')
            ->assertSee('550.00')
            ->assertSee('Print', false)
            ->assertSee('window.print()', false);
    }

    public function test_month_report_returns_not_found_for_an_invalid_month(): void
    {
        $this->get('/history/month/2026/13')->assertNotFound();
    }
}
