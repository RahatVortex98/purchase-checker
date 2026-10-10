@extends('layouts.app')
@section('content')
<div class="card" style="max-width:720px"><div class="card-body">
<h5>{{ $row->exists ? 'Edit' : 'Add' }} purchase item</h5>
<form method="POST" action="{{ $row->exists ? route('history.update', $row) : route('history.store') }}">
    @csrf
    @if($row->exists) @method('PUT') @endif
    @if($returnMonth)
        <input type="hidden" name="return_month" value="{{ $returnMonth->format('Y-m') }}">
    @endif
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Date</label>
            <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', $row->purchase_date?->format('Y-m-d')) }}"></div>
        <div class="col-md-8"><label class="form-label">Item *</label>
            <input name="item_name" class="form-control" required value="{{ old('item_name', $row->item_name) }}"></div>
        <div class="col-md-3"><label class="form-label">Qty</label>
            <input name="qty" class="form-control" value="{{ old('qty', $row->qty) }}"></div>
        <div class="col-md-3"><label class="form-label">Unit</label>
            <input name="unit" class="form-control" value="{{ old('unit', $row->unit) }}"></div>
        <div class="col-md-3"><label class="form-label">Rate</label>
            <input name="rate" class="form-control" value="{{ old('rate', $row->rate) }}"></div>
        <div class="col-md-3"><label class="form-label">Amount</label>
            <input name="amount" class="form-control" value="{{ old('amount', $row->amount) }}"></div>
        <div class="col-md-6"><label class="form-label">Supplier</label>
            <input name="supplier" class="form-control" value="{{ old('supplier', $row->supplier) }}"></div>
        <div class="col-md-6"><label class="form-label">Department</label>
            <input name="department" class="form-control" value="{{ old('department', $row->department) }}"></div>
    </div>
    <button class="btn btn-primary mt-3">Save</button>
    <a href="{{ $returnMonth ? route('history.month', ['year' => $returnMonth->year, 'month' => $returnMonth->format('m')]) : route('history.index') }}" class="btn btn-link mt-3">Cancel</a>
</form>
</div></div>
@endsection