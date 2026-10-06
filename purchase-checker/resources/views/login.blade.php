@extends('layouts.app')
@section('content')
<div class="card mx-auto" style="max-width:380px"><div class="card-body">
    <h5 class="mb-3"><i class="bi bi-lock"></i> Login</h5>
    <form method="POST" action="{{ url('/login') }}">
        @csrf
        <input type="password" name="password" class="form-control mb-3" placeholder="Password" autofocus required>
        <button class="btn btn-primary w-100">Enter</button>
    </form>
</div></div>
@endsection