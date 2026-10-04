<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API Backend — {{ $nama }}</title>
    <meta name="description" content="Server API backend resmi website MA Bustanul Muta'allimin.">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <style>
        :root { --brand:#17663a; --brand-2:#2f9e5c; --gold:#e0b544; --ink:#0d2a1b; --muted:#6b7280; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { min-height:100vh; display:grid; place-items:center; padding:24px;
               background:#f4f8f5; color:var(--ink);
               font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; }
        .bg { position:fixed; inset:0; pointer-events:none;
              background-image:linear-gradient(rgba(23,102,58,.07) 1px,transparent 1px),
                               linear-gradient(90deg,rgba(23,102,58,.07) 1px,transparent 1px);
              background-size:44px 44px; }
        .card { position:relative; width:100%; max-width:660px; background:#fff; border:1px solid #e6efe8;
                border-radius:28px; padding:44px 34px; text-align:center;
                box-shadow:0 24px 60px rgba(13,42,27,.13); }
        .logo { width:72px; height:72px; margin:0 auto 18px; display:block; }
        .badge { display:inline-block; margin-bottom:16px; padding:6px 14px; border-radius:999px;
                 background:#e8f4ec; color:var(--brand); font-size:11px; font-weight:800;
                 letter-spacing:.09em; text-transform:uppercase; }
        h1 { font-size:26px; font-weight:800; letter-spacing:-.4px; }
        .sub { margin-top:6px; font-size:15px; font-weight:700; color:var(--brand-2); }
        p.lead { margin-top:14px; color:var(--muted); font-size:15px; line-height:1.7; }
        .chips { margin-top:20px; display:flex; gap:8px; justify-content:center; flex-wrap:wrap; }
        .chip { display:inline-flex; align-items:center; gap:6px; padding:6px 13px; border-radius:999px;
                background:#f2f7f4; border:1px solid #e2ece7; color:#41564b; font-size:12px; font-weight:700; }
        .dot { width:7px; height:7px; border-radius:999px; background:#2f9e5c;
               box-shadow:0 0 0 3px rgba(47,158,92,.18); }
        .actions { margin-top:26px; display:flex; gap:12px; justify-content:center; flex-wrap:wrap; }
        a { display:inline-flex; align-items:center; gap:8px; text-decoration:none;
            font-weight:700; font-size:14px; padding:11px 20px; border-radius:14px; }
        .primary { background:var(--brand); color:#fff; box-shadow:0 10px 24px rgba(23,102,58,.25); }
        .ghost { border:1px solid #cfe3d6; color:var(--brand); background:#fff; }
        .endpoints { margin-top:28px; text-align:left; border-top:1px solid #eef4f0; padding-top:20px; }
        .endpoints h2 { font-size:12px; font-weight:800; letter-spacing:.09em; text-transform:uppercase;
                        color:#9aa8a0; margin-bottom:10px; }
        .endpoints ul { list-style:none; display:grid; gap:6px; }
        .endpoints li { display:flex; align-items:center; justify-content:space-between; gap:12px;
                        padding:8px 12px; border-radius:10px; background:#f7faf8; font-size:13px; }
        .method { font-weight:800; color:var(--brand-2); font-size:11px; letter-spacing:.05em; }
        code { font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; font-size:12.5px; color:#31463a; }
        .brandline { margin-top:26px; padding-top:18px; border-top:1px solid #eef4f0;
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

        <span class="badge">API Backend</span>
        <h1>{{ $nama }}</h1>
        <div class="sub">Server Backend Website Madrasah</div>

        <p class="lead">
            Ini adalah <strong>server backend (API) resmi</strong> website {{ $nama }}.
            Halaman ini bukan untuk pengunjung — seluruh data madrasah disajikan lewat
            endpoint <code>/api</code> dan dikonsumsi oleh website utama.
        </p>

        <div class="chips">
            <span class="chip"><span class="dot"></span> Layanan Aktif</span>
            <span class="chip">Laravel {{ $versi }}</span>
            <span class="chip">Mode {{ ucfirst($lingkungan) }}</span>
        </div>

        <div class="actions">
            <a class="primary" href="{{ config('cms.frontend_url') }}">Buka Website Utama</a>
            <a class="ghost" href="/api/settings">Cek Endpoint API</a>
        </div>

        <section class="endpoints">
            <h2>Endpoint Publik</h2>
            <ul>
                <li><code>/api/settings</code><span class="method">GET</span></li>
                <li><code>/api/berita</code><span class="method">GET</span></li>
                <li><code>/api/profil</code><span class="method">GET</span></li>
                <li><code>/api/jurusan</code><span class="method">GET</span></li>
            </ul>
        </section>

        <div class="brandline">© {{ date('Y') }} {{ $nama }} · by Bumi Production</div>
    </main>
</body>
</html>
