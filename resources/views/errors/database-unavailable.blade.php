<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layanan Database Bermasalah</title>
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center px-4">
    <div class="max-w-lg w-full bg-white rounded-2xl border border-slate-200 shadow-sm p-8 text-center">
        <h1 class="text-xl font-semibold text-slate-800">Koneksi Database Bermasalah</h1>
        <p class="text-sm text-slate-500 mt-3">
            Sistem sedang kesulitan terhubung ke database. Silakan coba lagi beberapa saat.
        </p>
        <div class="mt-6">
            <a href="{{ url()->current() }}"
               class="inline-flex items-center px-4 py-2 rounded-xl bg-blue-600 text-white text-sm hover:bg-blue-700 transition">
                Coba Lagi
            </a>
        </div>
    </div>
</body>
</html>
