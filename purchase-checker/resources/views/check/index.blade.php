@extends('layouts.app')
@section('content')
<div class="card" style="max-width:640px"><div class="card-body">
    <h5>Check a new requisition</h5>
    <form method="POST" action="{{ route('check.run') }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label class="form-label">New requisition Excel file</label>
            <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Sheet name (blank = first sheet)</label>
            <input name="sheet" value="{{ old('sheet') }}" class="form-control" placeholder="e.g. October">
        </div>
        <hr>
        <div class="mb-3">
            <label class="form-label">Previous purchase history Excel (optional)</label>
            <input type="file" name="history_file" class="form-control" accept=".xlsx,.xls,.csv">
            <div class="form-text">Used only to compare this requisition; it will not be saved to purchase history. Items not found in this file or saved history are marked NEW.</div>
        </div>
        <div class="mb-3">
            <label class="form-label">History sheet name (blank = first sheet)</label>
            <input name="history_sheet" value="{{ old('history_sheet') }}" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">OR full file path on this PC</label>
            <input name="path" value="{{ old('path') }}" class="form-control" placeholder='D:\Liberty_Chemicals_Purchase_List_October_2026.xlsx'>
        </div>
        <button class="btn btn-primary">Check items</button>
    </form>
</div></div>
@endsection