@extends('layouts.app')
@section('content')
<div class="card mx-auto" style="max-width:380px"><div class="card-body">
    <h5 class="mb-3"><i class="bi bi-lock"></i> Login</h5>
    <form method="POST" action="{{ route('login.submit') }}">
        @csrf
        <label for="email" class="form-label">Managing director email <span class="text-muted">(leave blank for admin)</span></label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control mb-3" placeholder="name@example.com" autocomplete="username">
        <label for="password" class="form-label">Password</label>
        <input id="password" type="password" name="password" class="form-control mb-3" placeholder="Password" autocomplete="current-password" autofocus required>
        <button class="btn btn-primary w-100">Enter</button>
    </form>
</div></div>
@endsection