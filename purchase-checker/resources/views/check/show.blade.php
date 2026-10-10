@extends('layouts.app')
@section('content')
@php $n = fn($v) => $v === null ? '' : number_format($v, 2); @endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h5 class="mb-0">{{ $data['file'] }} @if($data['sheet'])<small class="text-muted">/ {{ $data['sheet'] }}</small>@endif</h5>
    @if($reportApproved)
        <div class="d-flex gap-2 report-actions">
            <button class="btn btn-outline-primary btn-sm" type="button" onclick="window.print()">Print report</button>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('check.export', $token) }}">Download full report</a>
            <a class="btn btn-outline-danger btn-sm" href="{{ route('check.export', [$token, 'only' => 'new']) }}">Download NEW items</a>
        </div>
    @endif
</div>

@if($data['history_file'])
    <div class="alert alert-info py-2">Comparing with {{ $data['history_file'] }} (this file is not saved to purchase history).</div>
@endif

@if($reportApproved)
    <div class="alert alert-success report-actions">Report generation approved. Use Print report or download the Excel report.</div>
@else
    <div class="card mb-3 report-actions">
        <div class="card-body">
            <h6>Results ready</h6>
            <p class="mb-2">Review the matches below. A printable/downloadable report is created only after you confirm that you have permission.</p>
            <form method="POST" action="{{ route('check.report.approve', $token) }}" class="d-flex flex-wrap align-items-center gap-3">
                @csrf
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="permission" value="1" id="report-permission" required>
                    <label class="form-check-label" for="report-permission">I have permission to generate this report</label>
                </div>
                <button class="btn btn-primary btn-sm">Generate report</button>
            </form>
        </div>
    </div>
@endif

<div class="report-actions mb-3">
    <form method="POST" action="{{ route('check.save', $token) }}" onsubmit="return confirm('Add all items of this list to purchase history?')">
        @csrf <button class="btn btn-success btn-sm">Save this list to history</button>
    </form>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3"><div class="card stat"><div class="text-muted small">Total items</div><div class="n">{{ count($results) }}</div></div></div>
    <div class="col-6 col-md-3"><div class="card stat"><div class="text-success small">Bought before</div><div class="n text-success">{{ $counts['found'] ?? 0 }}</div></div></div>
    <div class="col-6 col-md-3"><div class="card stat"><div class="text-warning small">Similar</div><div class="n text-warning">{{ $counts['similar'] ?? 0 }}</div></div></div>
    <div class="col-6 col-md-3"><div class="card stat"><div class="text-danger small">New (needs inquiry)</div><div class="n text-danger">{{ $counts['new'] ?? 0 }}</div></div></div>
</div>

<table class="table table-sm table-bordered bg-white align-middle">
<thead class="table-light">
<tr><th>#</th><th>Item (new list)</th><th>Qty</th><th>New rate</th><th>Dept</th><th>Status</th><th>Last purchase</th><th>Price range (avg)</th><th>Rate change</th></tr>
</thead>
<tbody>
@foreach($results as $i => $r)
    @php $row = $r['row']; $l = $r['last']; @endphp
    <tr data-status="{{ $r['status'] }}">
        <td>{{ $i + 1 }}</td>
        <td>{{ $row['item_name'] }}<div class="small text-muted">{{ $row['supplier'] }}</div></td>
        <td>{{ $row['qty'] !== null ? $row['qty'] + 0 : '' }} {{ $row['unit'] }}</td>
        <td>{{ $n($row['rate']) }}</td>
        <td>{{ $row['department'] }}</td>
        <td>
            @if($r['status'] === 'found')
                <span class="badge bg-success">Bought before ×{{ $r['times'] }}</span>
            @elseif($r['status'] === 'similar')
                <span class="badge bg-warning text-dark">Similar {{ round($r['score'] * 100) }}%</span>
                <div class="small">{{ $r['matched_name'] }}</div>
            @else
                <span class="badge bg-danger">NEW ITEM</span>
                <div class="small text-danger">Needs price inquiry</div>
            @endif
            @if($r['dept_differs'])
                <div class="small text-primary">Dept differs (before: {{ $r['departments']->implode(', ') }})</div>
            @endif
        </td>
        <td style="min-width:320px">
            @if($l)
                {{ $l->purchase_date?->format('d.m.Y') ?? $l->date_text }} ·
                {{ $l->qty !== null ? $l->qty + 0 : '-' }} {{ $l->unit }} ·
                <b>{{ $n($l->rate) ?: '-' }}</b> · {{ $l->supplier }} · {{ $l->department }}
                <details class="small mt-1"><summary>Last {{ $r['past']->count() }} purchases</summary>
                    <table class="table table-sm mb-0">
                        @foreach($r['past'] as $p)
                            <tr>
                                <td>{{ $p->purchase_date?->format('d.m.Y') ?? $p->date_text }}</td>
                                <td>{{ $p->qty !== null ? $p->qty + 0 : '' }} {{ $p->unit }}</td>
                                <td>{{ $n($p->rate) }}</td>
                                <td>{{ $p->supplier }}</td>
                                <td>{{ $p->department }}</td>
                            </tr>
                        @endforeach
                    </table>
                </details>
            @else
                <span class="text-muted">—</span>
            @endif
        </td>
        <td>@if($r['min'] !== null){{ $n($r['min']) }} – {{ $n($r['max']) }} ({{ $n($r['avg']) }})@endif</td>
        <td>
            @if($r['change_pct'] !== null)
                <span class="badge {{ $r['change_pct'] > 0 ? 'bg-danger' : ($r['change_pct'] < 0 ? 'bg-success' : 'bg-secondary') }}">
                    {{ $r['change_pct'] > 0 ? '+' : '' }}{{ $r['change_pct'] }}%
                </span>
            @endif
        </td>
    </tr>
@endforeach
</tbody>
</table>

<style>
@media print {
    body { background: #fff !important; }
    .navbar, .report-actions, .alert { display: none !important; }
    .container-fluid { padding: 0 !important; }
    .card { box-shadow: none; }
    table { font-size: 9pt; }
}
</style>
@endsection