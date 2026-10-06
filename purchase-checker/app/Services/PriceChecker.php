<?php

namespace App\Services;

use App\Models\PurchaseHistory;

class PriceChecker
{
    public function check(array $rows, array $additionalHistory = []): array
    {
        $history = PurchaseHistory::orderByRaw('purchase_date is null')
            ->orderByDesc('purchase_date')->orderByDesc('id')
            ->get()
            ->concat(collect($additionalHistory)->map(fn (array $row) => new PurchaseHistory($row)))
            ->sort(fn (PurchaseHistory $a, PurchaseHistory $b) => (
                ($b->purchase_date?->timestamp ?? PHP_INT_MIN) <=> ($a->purchase_date?->timestamp ?? PHP_INT_MIN)
            ) ?: (($b->id ?? 0) <=> ($a->id ?? 0)))
            ->groupBy('normalized_name');

        $names = $history->keys()->all();
        $threshold = (float) config('purchase.similar_threshold', 0.65);
        $results = [];

        foreach ($rows as $row) {
            $key = $row['normalized_name'];
            $status = 'new';
            $past = collect();
            $matchedName = null;
            $score = null;

            if ($history->has($key)) {
                $status = 'found';
                $past = $history[$key];
                $matchedName = $past->first()->item_name;
                $score = 1.0;
            } else {
                $best = null;
                $bestScore = 0.0;
                foreach ($names as $n) {
                    $s = ItemNormalizer::similarity($key, $n);
                    if ($s > $bestScore) {
                        $bestScore = $s;
                        $best = $n;
                    }
                }
                if ($best !== null && $bestScore >= $threshold) {
                    $status = 'similar';
                    $past = $history[$best];
                    $matchedName = $past->first()->item_name;
                    $score = round($bestScore, 2);
                }
            }

            $withRate = $past->filter(fn ($p) => $p->rate !== null && $p->rate > 0);
            $last = $withRate->first();
            $changePct = ($last && $row['rate']) ? round((($row['rate'] - $last->rate) / $last->rate) * 100, 1) : null;
            $depts = $past->pluck('department')->filter()->unique()->values();
            $deptDiffers = $row['department'] && $depts->isNotEmpty()
                && ! $depts->contains(fn ($d) => mb_strtolower($d) === mb_strtolower($row['department']));

            $results[] = [
                'row' => $row,
                'status' => $status,
                'matched_name' => $matchedName,
                'score' => $score,
                'times' => $past->count(),
                'last' => $last ?? $past->first(),
                'min' => $withRate->min('rate'),
                'max' => $withRate->max('rate'),
                'avg' => $withRate->count() ? round($withRate->avg('rate'), 2) : null,
                'change_pct' => $changePct,
                'departments' => $depts,
                'dept_differs' => $deptDiffers,
                'past' => $past->take(5),
            ];
        }

        return $results;
    }
}
