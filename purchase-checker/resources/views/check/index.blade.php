@extends('layouts.app')
@section('content')
<div class="card" style="max-width:640px"><div class="card-body">
    <h5>Upload new purchase list</h5>
    <form method="POST" action="{{ route('check.run') }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label class="form-label">Excel file</label>
            <input type="file" name="file" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Sheet name (blank = first sheet)</label>
            <input name="sheet" value="{{ old('sheet') }}" class="form-control" placeholder="e.g. October">
        </div>
        <div class="mb-3">
    <label class="form-label">OR full file path on this PC</label>
    <input name="path" value="{{ old('path') }}" class="form-control" placeholder='D:\Liberty_Chemicals_Purchase_List_October_2026.xlsx'>
</div>
        <button class="btn btn-primary">Check items</button>
    </form>
</div></div>
@endsection