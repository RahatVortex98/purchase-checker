<?php

namespace App\Services;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlDate;

class ExcelReader
{
    private const ALIASES = [
        'date'       => ['date', 'purchase date'],
        'item'       => ['item / description', 'item', 'product name', 'product', 'description', 'item name'],
        'qty'        => ['qty', 'quantity'],
        'unit'       => ['unit'],
        'rate'       => ['rate (bdt)', 'rate', 'unit price', 'unit price (bdt)', 'unit price / rate (bdt)'],
        'amount'     => ['amount (bdt)', 'amount', 'total amount (bdt)', 'total amount'],
        'supplier'   => ['supplier', 'supplier / party name', 'party name', 'supplier name'],
        'department' => ['department', 'department / source', 'dept'],
    ];

    /** $forwardFill = true for new lists where merged cells leave date/supplier/dept blank */
    public function read(string $path, ?string $sheet = null, bool $forwardFill = false): array
    {
        $book = IOFactory::load($path);
        $ws = $sheet ? $book->getSheetByName($sheet) : $book->getSheet(0);
        if (!$ws) {
            throw new \RuntimeException("Sheet \"$sheet\" not found. Available sheets: " . implode(', ', $book->getSheetNames()));
        }
        $grid = $ws->toArray(null, true, false, false);

        [$headerRow, $map] = $this->findHeader($grid);
        if ($headerRow === null) {
            throw new \RuntimeException('Header row not found. Sheet needs an "Item / Product Name" column plus Qty/Rate/etc.');
        }

        $rows = [];
        $last = ['date' => null, 'supplier' => null, 'dept' => null];

        for ($i = $headerRow + 1; $i < count($grid); $i++) {
            $g = $grid[$i];
            $get = fn (string $f) => isset($map[$f]) ? ($g[$map[$f]] ?? null) : null;

            $item = trim((string) $get('item'));
            if ($item === '') {
                continue;
            }
            $qty = $this->num($get('qty'));
            $rate = $this->num($get('rate'));
            $amount = $this->num($get('amount'));
            if ($qty === null && $rate === null && preg_match('/\btotal\b/i', $item)) {
                continue; // "OCTOBER TOTAL" etc.
            }

            $dateRaw = $get('date');
            $supplier = trim((string) $get('supplier'));
            $dept = trim((string) $get('department'));

            if ($forwardFill) {
                $isContinuation = ($dateRaw === null || $dateRaw === '') && $supplier === '';
                if ($isContinuation) {
                    $dateRaw = $last['date'];
                    $supplier = (string) $last['supplier'];
                    $dept = $dept !== '' ? $dept : (string) $last['dept'];
                } else {
                    $last = ['date' => $dateRaw, 'supplier' => $supplier, 'dept' => $dept];
                }
            }

            if ($rate === null && $qty && $amount) {
                $rate = round($amount / $qty, 2);
            }
            if ($amount === null && $qty !== null && $rate !== null) {
                $amount = round($qty * $rate, 2);
            }

            [$date, $dateText] = $this->parseDate($dateRaw);

            $rows[] = [
                'row_no'          => $i + 1,
                'purchase_date'   => $date?->toDateString(),
                'date_text'       => $dateText,
                'item_name'       => $item,
                'normalized_name' => ItemNormalizer::normalize($item),
                'qty'             => $qty,
                'unit'            => trim((string) $get('unit')) ?: null,
                'rate'            => $rate,
                'amount'          => $amount,
                'supplier'        => $supplier ?: null,
                'department'      => $this->cleanDepartment($dept),
                'source'          => $dept ?: null,
            ];
        }

        return $rows;
    }

    private function findHeader(array $grid): array
    {
        foreach (array_slice($grid, 0, 30, true) as $i => $row) {
            $map = [];
            foreach ($row as $c => $cell) {
                $h = mb_strtolower(preg_replace('/\s+/u', ' ', trim((string) $cell)));
                if ($h === '') {
                    continue;
                }
                foreach (self::ALIASES as $field => $names) {
                    if (!isset($map[$field]) && in_array($h, $names, true)) {
                        $map[$field] = $c;
                        break;
                    }
                }
            }
            if (isset($map['item']) && count($map) >= 3) {
                return [$i, $map];
            }
        }
        return [null, []];
    }

    private function num($v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v)) {
            return (float) $v;
        }
        $v = str_replace(',', '', (string) $v);
        return is_numeric($v) ? (float) $v : null;
    }

    /** Handles "22.02.2026", "08-09-2026", ranges, real Excel dates. Typos (year 2027+, 20206) -> date null, text kept. */
    private function parseDate($v): array
    {
        if ($v === null || $v === '') {
            return [null, null];
        }
        if (is_numeric($v)) {
            $dt = Carbon::instance(XlDate::excelToDateTimeObject($v))->startOfDay();
            return [$dt, $dt->format('d.m.Y')];
        }
        $text = trim((string) $v);
        if (preg_match('/(\d{1,2})[.\-\/](\d{1,2})[.\-\/](\d{4})(?!\d)/', $text, $m)) {
            [$d, $mo, $y] = [(int) $m[1], (int) $m[2], (int) $m[3]];
            if (checkdate($mo, $d, $y) && $y <= (int) now()->year) {
                return [Carbon::create($y, $mo, $d)->startOfDay(), $text];
            }
        }
        return [null, $text];
    }

    /** "July 2026 (Production)" -> "Production"; "September" -> null; "Q/C" -> "Q/C" */
    private function cleanDepartment(?string $v): ?string
    {
        $v = trim((string) $v);
        if ($v === '') {
            return null;
        }
        $months = 'january|february|march|april|may|june|july|august|september|october|november|december';
        if (preg_match("/^($months)(\s+\d{4})?\s*(?:\((.+)\))?\s*$/iu", $v, $m)) {
            return isset($m[3]) && trim($m[3]) !== '' ? trim($m[3]) : null;
        }
        return $v;
    }
}