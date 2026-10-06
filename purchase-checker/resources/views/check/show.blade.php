@extends('layouts.app')
@section('content')
@php $n = fn($v) => $v === null ? '' : number_format($v, 2); @endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h5 class="mb-0">{{ $data['file'] }} @if($data['sheet'])<small class="text-muted">/ {{ $data['sheet'] }}</small>@endif</h5>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('check.export', $token) }}">Download full result</a>
        <a class="btn btn-outline-danger btn-sm" href="{{ route('check.export', [$token, 'only' => 'new']) }}">Download NEW items (for price inquiry)</a>
        <form method="POST" action="{{ route('check.save', $token) }}" onsubmit="return confirm('Add all rows of this list to purchase history?')">
            @csrf <button class="btn btn-success btn-sm">Save this list to history</button>
        </form>
    </div>
</div>

<div class="btn-group btn-group-sm mb-3" id="filters">
    <button class="btn btn-dark active" data-f="all">All ({{ count($results) }})</button>
    <button class="btn btn-outline-success" data-f="found">Bought before ({{ $counts['found'] ?? 0 }})</button>
    <button class="btn btn-outline-warning" data-f="similar">Similar ({{ $counts['similar'] ?? 0 }})</button>
    <button class="btn btn-outline-danger" data-f="new">New item ({{ $counts['new'] ?? 0 }})</button>
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

<script>
document.querySelectorAll('#filters button').forEach(b => b.onclick = () => {
    document.querySelectorAll('#filters button').forEach(x => x.classList.remove('active'));
    b.classList.add('active');
    document.querySelectorAll('tr[data-status]').forEach(tr => {
        tr.style.display = (b.dataset.f === 'all' || tr.dataset.status === b.dataset.f) ? '' : 'none';
    });
});
</script>
@endsection