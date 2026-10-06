@extends('layouts.app')
@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card stat"><div class="text-muted small">History rows</div><div class="n">{{ $rows }}</div></div></div>
    <div class="col-md-3"><div class="card stat"><div class="text-muted small">Suppliers</div><div class="n">{{ $suppliers }}</div></div></div>
    <div class="col-md-3"><div class="card stat"><div class="text-muted small">Last import</div><div class="fw-semibold">{{ $last?->created_at->format('d M Y H:i') ?? '—' }}</div><div class="small text-muted">{{ $last?->file_name }}</div></div></div>
    <div class="col-md-3 d-flex"><a href="{{ route('check.index') }}" class="btn btn-primary w-100 d-flex align-items-center justify-content-center fs-5"><i class="bi bi-search me-2"></i> Check new list</a></div>
</div>
<div class="row g-3">
    <div class="col-lg-7"><div class="card"><div class="card-body"><h6>Spend by department (BDT)</h6><canvas id="dept" height="130"></canvas></div></div></div>
    <div class="col-lg-5"><div class="card"><div class="card-body"><h6>Top suppliers</h6>
        <table class="table table-sm mb-0">
            @foreach($topSuppliers as $s)
                <tr><td>{{ $s->supplier }}</td><td class="text-muted">{{ $s->lines }} lines</td><td class="text-end">{{ number_format($s->total) }}</td></tr>
            @endforeach
        </table>
    </div></div></div>
</div>
<div class="card mt-3"><div class="card-body">
    <h6>Monthly purchase cost</h6>
    @php $max = max($monthly->max('total'), 1); @endphp
    <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
            <tr><th>Year</th><th>Month</th><th class="text-end">Lines</th><th class="text-end">Total cost (BDT)</th><th style="width:30%"></th></tr>
        </thead>
        <tbody>
            @foreach($monthly as $m)
                <tr>
                    <td>{{ $m['year'] ?? '—' }}</td>
                    <td>
                        @if($m['year'])
                            <a href="{{ route('history.month', ['year' => $m['year'], 'month' => substr($m['key'], 5, 2)]) }}">{{ $m['month'] }}</a>
                        @else
                            {{ $m['month'] }}
                        @endif
                    </td>
                    <td class="text-end">{{ $m['lines'] }}</td>
                    <td class="text-end fw-semibold">{{ number_format($m['total'], 2) }}</td>
                    <td><div class="progress" style="height:8px"><div class="progress-bar" style="width:{{ $m['total'] / $max * 100 }}%"></div></div></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="table-light">
            @foreach($yearTotals as $y => $t)
                <tr><td colspan="3">Total {{ $y }}</td><td class="text-end fw-bold">{{ number_format($t, 2) }}</td><td></td></tr>
            @endforeach
            <tr><td colspan="3">Grand total (all rows)</td><td class="text-end fw-bold">{{ number_format($grandTotal, 2) }}</td><td></td></tr>
        </tfoot>
    </table>
</div></div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('dept'), {
    type: 'bar',
    data: { labels: @json($byDept->pluck('department')),
            datasets: [{ data: @json($byDept->pluck('total')), backgroundColor: '#3b5bdb', borderRadius: 6 }] },
    options: { plugins: { legend: { display: false } } }
});
</script>
@endsection