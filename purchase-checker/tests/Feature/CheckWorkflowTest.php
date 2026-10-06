<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CheckWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_history_is_used_for_a_check_without_being_saved(): void
    {
        $response = $this->post(route('check.run'), [
            'file' => UploadedFile::fake()->createWithContent(
                'requisition.csv',
                "Date,Item,Qty,Rate,Amount\n,Paper stock,5,10,50\n,Unrelated xylophone,1,20,20\n",
            ),
            'history_file' => UploadedFile::fake()->createWithContent(
                'old-purchases.csv',
                "Date,Item,Qty,Rate,Amount,Supplier\n01.03.2024,Paper stock,2,8,16,Office vendor\n",
            ),
        ])->assertRedirect();

        $token = basename(parse_url($response->headers->get('Location'), PHP_URL_PATH));

        $this->get(route('check.show', $token))
            ->assertOk()
            ->assertSee('Bought before')
            ->assertSee('01.03.2024')
            ->assertSee('NEW ITEM')
            ->assertSee('old-purchases.csv')
            ->assertDontSee('Download full report');

        $this->assertDatabaseCount('purchase_histories', 0);
    }

    public function test_new_items_are_marked_automatically_and_report_requires_permission(): void
    {
        $response = $this->post(route('check.run'), [
            'file' => UploadedFile::fake()->createWithContent(
                'requisition.csv',
                "Date,Item,Qty,Rate,Amount\n,Unrelated xylophone,1,20,20\n",
            ),
        ])->assertRedirect();

        $token = basename(parse_url($response->headers->get('Location'), PHP_URL_PATH));

        $this->get(route('check.show', $token))
            ->assertOk()
            ->assertSee('NEW ITEM')
            ->assertSee('I have permission to generate this report')
            ->assertDontSee('Print report');

        $this->get(route('check.export', $token))->assertForbidden();
        $this->post(route('check.report.approve', $token))->assertSessionHasErrors('permission');

        $this->post(route('check.report.approve', $token), ['permission' => '1'])
            ->assertRedirect(route('check.show', $token));

        $this->get(route('check.show', $token))
            ->assertOk()
            ->assertSee('Print report')
            ->assertSee('window.print()', false);
        $this->get(route('check.export', $token))->assertOk();
    }
}
