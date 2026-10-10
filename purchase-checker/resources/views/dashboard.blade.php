@extends('layouts.app')
@section('content')
@php
    $maxMonthSpend = max((float) $monthly->max('total'), 1);
    $maxSupplierSpend = max((float) $topSuppliers->max('total'), 1);
@endphp
<style>
.dashboard { --dashboard-ink: #17233b; --dashboard-muted: #718096; --dashboard-line: #e9edf4; color: var(--dashboard-ink); }
.dashboard-heading { display:flex; justify-content:space-between; align-items:flex-end; gap:1rem; margin-bottom:1.5rem; }
.dashboard-heading h1 { font-size:1.65rem; font-weight:700; letter-spacing:-.04em; margin:0; }
.dashboard-heading p { color:var(--dashboard-muted); margin:.35rem 0 0; }
.dashboard .card { border:1px solid var(--dashboard-line); border-radius:16px; box-shadow:0 5px 20px rgba(31,45,75,.045); }
.dashboard .card-body { padding:1.25rem; }
.dashboard-kpi { height:100%; min-height:132px; position:relative; overflow:hidden; }
.dashboard-kpi::after { content:""; position:absolute; width:100px; height:100px; right:-32px; top:-35px; border-radius:50%; background:var(--kpi-tint); }
.dashboard-kpi-icon { display:grid; place-items:center; width:38px; height:38px; color:var(--kpi-color); background:var(--kpi-tint); border-radius:12px; font-size:1.1rem; }
.dashboard-kpi-label { color:var(--dashboard-muted); font-size:.8rem; font-weight:600; margin-top:1rem; }
.dashboard-kpi-value { font-size:1.65rem; font-weight:700; letter-spacing:-.04em; line-height:1.2; margin-top:.2rem; }
.dashboard-section-title { font-weight:700; letter-spacing:-.02em; margin:0; }
.dashboard-section-subtitle { color:var(--dashboard-muted); font-size:.82rem; margin:.25rem 0 0; }
.dashboard .table { --bs-table-bg:transparent; margin-bottom:0; }
.dashboard .table th { color:var(--dashboard-muted); font-size:.72rem; font-weight:600; letter-spacing:.04em; text-transform:uppercase; white-space:nowrap; }
.dashboard .table td, .dashboard .table th { padding:.78rem .6rem; border-color:#edf0f5; vertical-align:middle; }
.dashboard .supplier-rank { display:inline-grid; place-items:center; width:27px; height:27px; border-radius:9px; background:#f1f4f9; color:#68758d; font-size:.75rem; font-weight:700; }
.dashboard .spend-track { height:5px; min-width:56px; overflow:hidden; border-radius:99px; background:#edf1f7; }
.dashboard .spend-track span { display:block; height:100%; border-radius:inherit; background:linear-gradient(90deg,#5478ed,#7a9bff); }
.dashboard .table-wrap { max-height:440px; overflow:auto; }
.dashboard .table-wrap thead { position:sticky; top:0; z-index:1; background:#fff; }
.dashboard .empty-state { color:var(--dashboard-muted); padding:2rem 1rem; text-align:center; }
.dashboard .progress { background:#edf1f7; }
.dashboard .progress-bar { background:linear-gradient(90deg,#5478ed,#7a9bff); }
@media (max-width:767.98px) {
    .dashboard-heading { align-items:flex-start; flex-direction:column; }
    .dashboard-heading h1 { font-size:1.4rem; }
}
</style>

<div class="dashboard">
    <div class="dashboard-heading">
        <div>
            <div class="small text-primary fw-semibold text-uppercase mb-1">Purchase overview</div>
            <h1>Dashboard</h1>
            <p>Track purchasing activity, suppliers, and monthly spend.</p>
        </div>
        @if(session('user_role', 'super_admin') === 'super_admin')
            <a href="{{ route('check.index') }}" class="btn btn-primary px-3 py-2 rounded-3">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Check a new list
            </a>
        @endif
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card dashboard-kpi" style="--kpi-color:#4968dc;--kpi-tint:#edf1ff">
                <div class="card-body">
                    <div class="dashboard-kpi-icon"><i class="bi bi-basket2" aria-hidden="true"></i></div>
                    <div class="dashboard-kpi-label">Purchase items</div>
                    <div class="dashboard-kpi-value">{{ number_format($rows) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card dashboard-kpi" style="--kpi-color:#15957e;--kpi-tint:#e7f7f2">
                <div class="card-body">
                    <div class="dashboard-kpi-icon"><i class="bi bi-buildings" aria-hidden="true"></i></div>
                    <div class="dashboard-kpi-label">Suppliers</div>
                    <div class="dashboard-kpi-value">{{ number_format($suppliers) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card dashboard-kpi" style="--kpi-color:#d18a28;--kpi-tint:#fff5e5">
                <div class="card-body">
                    <div class="dashboard-kpi-icon"><i class="bi bi-cash-stack" aria-hidden="true"></i></div>
                    <div class="dashboard-kpi-label">Total recorded spend</div>
                    <div class="dashboard-kpi-value fs-4">{{ number_format($grandTotal, 2) }} <span class="fs-6 text-muted">BDT</span></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card dashboard-kpi" style="--kpi-color:#7456c8;--kpi-tint:#f1edff">
                <div class="card-body">
                    <div class="dashboard-kpi-icon"><i class="bi bi-cloud-arrow-up" aria-hidden="true"></i></div>
                    <div class="dashboard-kpi-label">Last import</div>
                    <div class="dashboard-kpi-value fs-6 mt-2">{{ $last?->created_at->format('d M Y, H:i') ?? 'No imports yet' }}</div>
                    @if($last)
                        <div class="small text-muted text-truncate mt-1" title="{{ $last->file_name }}">{{ $last->file_name }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-5">
            <section class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h2 class="h6 dashboard-section-title">Spend by department</h2>
                            <p class="dashboard-section-subtitle">Recorded purchase totals · BDT</p>
                        </div>
                        <span class="dashboard-kpi-icon" style="--kpi-color:#4968dc;--kpi-tint:#edf1ff"><i class="bi bi-bar-chart" aria-hidden="true"></i></span>
                    </div>
                    @if($byDept->isNotEmpty())
                        <div style="height:270px"><canvas id="dept" aria-label="Spend by department chart"></canvas></div>
                    @else
                        <div class="empty-state">Department spend will appear after importing items with department and amount values.</div>
                    @endif
                </div>
            </section>
        </div>
        <div class="col-xl-7">
            <section class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h2 class="h6 dashboard-section-title">All suppliers</h2>
                            <p class="dashboard-section-subtitle">{{ $topSuppliers->count() }} suppliers ranked by recorded spend</p>
                        </div>
                        <span class="dashboard-kpi-icon" style="--kpi-color:#15957e;--kpi-tint:#e7f7f2"><i class="bi bi-people" aria-hidden="true"></i></span>
                    </div>
                    <div class="table-wrap">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Supplier</th><th class="text-end">Items</th><th style="width:26%">Spend</th><th class="text-end">Total (BDT)</th></tr></thead>
                            <tbody>
                                @forelse($topSuppliers as $index => $supplier)
                                    <tr>
                                        <td><span class="supplier-rank me-2">{{ $index + 1 }}</span><span class="fw-semibold">{{ $supplier->supplier }}</span></td>
                                        <td class="text-end text-muted">{{ number_format($supplier->lines) }}</td>
                                        <td><div class="spend-track"><span style="width:{{ max(0, min(100, (float) $supplier->total / $maxSupplierSpend * 100)) }}%"></span></div></td>
                                        <td class="text-end fw-semibold text-nowrap">{{ number_format($supplier->total, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="empty-state">Supplier totals will appear after importing items with supplier and amount values.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <section class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
                <div>
                    <h2 class="h6 dashboard-section-title">Monthly purchase cost</h2>
                    <p class="dashboard-section-subtitle">Browse purchase items and spend by month.</p>
                </div>
                <div class="small text-muted">All recorded spend: <strong class="text-dark">{{ number_format($grandTotal, 2) }} BDT</strong></div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Year</th><th>Month</th><th class="text-end">Items</th><th class="text-end">Total cost (BDT)</th><th style="width:24%"></th></tr></thead>
                    <tbody>
                        @forelse($monthly as $month)
                            <tr>
                                <td class="text-muted">{{ $month['year'] ?? '—' }}</td>
                                <td class="fw-semibold">
                                    @if($month['year'])
                                        <a class="text-decoration-none" href="{{ route('history.month', ['year' => $month['year'], 'month' => substr($month['key'], 5, 2)]) }}">{{ $month['month'] }} <i class="bi bi-arrow-up-right small" aria-hidden="true"></i></a>
                                    @else
                                        {{ $month['month'] }}
                                    @endif
                                </td>
                                <td class="text-end text-muted">{{ number_format($month['lines']) }}</td>
                                <td class="text-end fw-semibold">{{ number_format($month['total'], 2) }}</td>
                                <td><div class="progress" style="height:6px"><div class="progress-bar" role="progressbar" style="width:{{ max(0, min(100, $month['total'] / $maxMonthSpend * 100)) }}%" aria-valuenow="{{ round($month['total'] / $maxMonthSpend * 100) }}" aria-valuemin="0" aria-valuemax="100"></div></div></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="empty-state">Monthly spend will appear after purchase history is imported.</td></tr>
                        @endforelse
                    </tbody>
                    @if($monthly->isNotEmpty())
                        <tfoot>
                            @foreach($yearTotals as $year => $total)
                                <tr><td colspan="3" class="text-muted">Total {{ $year }}</td><td class="text-end fw-bold">{{ number_format($total, 2) }}</td><td></td></tr>
                            @endforeach
                            <tr class="border-top"><td colspan="3" class="fw-bold">Grand total</td><td class="text-end fw-bold">{{ number_format($grandTotal, 2) }}</td><td></td></tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </section>
</div>
@if($byDept->isNotEmpty())
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        const departmentChart = document.getElementById('dept');
        if (typeof Chart !== 'undefined' && departmentChart) {
            new Chart(departmentChart, {
                type: 'bar',
                data: {
                    labels: @json($byDept->pluck('department')),
                    datasets: [{
                        data: @json($byDept->pluck('total')),
                        backgroundColor: ['#5478ed', '#28a78b', '#f1ae4e', '#9673e6', '#35a6c8', '#e8788c', '#8394ac', '#b19a60'],
                        borderRadius: 7,
                        borderSkipped: false,
                        barThickness: 20,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: context => ` ${Number(context.raw).toLocaleString()} BDT` } },
                    },
                    scales: {
                        x: { beginAtZero: true, grid: { color: '#edf0f5' }, ticks: { callback: value => Number(value).toLocaleString() } },
                        y: { grid: { display: false } },
                    },
                },
            });
        }
    </script>
@endif
@endsection