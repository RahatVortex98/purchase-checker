<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Purchase Checker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <span class="navbar-brand">Purchase Checker</span>
        <div class="navbar-nav">
            <a class="nav-link" href="{{ route('check.index') }}">Check new list</a>
            <a class="nav-link" href="{{ route('history.index') }}">History</a>
            <a class="nav-link" href="{{ route('history.import') }}">Import / update history</a>
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