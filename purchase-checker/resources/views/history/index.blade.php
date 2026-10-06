@extends('layouts.app')
@section('content')
@php $n = fn($v) => $v === null ? '' : number_format($v, 2); @endphp
<div class="d-flex justify-content-between mb-3">
    <form class="d-flex gap-2" method="GET">
        <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search item / supplier">
        <select name="department" class="form-select form-select-sm">
            <option value="">All departments</option>
            @foreach($departments as $d)
                <option @selected(request('department') === $d)>{{ $d }}</option>
            @endforeach
        </select>
        <button class="btn btn-sm btn-primary">Search</button>
    </form>
    <div>
        <span class="me-3 text-muted">{{ $total }} rows</span>
        <a href="{{ route('history.create') }}" class="btn btn-sm btn-success">+ Add row</a>
        <a href="{{ route('history.import') }}" class="btn btn-sm btn-outline-primary">Import Excel</a>
    </div>
</div>

<table class="table table-sm table-bordered bg-white">
<thead class="table-light"><tr><th>Date</th><th>Item</th><th>Qty</th><th>Unit</th><th>Rate</th><th>Amount</th><th>Supplier</th><th>Dept</th><th></th></tr></thead>
<tbody>
@php $currentMonth = null; @endphp
@foreach($rows as $r)
@php $month = $r->purchase_date?->format('Y-m') ?? 'unknown'; @endphp
@if($month !== $currentMonth)
<tr class="table-secondary"><th colspan="9">{{ $r->purchase_date?->format('F Y') ?? 'Date unknown' }}</th></tr>
@php $currentMonth = $month; @endphp
@endif
<tr>
    <td>{{ $r->purchase_date?->format('d.m.Y') ?? $r->date_text }}</td>
    <td>{{ $r->item_name }}</td>
    <td>{{ $r->qty !== null ? $r->qty + 0 : '' }}</td>
    <td>{{ $r->unit }}</td>
    <td>{{ $n($r->rate) }}</td>
    <td>{{ $n($r->amount) }}</td>
    <td>{{ $r->supplier }}</td>
    <td>{{ $r->department }}</td>
    <td class="text-nowrap">
        <a href="{{ route('history.edit', $r) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
        <form method="POST" action="{{ route('history.destroy', $r) }}" class="d-inline" onsubmit="return confirm('Delete this row?')">
            @csrf @method('DELETE') <button class="btn btn-sm btn-outline-danger">Del</button>
        </form>
    </td>
</tr>
@endforeach
</tbody>
</table>
{{ $rows->links() }}
@endsection