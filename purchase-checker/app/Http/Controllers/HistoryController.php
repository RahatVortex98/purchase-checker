<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Models\PurchaseHistory;
use App\Services\ExcelReader;
use App\Services\HistoryImporter;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HistoryController extends Controller
{
    public function month(string $year, string $month): View
    {
        $yearNumber = (int) $year;
        $monthNumber = (int) $month;
        abort_unless(checkdate($monthNumber, 1, $yearNumber), 404);

        $monthDate = Carbon::create($yearNumber, $monthNumber, 1)->startOfMonth();
        $rows = PurchaseHistory::query()
            ->whereBetween('purchase_date', [
                $monthDate->toDateString(),
                $monthDate->copy()->endOfMonth()->toDateString(),
            ])
            ->orderBy('purchase_date')
            ->orderBy('id')
            ->get();
        $dateGroups = $rows->groupBy(fn (PurchaseHistory $row) => $row->purchase_date->format('Y-m-d'));

        return view('history.month', [
            'month' => $monthDate,
            'rows' => $rows,
            'dateGroups' => $dateGroups,
            'total' => $rows->sum('amount'),
        ]);
    }

    public function index(Request $request)
    {
        $rows = PurchaseHistory::query()
            ->when($request->filled('q'), fn ($x) => $x->where(fn ($w) => $w
                ->where('item_name', 'like', '%'.$request->q.'%')
                ->orWhere('supplier', 'like', '%'.$request->q.'%')))
            ->when($request->filled('department'), fn ($x) => $x->where('department', $request->department))
            ->orderByRaw('purchase_date is null')->orderBy('purchase_date')->orderBy('id')
            ->paginate(50)->withQueryString();

        $groupedRows = $rows->getCollection()
            ->groupBy(fn (PurchaseHistory $row) => ($row->purchase_date?->format('Y-m-d') ?? $row->date_text ?? 'unknown').'|'.($row->supplier ?? 'Unknown supplier'))
            ->filter(fn ($group) => $group->first()?->purchase_date !== null || $group->first()?->date_text !== null);

        return view('history.index', [
            'rows' => $rows,
            'groupedRows' => $groupedRows,
            'departments' => PurchaseHistory::whereNotNull('department')->distinct()->orderBy('department')->pluck('department'),
            'total' => PurchaseHistory::count(),
        ]);
    }

    public function create(Request $request)
    {
        $returnMonth = $this->returnMonth($request);
        $purchaseDate = $request->query('purchase_date');
        if ($purchaseDate !== null) {
            $request->validate(['purchase_date' => ['date_format:Y-m-d']]);
        } elseif ($returnMonth !== null) {
            $purchaseDate = $returnMonth->toDateString();
        }

        $row = new PurchaseHistory;
        $row->purchase_date = $purchaseDate;

        return view('history.form', ['row' => $row, 'returnMonth' => $returnMonth]);
    }

    public function store(Request $request)
    {
        $returnMonth = $this->returnMonth($request);
        PurchaseHistory::create($this->data($request));

        $redirect = $returnMonth
            ? redirect()->route('history.month', ['year' => $returnMonth->year, 'month' => $returnMonth->format('m')])
            : redirect()->route('history.index');

        return $redirect->with('ok', 'Row added.');
    }

    public function edit(Request $request, PurchaseHistory $history)
    {
        return view('history.form', [
            'row' => $history,
            'returnMonth' => $this->returnMonth($request),
        ]);
    }

    public function update(Request $request, PurchaseHistory $history)
    {
        $returnMonth = $this->returnMonth($request);
        $history->update($this->data($request));

        $redirect = $returnMonth
            ? redirect()->route('history.month', ['year' => $returnMonth->year, 'month' => $returnMonth->format('m')])
            : redirect()->route('history.index');

        return $redirect->with('ok', 'Row updated.');
    }

    public function destroy(Request $request, PurchaseHistory $history)
    {
        $returnMonth = $this->returnMonth($request);
        $history->delete();

        $redirect = $returnMonth
            ? redirect()->route('history.month', ['year' => $returnMonth->year, 'month' => $returnMonth->format('m')])
            : redirect()->route('history.index');

        return $redirect->with('ok', 'Row deleted.');
    }

    public function importForm()
    {
        return view('history.import', ['batches' => ImportBatch::latest()->limit(10)->get()]);
    }

    public function sheets(Request $request, ExcelReader $reader): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            $sheets = $reader->sheetNames($request->file('file')->getRealPath());
        } catch (\Throwable $e) {
            Log::error('Could not read uploaded purchase-history worksheets.', [
                'file' => $request->file('file')->getClientOriginalName(),
                'exception' => $e,
            ]);

            return response()->json(['message' => 'Could not read worksheets from this file.'], 422);
        }

        return response()->json(['sheets' => $sheets]);
    }

    public function preview(Request $request, ExcelReader $reader): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'sheet' => 'nullable|string|max:100',
        ]);

        try {
            $rows = $reader->read($request->file('file')->getRealPath(), $request->input('sheet'), true);
        } catch (\Throwable $e) {
            Log::error('Could not preview uploaded purchase-history dates.', [
                'file' => $request->file('file')->getClientOriginalName(),
                'exception' => $e,
            ]);

            return response()->json(['message' => 'Could not preview dates from this file.'], 422);
        }

        return response()->json(['future_dates' => $this->futureDateSummary($rows)]);
    }

    public function destroyImport(ImportBatch $batch): RedirectResponse
    {
        $deletedRows = DB::transaction(function () use ($batch) {
            $deletedRows = PurchaseHistory::where('import_batch_id', $batch->id)->delete();
            $batch->delete();

            return $deletedRows;
        });

        return redirect()->route('history.import')
            ->with('ok', "Deleted import and {$deletedRows} purchase-history rows.");
    }

    public function import(Request $request, ExcelReader $reader, HistoryImporter $importer)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'sheet' => 'nullable|string|max:100',
            'mode' => 'required|in:append,replace',
        ]);

        try {
            $rows = $reader->read($request->file('file')->getRealPath(), $request->input('sheet'), true);
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => $e->getMessage()])->withInput();
        }
        if (! $rows) {
            return back()->withErrors(['file' => 'No item rows found in that sheet.'])->withInput();
        }

        $futureDates = $this->futureDateSummary($rows);
        if ($futureDates !== [] && ! $request->boolean('confirm_future_dates')) {
            return back()->withErrors([
                'future_dates' => 'This sheet contains future-dated purchases. Review the dates and confirm before importing.',
            ])->withInput();
        }

        $r = $importer->import($rows, $request->file('file')->getClientOriginalName(), $request->input('sheet'), $request->mode);

        return redirect()->route('history.index')
            ->with('ok', "Import done: {$r['added']} added, {$r['updated']} previous rows refreshed, {$r['skipped']} duplicates skipped.");
    }

    private function futureDateSummary(array $rows): array
    {
        $today = now()->toDateString();

        return collect($rows)
            ->filter(fn (array $row) => $row['purchase_date'] !== null && $row['purchase_date'] > $today)
            ->groupBy('purchase_date')
            ->map(fn ($dateRows, $date) => ['date' => $date, 'count' => $dateRows->count()])
            ->values()
            ->all();
    }

    private function returnMonth(Request $request): ?Carbon
    {
        $value = $request->input('return_month');
        if ($value === null || $value === '') {
            return null;
        }

        abort_unless(
            is_string($value)
                && preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $value, $matches)
                && checkdate((int) $matches[2], 1, (int) $matches[1]),
            422,
        );

        return Carbon::create((int) $matches[1], (int) $matches[2], 1)->startOfMonth();
    }

    private function data(Request $request): array
    {
        $d = $request->validate([
            'purchase_date' => 'nullable|date',
            'item_name' => 'required|string|max:255',
            'qty' => 'nullable|numeric',
            'unit' => 'nullable|string|max:30',
            'rate' => 'nullable|numeric',
            'amount' => 'nullable|numeric',
            'supplier' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
        ]);
        if (empty($d['amount']) && ! empty($d['qty']) && ! empty($d['rate'])) {
            $d['amount'] = $d['qty'] * $d['rate'];
        }
        $d['date_text'] = ! empty($d['purchase_date']) ? Carbon::parse($d['purchase_date'])->format('d.m.Y') : null;

        return $d;
    }
}
