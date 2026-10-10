<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Purchase Checker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body{font-family:Inter,system-ui,sans-serif;background:#f4f6fb}
.dashboard-navbar{background:#fff;border-bottom:1px solid #e7ebf2;box-shadow:0 3px 14px rgba(15,23,42,.045)}
.dashboard-navbar .navbar-brand{display:flex;align-items:center;gap:.7rem;color:#17233b;font-size:.98rem;font-weight:700;letter-spacing:-.025em}
.dashboard-navbar .navbar-brand img{display:block;width:76px;height:auto}
.dashboard-navbar .brand-caption{display:block;margin-top:.12rem;color:#718096;font-size:.68rem;font-weight:500;letter-spacing:.035em}
.dashboard-navbar .navbar-nav{display:flex;flex-direction:row;flex-wrap:wrap;gap:.3rem}
.dashboard-navbar .nav-link{display:flex;align-items:center;gap:.45rem;padding:.62rem .82rem;color:#5b667a;font-size:.88rem;font-weight:600;border-radius:10px;transition:background-color .18s ease,color .18s ease}
.dashboard-navbar .nav-link:hover{background:#f5f7fb;color:#243b75}
.dashboard-navbar .nav-link.active{background:#eef2ff;color:#2947a8;font-weight:700}
.dashboard-navbar .nav-link:focus-visible{outline:3px solid rgba(84,120,237,.35);outline-offset:2px}
.card{border:0;border-radius:12px;box-shadow:0 2px 10px rgba(15,23,42,.08)}
.stat{padding:16px 20px}.stat .n{font-size:28px;font-weight:600}
tr[data-status=found]>td:first-child{border-left:4px solid #198754}
tr[data-status=similar]>td:first-child{border-left:4px solid #ffc107}
tr[data-status=new]>td:first-child{border-left:4px solid #dc3545}
tr[data-status=new]{background:#fff5f5}
@media(max-width:767.98px){
    .dashboard-navbar .container-fluid{align-items:flex-start;flex-direction:column;gap:.55rem}
    .dashboard-navbar .navbar-nav{width:100%;gap:.15rem}
    .dashboard-navbar .nav-link{justify-content:center;flex:1 1 auto;padding:.55rem .65rem;font-size:.82rem}
}
</style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand dashboard-navbar mb-4" aria-label="Main navigation">
    <div class="container-fluid">
        <a class="navbar-brand text-decoration-none" href="{{ session('logged_in') ? route('home') : route('login') }}">
            <img src="{{ asset('images/liberty-chemicals-logo.jpg') }}" alt="Liberty Chemicals Industries Ltd.">
            <span>Purchase Checker<span class="brand-caption">Purchasing dashboard</span></span>
        </a>
        @if(session('logged_in'))
            <div class="navbar-nav">
                <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}"><i class="bi bi-grid-1x2" aria-hidden="true"></i> Dashboard</a>
                @if(session('user_role', 'super_admin') === 'super_admin')
                    <a class="nav-link {{ request()->routeIs('check.*') ? 'active' : '' }}" href="{{ route('check.index') }}"><i class="bi bi-search" aria-hidden="true"></i> Check new list</a>
                    <a class="nav-link {{ request()->routeIs('history.index','history.create','history.edit','history.month') ? 'active' : '' }}" href="{{ route('history.index') }}"><i class="bi bi-clock-history" aria-hidden="true"></i> History</a>
                    <a class="nav-link {{ request()->routeIs('history.import') ? 'active' : '' }}" href="{{ route('history.import') }}"><i class="bi bi-upload" aria-hidden="true"></i> Import / update</a>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="d-flex">
                    @csrf
                    <button class="nav-link" type="submit"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Logout</button>
                </form>
            </div>
        @endif
    </div>
</nav>
<div class="container-fluid px-4 pb-5">
    @if(session('ok')) <div class="alert alert-success">{{ session('ok') }}</div> @endif
    @if($errors->any())
        <div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif
    @yield('content')
</div>
</body>
</html>