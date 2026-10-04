@props(['code' => 500, 'title' => 'Terjadi Kesalahan', 'message' => 'Maaf, ada gangguan pada server.'])

{{--
    Halaman error kustom CMS Bumi (menggantikan tampilan bawaan Laravel).
    Sengaja mandiri: CSS inline + tanpa akses database, supaya tetap tampil
    walau database/aset sedang bermasalah.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $code }} — {{ $title }} | MA Bustanul Muta'allimin</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <style>
        :root { --brand:#17663a; --brand-2:#2f9e5c; --ink:#0d2a1b; --muted:#6b7280; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { min-height:100vh; display:grid; place-items:center; padding:24px;
               background:#f4f8f5; color:var(--ink);
               font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; }
        .bg { position:fixed; inset:0; pointer-events:none;
              background-image:linear-gradient(rgba(23,102,58,.07) 1px,transparent 1px),
                               linear-gradient(90deg,rgba(23,102,58,.07) 1px,transparent 1px);
              background-size:44px 44px; }
        .card { position:relative; width:100%; max-width:620px; background:#fff; border:1px solid #e6efe8;
                border-radius:28px; padding:44px 32px; text-align:center;
                box-shadow:0 24px 60px rgba(13,42,27,.13); }
        .logo { width:64px; height:64px; margin:0 auto 16px; display:block; }
        .badge { display:inline-block; margin-bottom:16px; padding:6px 14px; border-radius:999px;
                 background:#e8f4ec; color:var(--brand); font-size:11px; font-weight:800;
                 letter-spacing:.09em; text-transform:uppercase; }
        .code { font-size:62px; font-weight:800; letter-spacing:-2px; color:var(--brand); line-height:1; }
        h1 { margin-top:12px; font-size:21px; font-weight:800; }
        p { margin-top:10px; color:var(--muted); font-size:15px; line-height:1.65; }
        .actions { margin-top:26px; display:flex; gap:12px; justify-content:center; flex-wrap:wrap; }
        a { display:inline-flex; align-items:center; gap:8px; text-decoration:none;
            font-weight:700; font-size:14px; padding:11px 20px; border-radius:14px; }
        .primary { background:var(--brand); color:#fff; box-shadow:0 10px 24px rgba(23,102,58,.25); }
        .ghost { border:1px solid #cfe3d6; color:var(--brand); background:#fff; }
        .brandline { margin-top:28px; padding-top:18px; border-top:1px solid #eef4f0;
                     color:#9aa8a0; font-size:12px; }
    </style>
</head>
<body>
    <div class="bg"></div>

    <main class="card">
        <svg class="logo" viewBox="0 0 64 64" aria-hidden="true">
            <defs>
                <linearGradient id="brandgrad" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stop-color="#17663a"/>
                    <stop offset="1" stop-color="#2f9e5c"/>
                </linearGradient>
            </defs>
            <rect x="4" y="4" width="56" height="56" rx="14" fill="url(#brandgrad)"/>
            <path d="M32 12 L48 22 L32 32 L16 22 Z" fill="#f2dd95"/>
            <path d="M20 26 v12 c0 4 5 6 12 6 s12 -2 12 -6 v-12" fill="none"
                  stroke="#fdf9ec" stroke-width="3" stroke-linecap="round"/>
            <rect x="30" y="44" width="4" height="8" rx="1.5" fill="#fdf9ec"/>
        </svg>

        <span class="badge">MA Bustanul Muta'allimin</span>

        <div class="code">{{ $code }}</div>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>

        <div class="actions">
            <a class="primary" href="{{ config('cms.frontend_url') }}">Kembali ke Website</a>
            <a class="ghost" href="{{ config('cms.frontend_url') }}/berita">Lihat Berita</a>
        </div>

        <div class="brandline">© {{ date('Y') }} MA Bustanul Muta'allimin · by Bumi Production</div>
    </main>
</body>
</html>
