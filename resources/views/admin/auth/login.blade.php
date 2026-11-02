<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion Admin · Smart Solar Energy Africa</title>
    <style>
        body { font-family: -apple-system, Segoe UI, Roboto, Arial, sans-serif; background: #f4f6f5; display:flex; align-items:center; justify-content:center; height:100vh; margin:0; }
        .box { background:#fff; padding:36px; border-radius:12px; border:1px solid #e6e6e6; width:340px; }
        .brand { text-align:center; font-weight:800; margin-bottom:22px; }
        .brand .smart { color:#1e7d32; }
        .brand .solar { color:#f39c12; }
        label { font-size:13px; font-weight:600; color:#555; display:block; margin:14px 0 4px; }
        input { width:100%; padding:9px 10px; border:1px solid #ccc; border-radius:6px; font-size:14px; box-sizing:border-box; }
        button { width:100%; margin-top:22px; padding:10px; border:none; border-radius:7px; background:#1e7d32; color:#fff; font-weight:700; font-size:14px; cursor:pointer; }
        .alert { background:#fbe4e4; color:#c0392b; padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:10px; }
        .hint { text-align:center; font-size:12px; color:#888; margin-top:16px; }
    </style>
</head>
<body>
    <div class="box">
        <div class="brand"><span class="smart">SMART SOLAR</span> <span class="solar">ENERGY</span></div>

        @if ($errors->any())
            <div class="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}">
            @csrf
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            <label>Mot de passe</label>
            <input type="password" name="password" required>
            <button type="submit">Se connecter</button>
        </form>
        <div class="hint">Espace réservé aux administrateurs SSEA</div>
    </div>
</body>
</html>
