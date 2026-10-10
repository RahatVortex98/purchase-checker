<?php

use App\Http\Controllers\CheckController;
use App\Http\Controllers\HistoryController;
use App\Http\Middleware\SimpleAuth;
use App\Models\ImportBatch;
use App\Models\PurchaseHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

Route::middleware(SimpleAuth::class)->group(function () {
    Route::get('/', function () {
        $all = PurchaseHistory::get(['purchase_date', 'amount']);

        $monthly = $all
            ->groupBy(fn ($r) => $r->purchase_date?->format('Y-m') ?? 'unknown')
            ->reject(fn ($g, $k) => $k === 'unknown')
            ->map(fn ($g, $k) => [
                'key' => $k,
                'year' => substr($k, 0, 4),
                'month' => Carbon::createFromFormat('Y-m-d', $k.'-01')->format('F'),
                'lines' => $g->count(),
                'total' => $g->sum('amount'),
            ])
            ->sortKeys()
            ->values();

        $yearTotals = $monthly->whereNotNull('year')->groupBy('year')->map(fn ($g) => $g->sum('total'));

        $byDept = PurchaseHistory::selectRaw('department, SUM(amount) as total')
            ->whereNotNull('department')->groupBy('department')->orderByDesc('total')->limit(8)->get();
        $topSuppliers = PurchaseHistory::selectRaw('supplier, COUNT(*) as lines, SUM(amount) as total')
            ->whereNotNull('supplier')->whereRaw("TRIM(supplier) <> ''")
            ->groupBy('supplier')->orderByDesc('total')->get();

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
    Route::post('/check/{token}/report/approve', [CheckController::class, 'approveReport'])->name('check.report.approve');
    Route::get('/check/{token}/export', [CheckController::class, 'export'])->name('check.export');

    Route::get('/history/import', [HistoryController::class, 'importForm'])->name('history.import');
    Route::post('/history/import/sheets', [HistoryController::class, 'sheets'])->name('history.import.sheets');
    Route::post('/history/import/preview', [HistoryController::class, 'preview'])->name('history.import.preview');
    Route::post('/history/import', [HistoryController::class, 'import'])->name('history.import.run');
    Route::delete('/history/import/{batch}', [HistoryController::class, 'destroyImport'])->name('history.import.destroy');
    Route::get('/history/month/{year}/{month}', [HistoryController::class, 'month'])
        ->where(['year' => '[0-9]{4}', 'month' => '0[1-9]|1[0-2]'])
        ->name('history.month');
    Route::resource('history', HistoryController::class)->except(['show']);

    Route::get('/diag', function () {
        $uploadTempDir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
        $tempFile = tempnam($uploadTempDir, 'upload-check-');
        if ($tempFile !== false) {
            unlink($tempFile);
        }

        return [
            'php_bin' => PHP_BINARY,
            'ini_file' => php_ini_loaded_file(),
            'sapi' => php_sapi_name(),
            'upload_tmp_dir' => ini_get('upload_tmp_dir'),
            'sys_temp' => sys_get_temp_dir(),
            'tmp_exists' => is_dir($uploadTempDir),
            'tmp_writable' => is_writable($uploadTempDir),
            'upload_temp_file_test' => $tempFile !== false,
            'upload_max' => ini_get('upload_max_filesize'),
            'post_max' => ini_get('post_max_size'),
        ];
    });
});

Route::get('/login', function () {
    if (session('logged_in')) {
        return redirect()->route('home');
    }

    return view('login');
})->name('login');
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['nullable', 'email', 'max:255'],
        'password' => ['required', 'string', 'max:255'],
    ]);
    $email = strtolower(trim($credentials['email'] ?? ''));
    $director = config('purchase.managing_director');

    if ($email !== '' && hash_equals(strtolower($director['email']), $email)) {
        $authenticated = is_string($director['password_hash'])
            && Hash::check($credentials['password'], $director['password_hash']);
        $role = 'managing_director';
    } elseif ($email === '') {
        $adminPassword = config('purchase.password');
        $authenticated = is_string($adminPassword)
            && hash_equals($adminPassword, $credentials['password']);
        $role = 'super_admin';
    } else {
        $authenticated = false;
        $role = null;
    }

    if (! $authenticated) {
        return back()->withErrors(['credentials' => 'The provided credentials are invalid.'])->onlyInput('email');
    }

    $request->session()->regenerate();
    $request->session()->put(['logged_in' => true, 'user_role' => $role]);

    return redirect()->route('home');
})->middleware('throttle:5,1')->name('login.submit');
Route::post('/logout', function (Request $request) {
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware(SimpleAuth::class)->name('logout');
