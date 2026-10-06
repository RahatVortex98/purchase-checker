@extends('layouts.app')
@section('content')
<div class="card mb-4" style="max-width:640px"><div class="card-body">
    <h5>Import / update purchase history</h5>
    <form method="POST" action="{{ route('history.import.run') }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-3"><label class="form-label">Excel file</label>
            <input type="file" name="file" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Sheet name</label>
            <input name="sheet" class="form-control" value="{{ old('sheet', 'Previous purchase') }}"></div>
        <div class="mb-3">
            <div class="form-check"><input class="form-check-input" type="radio" name="mode" value="append" id="m1" checked>
                <label class="form-check-label" for="m1">Add to existing history (duplicates skipped automatically)</label></div>
            <div class="form-check"><input class="form-check-input" type="radio" name="mode" value="replace" id="m2">
                <label class="form-check-label" for="m2">Replace all history with this file</label></div>
        </div>
        <button class="btn btn-primary">Import</button>
    </form>
</div></div>

<h6>Recent imports</h6>
<table class="table table-sm bg-white" style="max-width:640px">
    <tr><th>When</th><th>File</th><th>Sheet</th><th>Rows</th></tr>
    @foreach($batches as $b)
        <tr><td>{{ $b->created_at->format('d.m.Y H:i') }}</td><td>{{ $b->file_name }}</td><td>{{ $b->sheet_name }}</td><td>{{ $b->rows_count }}</td></tr>
    @endforeach
</table>
@endsection