@extends('layouts.app')
@section('content')
@php $n = fn($value) => $value === null ? '' : number_format($value, 2); @endphp
<style>
@@media print {
    @page { margin: 12mm; }
    body { background: #fff !important; }
    .navbar, .no-print { display: none !important; }
    .container-fluid { padding: 0 !important; }
    .table { font-size: 10pt; }
    .table thead { display: table-header-group; }
    tr { break-inside: avoid; }
}
</style>

<div class="d-flex justify-content-between align-items-start gap-3 mb-3">
    <div>
        <div class="text-muted small">Monthly purchase report</div>
        <h1 class="h3 mb-1">{{ $month->format('F Y') }} purchases</h1>
        <div class="text-muted">{{ $rows->count() }} purchase items</div>
    </div>
    <div class="d-flex gap-2 no-print">
        <a href="{{ route('home') }}" class="btn btn-sm btn-outline-secondary">Dashboard</a>
        @if(session('user_role', 'super_admin') === 'super_admin')
            <a href="{{ route('history.create', ['purchase_date' => $month->toDateString(), 'return_month' => $month->format('Y-m')]) }}" class="btn btn-sm btn-success">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> New entry
            </a>
        @endif
        <button type="button" class="btn btn-sm btn-primary" onclick="window.print()">
            <i class="bi bi-printer me-1" aria-hidden="true"></i> Print
        </button>
    </div>
</div>

<table class="table table-sm table-bordered bg-white align-middle">
    <thead class="table-light">
        <tr><th>Item</th><th class="text-end">Qty</th><th>Unit</th><th class="text-end">Rate</th><th class="text-end">Amount</th><th>Supplier</th><th>Department</th>@if(session('user_role', 'super_admin') === 'super_admin')<th class="no-print">Actions</th>@endif</tr>
    </thead>
    <tbody>
        @forelse($dateGroups as $date => $dateRows)
            <tr class="table-secondary">
                <th colspan="{{ session('user_role', 'super_admin') === 'super_admin' ? 8 : 7 }}">
                    <div class="d-flex justify-content-between align-items-center gap-3">
                        <span><i class="bi bi-calendar3 me-1" aria-hidden="true"></i>{{ \Carbon\Carbon::parse($date)->format('d.m.Y') }}</span>
                        <span class="small fw-normal">{{ $dateRows->count() }} {{ $dateRows->count() === 1 ? 'item' : 'items' }} · {{ number_format($dateRows->sum('amount'), 2) }} BDT</span>
                    </div>
                </th>
            </tr>
            @foreach($dateRows as $row)
                <tr>
                    <td>{{ $row->item_name }}</td>
                    <td class="text-end">{{ $row->qty !== null ? $row->qty + 0 : '' }}</td>
                    <td>{{ $row->unit }}</td>
                    <td class="text-end">{{ $n($row->rate) }}</td>
                    <td class="text-end">{{ $n($row->amount) }}</td>
                    <td>{{ $row->supplier }}</td>
                    <td>{{ $row->department }}</td>
                    @if(session('user_role', 'super_admin') === 'super_admin')
                        <td class="no-print text-nowrap">
                            <a href="{{ route('history.edit', ['history' => $row, 'return_month' => $month->format('Y-m')]) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('history.destroy', $row) }}" class="d-inline" onsubmit="return confirm('Delete this purchase item?')">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="return_month" value="{{ $month->format('Y-m') }}">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                            </form>
                        </td>
                    @endif
                </tr>
            @endforeach
        @empty
            <tr><td colspan="{{ session('user_role', 'super_admin') === 'super_admin' ? 8 : 7 }}" class="text-center text-muted py-4">No purchases recorded for this month.</td></tr>
        @endforelse
    </tbody>
    <tfoot class="table-light">
        <tr><td colspan="5"></td><th class="text-end">Total spend</th><th class="text-end">{{ number_format($total, 2) }}</th><td></td></tr>
    </tfoot>
</table>
@endsection