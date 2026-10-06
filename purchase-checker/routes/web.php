<?php

use App\Http\Controllers\CheckController;
use App\Http\Controllers\HistoryController;
use App\Http\Middleware\SimpleAuth;
use App\Models\ImportBatch;
use App\Models\PurchaseHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $all = PurchaseHistory::get(['purchase_date', 'amount']);

    $monthly = $all
        ->groupBy(fn ($r) => $r->purchase_date?->format('Y-m') ?? 'unknown')
        ->map(fn ($g, $k) => [
            'key' => $k,
            'year' => $k === 'unknown' ? null : substr($k, 0, 4),
            'month' => $k === 'unknown' ? 'Date unknown' : Carbon::createFromFormat('Y-m-d', $k.'-01')->format('F'),
            'lines' => $g->count(),
            'total' => $g->sum('amount'),
        ])
        ->sortKeys()
        ->values();

    $yearTotals = $monthly->whereNotNull('year')->groupBy('year')->map(fn ($g) => $g->sum('total'));

    $byDept = PurchaseHistory::selectRaw('department, SUM(amount) as total')
        ->whereNotNull('department')->groupBy('department')->orderByDesc('total')->limit(8)->get();
    $topSuppliers = PurchaseHistory::selectRaw('supplier, COUNT(*) as lines, SUM(amount) as total')
        ->whereNotNull('supplier')->groupBy('supplier')->orderByDesc('total')->limit(6)->get();

    return view('dashboard', [
        'rows' => PurchaseHistory::count(),
        'suppliers' => PurchaseHistory::whereNotNull('supplier')->distinct('supplier')->count('supplier'),
        'last' => ImportBatch::latest()->first(),
        'byDept' => $byDept,
        'topSuppliers' => $topSuppliers,
        'monthly' => $monthly,
        'yearTotals' => $yearTotals,
        'grandTotal' => $all->sum('amount'),
    ]);
})->name('home');

Route::get('/check', [CheckController::class, 'index'])->name('check.index');
Route::post('/check', [CheckController::class, 'run'])->name('check.run');
Route::get('/check/{token}', [CheckController::class, 'show'])->name('check.show');
Route::post('/check/{token}/save', [CheckController::class, 'save'])->name('check.save');
Route::get('/check/{token}/export', [CheckController::class, 'export'])->name('check.export');

Route::get('/history/import', [HistoryController::class, 'importForm'])->name('history.import');
Route::post('/history/import/sheets', [HistoryController::class, 'sheets'])->name('history.import.sheets');
Route::post('/history/import', [HistoryController::class, 'import'])->name('history.import.run');
Route::delete('/history/import/{batch}', [HistoryController::class, 'destroyImport'])->name('history.import.destroy');
Route::get('/history/month/{year}/{month}', [HistoryController::class, 'month'])
    ->where(['year' => '[0-9]{4}', 'month' => '0[1-9]|1[0-2]'])
    ->name('history.month');
Route::resource('history', HistoryController::class)->except(['show']);

Route::get('/diag', fn () => [
    'php_bin' => PHP_BINARY,
    'ini_file' => php_ini_loaded_file(),
    'sapi' => php_sapi_name(),
    'upload_tmp_dir' => ini_get('upload_tmp_dir'),
    'sys_temp' => sys_get_temp_dir(),
    'tmp_exists' => is_dir('C:\\tmp'),
    'tmp_writable' => is_writable('C:\\tmp'),
    'upload_max' => ini_get('upload_max_filesize'),
]);

Route::get('/login', fn () => view('login'))->name('login');
Route::post('/login', function (Request $r) {
    if (config('purchase.password') && hash_equals(config('purchase.password'), (string) $r->password)) {
        session(['logged_in' => true]);

        return redirect()->route('home');
    }

    return back()->withErrors(['password' => 'Wrong password']);
});
Route::post('/logout', function () {
    session()->flush();

    return redirect()->route('login');
})->name('logout');

Route::middleware(SimpleAuth::class)->group(function () {
    // ...paste ALL your other routes here (home, check, history)...
});
