<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Tidak Ditemukan | Kebab SK</title>
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center px-4">
    <div class="max-w-lg w-full bg-white rounded-2xl border border-slate-200 shadow-sm p-8 text-center">
        <div class="text-5xl mb-4">🔍</div>
        <h1 class="text-xl font-semibold text-slate-800">Halaman Tidak Ditemukan</h1>
        <p class="text-sm text-slate-500 mt-3">
            Halaman yang Anda tuju tidak ada atau sudah dipindahkan.
            Silakan kembali ke halaman sebelumnya.
        </p>
        <div class="mt-6 flex items-center justify-center gap-3">
            <a href="{{ url('/') }}"
               class="inline-flex items-center px-4 py-2 rounded-xl bg-blue-600 text-white text-sm hover:bg-blue-700 transition">
                Ke Beranda
            </a>
            <a href="{{ url()->previous() }}"
               class="inline-flex items-center px-4 py-2 rounded-xl border border-slate-200 text-slate-700 text-sm hover:bg-slate-50 transition">
                Kembali
            </a>
        </div>
    </div>
</body>
</html>
