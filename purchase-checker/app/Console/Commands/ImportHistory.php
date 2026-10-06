<?php

namespace App\Console\Commands;

use App\Services\ExcelReader;
use App\Services\HistoryImporter;
use Illuminate\Console\Command;

class ImportHistory extends Command
{
    protected $signature = 'history:import {path} {--sheet=} {--replace}';
    protected $description = 'Import purchase history from an Excel file on disk';

    public function handle(ExcelReader $reader, HistoryImporter $importer): int
    {
        $path = $this->argument('path');
        if (!is_file($path)) {
            $this->error("File not found: $path");
            return self::FAILURE;
        }
        $sheet = $this->option('sheet') ?: null;
        $rows = $reader->read($path, $sheet, false);
        $r = $importer->import($rows, basename($path), $sheet, $this->option('replace') ? 'replace' : 'append');
        $this->info("Added {$r['added']}, skipped {$r['skipped']} duplicates.");
        return self::SUCCESS;
    }
}