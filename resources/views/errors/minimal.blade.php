{{-- Menggantikan template error bawaan Laravel (errors::minimal), jadi 401/403/404/419/429/500/503
     memakai tampilan CPS ERA. CSS sengaja inline: halaman error harus tetap tampil walau aset Vite gagal. --}}
@php
    $code = trim($__env->yieldContent('code'));
    $message = match ($code) {
        '401' => 'Silakan masuk terlebih dahulu untuk membuka halaman ini.',
        '403' => 'Anda tidak punya akses ke halaman ini.',
        '404' => 'Halaman yang Anda cari tidak ditemukan atau sudah dipindahkan.',
        '419' => 'Sesi Anda sudah habis. Muat ulang halaman lalu coba lagi.',
        '429' => 'Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.',
        '500' => 'Terjadi kesalahan di server. Coba lagi beberapa saat lagi.',
        '503' => 'Sistem sedang dalam perawatan. Coba lagi beberapa saat lagi.',
        default => trim($__env->yieldContent('message')),
    };
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} · CPS ERA</title>
    <link rel="icon" href="{{ asset('images/cps-logo.png') }}">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 16px;
               font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; color: #18181b;
               background: linear-gradient(135deg, #f4f9f5 0%, #ffffff 55%, #eef6f0 100%); }
        .card { width: 100%; max-width: 420px; background: #fff; border: 1px solid #e5e5e5; border-radius: 8px; padding: 32px 28px; text-align: center; }
        .logo { width: 56px; height: 56px; margin: 0 auto 16px; padding: 6px; border: 1px solid #e5e5e5; border-radius: 8px; }
        .logo img { width: 100%; height: 100%; object-fit: contain; }
        .code { font-family: "IBM Plex Mono", ui-monospace, monospace; font-size: 12px; color: #0b7840; border: 1px solid #0b7840; border-radius: 2px; display: inline-block; padding: 2px 8px; }
        h1 { font-size: 18px; margin: 12px 0 8px; }
        p { font-size: 14px; line-height: 1.5; color: #52525b; margin: 0 0 24px; }
        .actions { display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; }
        .btn { font: inherit; font-size: 14px; font-weight: 600; padding: 10px 16px; border-radius: 6px; text-decoration: none; cursor: pointer; }
        .btn-primary { background: #0b7840; color: #fff; border: 1px solid #0b7840; }
        .btn-primary:hover { background: #09663a; }
        .btn-secondary { background: #fff; color: #18181b; border: 1px solid #d4d4d4; }
        .btn-secondary:hover { background: #fafafa; }
        .btn:focus-visible { outline: 2px solid #0b7840; outline-offset: 2px; }
    </style>
</head>
<body>
    <main class="card">
        <div class="logo"><img src="{{ asset('images/cps-logo.png') }}" alt="PT Catur Pilar Sejahtera"></div>
        <span class="code">{{ $code }}</span>
        <h1>{{ $code === '404' ? 'Halaman tidak ditemukan' : 'Tidak dapat membuka halaman' }}</h1>
        <p>{{ $message }}</p>
        <div class="actions">
            <button type="button" class="btn btn-secondary" onclick="history.length > 1 ? history.back() : location.assign('{{ url('/dashboard') }}')">Kembali</button>
            <a href="{{ url('/dashboard') }}" class="btn btn-primary">Ke Dashboard</a>
        </div>
    </main>
</body>
</html>
