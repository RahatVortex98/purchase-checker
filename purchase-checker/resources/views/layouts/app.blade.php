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
.navbar{background:linear-gradient(90deg,#0f172a,#1e3a8a)!important}
.navbar .nav-link.active{color:#fff;font-weight:600;border-bottom:2px solid #60a5fa}
.card{border:0;border-radius:12px;box-shadow:0 2px 10px rgba(15,23,42,.08)}
.stat{padding:16px 20px}.stat .n{font-size:28px;font-weight:600}
tr[data-status=found]>td:first-child{border-left:4px solid #198754}
tr[data-status=similar]>td:first-child{border-left:4px solid #ffc107}
tr[data-status=new]>td:first-child{border-left:4px solid #dc3545}
tr[data-status=new]{background:#fff5f5}
</style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand navbar-dark mb-4">
    <div class="container-fluid">
        <span class="navbar-brand"><i class="bi bi-clipboard-data"></i> Liberty Purchase Checker</span>
        <div class="navbar-nav">
            <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a class="nav-link {{ request()->routeIs('check.*') ? 'active' : '' }}" href="{{ route('check.index') }}"><i class="bi bi-search"></i> Check new list</a>
            <a class="nav-link {{ request()->routeIs('history.index','history.create','history.edit') ? 'active' : '' }}" href="{{ route('history.index') }}"><i class="bi bi-clock-history"></i> History</a>
            <a class="nav-link {{ request()->routeIs('history.import') ? 'active' : '' }}" href="{{ route('history.import') }}"><i class="bi bi-upload"></i> Import / update</a>
        </div>
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