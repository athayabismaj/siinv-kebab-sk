<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Kedaluwarsa | Kebab SK</title>
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center px-4">
    <div class="max-w-lg w-full bg-white rounded-2xl border border-slate-200 shadow-sm p-8 text-center">
        <div class="text-5xl mb-4">⏳</div>
        <h1 class="text-xl font-semibold text-slate-800">Halaman Kedaluwarsa</h1>
        <p class="text-sm text-slate-500 mt-3">
            Sesi atau token keamanan sudah kedaluwarsa.
            Anda akan diarahkan ke halaman login secara otomatis.
        </p>
        <div class="mt-6 flex items-center justify-center gap-3">
            <a href="{{ route('login') }}"
               class="inline-flex items-center px-4 py-2 rounded-xl bg-blue-600 text-white text-sm hover:bg-blue-700 transition cursor-pointer">
                Ke Masuk
            </a>
        </div>
    </div>
    <script>
        setTimeout(function () {
            window.location.href = "{{ route('login') }}";
        }, 1000);
    </script>
</body>
</html>
