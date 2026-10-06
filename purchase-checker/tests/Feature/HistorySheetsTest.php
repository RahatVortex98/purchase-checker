<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class HistorySheetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_available_worksheets_for_an_uploaded_file(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'purchases.csv',
            "Date,Item,Qty\n2026-10-01,Paper,1\n",
        );

        $response = $this->post(route('history.import.sheets'), ['file' => $file])
            ->assertOk()
            ->assertJsonStructure(['sheets']);

        $this->assertNotEmpty($response->json('sheets'));
    }

    public function test_it_detects_worksheets_in_an_uploaded_xlsx_file(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'purchase-checker-');
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->setTitle('Purchases');
        $spreadsheet->getActiveSheet()->fromArray([
            ['Date', 'Item', 'Qty'],
            ['2026-10-01', 'Paper', 1],
        ]);
        (new Xlsx($spreadsheet))->save($path);

        try {
            $file = UploadedFile::fake()->createWithContent('purchases.xlsx', file_get_contents($path));
        } finally {
            unlink($path);
        }

        $this->post(route('history.import.sheets'), ['file' => $file])
            ->assertOk()
            ->assertJsonPath('sheets.0', 'Purchases');
    }

    public function test_import_form_contains_the_worksheet_picker(): void
    {
        $this->get(route('history.import'))
            ->assertOk()
            ->assertSee('id="worksheet-picker"', false)
            ->assertSee(route('history.import.sheets'), false);
    }
}
