<?php

namespace App\Http\Controllers;

use App\Services\ExcelReader;
use App\Services\HistoryImporter;
use App\Services\PriceChecker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CheckController extends Controller
{
    public function index()
    {
        return view('check.index');
    }

    public function run(Request $request, ExcelReader $reader)
    {
        $request->validate([
            'file' => 'nullable|file|mimes:xlsx,xls,csv',
            'path' => 'nullable|string',
            'sheet' => 'nullable|string|max:100',
            'history_file' => 'nullable|file|mimes:xlsx,xls,csv',
            'history_sheet' => 'nullable|string|max:100',
        ]);

        $usePath = $request->filled('path');
        $path = $usePath ? trim($request->path, " \t\n\r\0\x0B\"") : $request->file('file')?->getRealPath();
        if (! $path || ! is_file($path)) {
            return back()->withErrors(['file' => 'Choose a file, or type a valid file path.'])->withInput();
        }
        $name = $usePath ? basename($path) : $request->file('file')->getClientOriginalName();

        try {
            $rows = $reader->read($path, $request->input('sheet'), true);
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => $e->getMessage()])->withInput();
        }
        if (! $rows) {
            return back()->withErrors(['file' => 'No item rows found in that sheet.'])->withInput();
        }

        $historyRows = [];
        if ($request->hasFile('history_file')) {
            try {
                $historyRows = $reader->read(
                    $request->file('history_file')->getRealPath(),
                    $request->input('history_sheet'),
                    true,
                );
            } catch (\Throwable $e) {
                return back()->withErrors(['history_file' => $e->getMessage()])->withInput();
            }
            if (! $historyRows) {
                return back()->withErrors(['history_file' => 'No item rows found in the historical purchase sheet.'])->withInput();
            }
        }

        $token = Str::random(24);
        Cache::put("check:$token", [
            'file' => $name,
            'sheet' => $request->input('sheet'),
            'rows' => $rows,
            'history_file' => $request->file('history_file')?->getClientOriginalName(),
            'history_rows' => $historyRows,
        ], now()->addDay());

        return redirect()->route('check.show', $token);
    }

    public function show(string $token, PriceChecker $checker)
    {
        $data = $this->load($token);
        $results = $checker->check($data['rows'], $data['history_rows'] ?? []);
        $counts = collect($results)->countBy('status');
        $reportApproved = Cache::get("check:report-approved:$token", false);

        return view('check.show', compact('token', 'data', 'results', 'counts', 'reportApproved'));
    }

    public function approveReport(Request $request, string $token)
    {
        $this->load($token);
        $request->validate([
            'permission' => 'required|accepted',
        ]);

        Cache::put("check:report-approved:$token", true, now()->addDay());

        return redirect()->route('check.show', $token)->with('ok', 'Report approved. You can now print or download it.');
    }

    public function save(string $token, HistoryImporter $importer)
    {
        $data = $this->load($token);
        $r = $importer->import($data['rows'], $data['file'], $data['sheet'], 'append');

        return redirect()->route('history.index')
            ->with('ok', "Saved to history: {$r['added']} added, {$r['skipped']} duplicates skipped.");
    }

    public function export(Request $request, string $token, PriceChecker $checker)
    {
        $data = $this->load($token);
        abort_unless(Cache::get("check:report-approved:$token", false), 403);

        $results = $checker->check($data['rows'], $data['history_rows'] ?? []);
        $onlyNew = $request->query('only') === 'new';
        if ($onlyNew) {
            $results = array_values(array_filter($results, fn ($r) => $r['status'] === 'new'));
        }

        $out = [[
            'SL', 'Item', 'Qty', 'Unit', 'New Rate', 'Department', 'Status', 'Matched Item', 'Times Bought',
            'Last Date', 'Last Qty', 'Last Rate', 'Last Supplier', 'Last Dept', 'Min Rate', 'Max Rate', 'Change %',
        ]];
        foreach ($results as $i => $r) {
            $row = $r['row'];
            $l = $r['last'];
            $out[] = [
                $i + 1, $row['item_name'], $row['qty'], $row['unit'], $row['rate'], $row['department'],
                strtoupper($r['status']), $r['matched_name'], $r['times'],
                $l?->purchase_date?->format('d.m.Y') ?? $l?->date_text, $l?->qty, $l?->rate, $l?->supplier, $l?->department,
                $r['min'], $r['max'], $r['change_pct'],
            ];
        }

        $book = new Spreadsheet;
        $ws = $book->getActiveSheet();
        $ws->fromArray($out, null, 'A1');
        $ws->getStyle('A1:Q1')->getFont()->setBold(true);
        foreach (range('A', 'Q') as $c) {
            $ws->getColumnDimension($c)->setAutoSize(true);
        }

        $name = ($onlyNew ? 'new_items_for_inquiry_' : 'price_check_').now()->format('Ymd_His').'.xlsx';

        return response()->streamDownload(
            fn () => (new Xlsx($book))->save('php://output'),
            $name,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    private function load(string $token): array
    {
        $data = Cache::get("check:$token");
        abort_if(! $data, 404, 'This check expired. Please upload the list again.');

        return $data;
    }
}
