<?php

namespace App\Console\Commands;

use App\Models\PurchaseHistory;
use Illuminate\Console\Command;

class RenormalizeHistory extends Command
{
    protected $signature = 'history:renormalize';
    protected $description = 'Recompute item matching keys after editing config/purchase.php';

    public function handle(): int
    {
        PurchaseHistory::chunkById(200, fn ($rows) => $rows->each->save());
        $this->info('Done.');
        return self::SUCCESS;
    }
}