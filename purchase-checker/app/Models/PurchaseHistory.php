<?php

namespace App\Models;

use App\Services\ItemNormalizer;
use Illuminate\Database\Eloquent\Model;

class PurchaseHistory extends Model
{
    protected $table = 'purchase_histories';
    protected $guarded = [];
    protected $casts = [
        'purchase_date' => 'date',
        'qty' => 'float',
        'rate' => 'float',
        'amount' => 'float',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $m) {
            $m->normalized_name = ItemNormalizer::normalize($m->item_name);
            if (!$m->row_hash) {
                $m->row_hash = md5(uniqid('', true));
            }
        });
    }
}