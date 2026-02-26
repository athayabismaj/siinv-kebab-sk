<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kebab SK</title>
    @vite('resources/css/app.css')
</head>

<body class="h-full bg-slate-100 flex items-center justify-center px-4">

<div class="w-full max-w-6xl bg-white rounded-3xl shadow-2xl overflow-hidden 
            flex flex-col md:flex-row">

    {{-- LEFT PANEL --}}
    <div class="md:w-1/2 w-full bg-blue-600 text-white 
                flex flex-col justify-center items-center 
                p-10 md:p-16 text-center">

        <h2 class="text-3xl md:text-4xl font-bold mb-6">
            Welcome Back!
        </h2>

        <p class="text-sm md:text-base opacity-90 max-w-xs leading-relaxed">
            Sistem Inventory <br>
            <span class="font-semibold">Kebab SK</span>
        </p>

    </div>

    {{-- RIGHT PANEL --}}
    <div class="md:w-1/2 w-full p-8 md:p-16 flex flex-col justify-center">

        <h2 class="text-2xl font-semibold mb-8 text-slate-800">
            Login
        </h2>

        {{-- ALERT SUCCESS --}}
        @if(session('success'))
            <div class="mb-5 px-4 py-3 rounded-lg text-sm
                        bg-emerald-50 text-emerald-700
                        border border-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        {{-- ALERT ERROR --}}
        @if(session('error'))
            <div class="mb-5 px-4 py-3 rounded-lg text-sm
                        bg-red-50 text-red-600
                        border border-red-200">
                {{ session('error') }}
            </div>
        @endif

        {{-- VALIDATION ERRORS --}}
        @if ($errors->any())
            <div class="mb-5 px-4 py-3 rounded-lg text-sm
                        bg-red-50 text-red-600
                        border border-red-200">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login.process') }}" 
              class="space-y-6">
            @csrf

            {{-- Username --}}
            <div>
                <label class="block text-sm text-slate-600 mb-2">
                    Username
                </label>
                <input type="text"
                       name="username"
                       value="{{ old('username') }}"
                       required
                       class="w-full px-4 py-3 rounded-xl 
                              border border-slate-300
                              focus:outline-none 
                              focus:ring-2 
                              focus:ring-blue-500 
                              focus:border-blue-500
                              transition">
            </div>

            {{-- Password --}}
            <div>
                <label class="block text-sm text-slate-600 mb-2">
                    Password
                </label>
                <input type="password"
                       name="password"
                       required
                       class="w-full px-4 py-3 rounded-xl 
                              border border-slate-300
                              focus:outline-none 
                              focus:ring-2 
                              focus:ring-blue-500 
                              focus:border-blue-500
                              transition">
            </div>

            <div class="text-right text-sm">
                <a href="#" 
                   class="text-slate-500 hover:text-blue-600 transition">
                    Forgot password?
                </a>
            </div>

            {{-- Button --}}
            <button type="submit"
                    class="w-full bg-blue-600 text-white 
                           py-3 rounded-xl font-medium
                           hover:bg-blue-700
                           active:scale-[0.98]
                           transition duration-200">
                Login
            </button>

        </form>

        <div class="mt-10 text-center text-xs text-slate-400">
            © {{ date('Y') }} Kebab SK
        </div>

    </div>

</div>

</body>
</html>