<?php

use App\Http\Controllers\CheckController;
use App\Http\Controllers\HistoryController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/check');

Route::get('/check', [CheckController::class, 'index'])->name('check.index');
Route::post('/check', [CheckController::class, 'run'])->name('check.run');
Route::get('/check/{token}', [CheckController::class, 'show'])->name('check.show');
Route::post('/check/{token}/save', [CheckController::class, 'save'])->name('check.save');
Route::get('/check/{token}/export', [CheckController::class, 'export'])->name('check.export');

Route::get('/history/import', [HistoryController::class, 'importForm'])->name('history.import');
Route::post('/history/import', [HistoryController::class, 'import'])->name('history.import.run');
Route::resource('history', HistoryController::class)->except(['show']);


Route::get('/diag', fn () => [
    'php_bin'  => PHP_BINARY,
    'ini_file' => php_ini_loaded_file(),
    'sapi'     => php_sapi_name(),
    'upload_tmp_dir' => ini_get('upload_tmp_dir'),
    'sys_temp'       => sys_get_temp_dir(),
    'tmp_exists'     => is_dir('C:\\tmp'),
    'tmp_writable'   => is_writable('C:\\tmp'),
    'upload_max'     => ini_get('upload_max_filesize'),
]);