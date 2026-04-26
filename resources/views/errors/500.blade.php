<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terjadi Kesalahan Sistem | Kebab SK</title>
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center px-4">
    <div class="max-w-lg w-full bg-white rounded-2xl border border-slate-200 shadow-sm p-8 text-center">
        <div class="text-5xl mb-4">❌</div>
        <h1 class="text-xl font-semibold text-slate-800">Terjadi Kesalahan Sistem</h1>
        <p class="text-sm text-slate-500 mt-3">
            Sistem mengalami gangguan yang tidak terduga. Tim kami sudah mengetahui masalah ini.
            Silakan coba muat ulang halaman atau kembali ke halaman sebelumnya.
        </p>
        <div class="mt-6 flex items-center justify-center gap-3">
            <button onclick="window.location.reload()"
               class="inline-flex items-center px-4 py-2 rounded-xl bg-blue-600 text-white text-sm hover:bg-blue-700 transition cursor-pointer">
                Muat Ulang
            </button>
            <a href="{{ url('/') }}"
               class="inline-flex items-center px-4 py-2 rounded-xl border border-slate-200 text-slate-700 text-sm hover:bg-slate-50 transition">
                Ke Beranda
            </a>
        </div>
    </div>
</body>
</html>
