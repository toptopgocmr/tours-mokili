<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') · Smart Solar Energy Africa</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root {
            --vert: #1e7d32;
            --orange: #f39c12;
            --gris: #555;
            --bg: #f4f6f5;
        }
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, Segoe UI, Roboto, Arial, sans-serif;
            margin: 0;
            background: var(--bg);
            color: #222;
        }
        .topbar {
            background: #fff;
            border-bottom: 1px solid #e2e2e2;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .brand { display: flex; align-items: center; gap: 10px; font-weight: 700; }
        .brand .smart { color: var(--vert); }
        .brand .solar { color: var(--orange); }
        nav.main a {
            margin-right: 18px;
            text-decoration: none;
            color: var(--gris);
            font-size: 14px;
            font-weight: 600;
        }
        nav.main a.active, nav.main a:hover { color: var(--vert); }
        .wrap { max-width: 1180px; margin: 24px auto; padding: 0 20px; }
        .card {
            background: #fff;
            border-radius: 10px;
            border: 1px solid #e6e6e6;
            padding: 20px;
            margin-bottom: 20px;
        }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #eee; text-align: left; }
        th { color: var(--gris); font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            background: #eef3ee;
            color: var(--vert);
        }
        .badge.orange { background: #fdf1de; color: #b9770e; }
        .badge.rouge { background: #fbe4e4; color: #c0392b; }
        .badge.gris { background: #eee; color: #666; }
        .btn {
            display: inline-block;
            padding: 9px 16px;
            border-radius: 7px;
            background: var(--vert);
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }
        .btn.orange { background: var(--orange); }
        .btn.secondaire { background: #fff; color: var(--gris); border: 1px solid #ccc; }
        input, select, textarea {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
        }
        label { font-size: 13px; font-weight: 600; color: var(--gris); display: block; margin-bottom: 4px; }
        .grid { display: grid; gap: 14px; }
        .grid.cols-2 { grid-template-columns: 1fr 1fr; }
        .grid.cols-3 { grid-template-columns: repeat(3, 1fr); }
        .grid.cols-4 { grid-template-columns: repeat(4, 1fr); }
        .stat { text-align: center; }
        .stat .num { font-size: 28px; font-weight: 800; color: var(--vert); }
        .stat .label { font-size: 13px; color: var(--gris); }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .alert.success { background: #eaf7ec; color: #1e7d32; }
        .alert.error { background: #fbe4e4; color: #c0392b; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        h2 { font-size: 17px; }
        .muted { color: var(--gris); font-size: 13px; }
        .section-title { font-size: 13px; text-transform: uppercase; letter-spacing: .05em; color: var(--vert); font-weight: 700; margin: 20px 0 10px; }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="brand">
            <span class="smart">SMART SOLAR</span> <span class="solar">ENERGY AFRICA</span>
        </div>
        @auth
        <nav class="main">
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Tableau de bord</a>
            <a href="{{ route('admin.dossiers.index') }}" class="{{ request()->routeIs('admin.dossiers.*') ? 'active' : '' }}">Fiches terrain</a>
            <form method="POST" action="{{ route('admin.logout') }}" style="display:inline">
                @csrf
                <button type="submit" class="btn secondaire" style="padding:6px 14px;">Déconnexion ({{ auth()->user()->name }})</button>
            </form>
        </nav>
        @endauth
    </div>

    <div class="wrap">
        @if (session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert error">
                <ul style="margin:0; padding-left:18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </div>
</body>
</html>
