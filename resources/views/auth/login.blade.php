<!DOCTYPE html>
<html lang="id" class="h-full antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk | Kebab SK</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="font-sans antialiased h-full bg-slate-100 dark:bg-slate-950 m-0 p-0 selection:bg-blue-500 selection:text-white">

    <div class="min-h-screen w-full">
        <div class="flex w-full min-h-screen overflow-hidden bg-white dark:bg-slate-900">

            <div class="hidden xl:flex xl:w-[50%] relative overflow-hidden bg-gradient-to-br from-blue-700 via-blue-600 to-cyan-500">
                <div class="absolute top-1/4 right-1/4 w-40 h-40 rounded-full bg-gradient-to-tr from-cyan-300 to-blue-300 opacity-50 blur-[2px]"></div>
                <div class="absolute bottom-0 left-0 w-96 h-96 bg-cyan-300 rounded-full mix-blend-screen filter blur-[100px] opacity-40"></div>
                <div class="absolute top-1/3 left-10 w-64 h-64 bg-blue-400 rounded-full mix-blend-screen filter blur-[80px] opacity-50"></div>

                <svg class="absolute inset-0 w-full h-full opacity-20" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <path d="M0,50 C30,60 40,20 100,50 L100,100 L0,100 Z" fill="url(#grad)"/>
                    <path d="M0,60 C40,70 60,10 100,60 L100,100 L0,100 Z" fill="none" stroke="white" stroke-width="0.2"/>
                    <path d="M0,62 C40,72 60,12 100,62 L100,100 L0,100 Z" fill="none" stroke="white" stroke-width="0.2"/>
                    <path d="M0,64 C40,74 60,14 100,64 L100,100 L0,100 Z" fill="none" stroke="white" stroke-width="0.2"/>
                    <defs>
                        <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#38bdf8" stop-opacity="0.3" />
                            <stop offset="100%" stop-color="#2563eb" stop-opacity="0.6" />
                        </linearGradient>
                    </defs>
                </svg>

                <div class="absolute bottom-10 left-10 text-white/80 text-xs tracking-[0.2em] font-bold uppercase">
                    Sistem Manajemen Inventory
                </div>
            </div>

            <div class="w-full xl:w-[50%] min-h-screen xl:min-h-[calc(100vh-3rem)] flex flex-col justify-center items-center bg-white dark:bg-slate-900 p-8 sm:p-12 xl:p-16 relative overflow-y-auto">
                <div class="w-full max-w-[540px]">

                    <div class="mb-10 font-bold text-sm text-slate-800 dark:text-slate-200 flex items-center gap-2.5">
                        <div class="h-7 w-7 overflow-hidden rounded-lg bg-white ring-1 ring-slate-200 shadow-sm shadow-slate-200/70 dark:bg-slate-800 dark:ring-slate-700 dark:shadow-none">
                            <img
                                src="{{ asset('images/kebab-sk-logo-report.jpeg') }}"
                                alt="Logo Kebab SK"
                                class="h-full w-full object-cover">
                        </div>
                        Kebab SK
                    </div>

                    <div class="mb-8">
                        <h2 class="text-3xl xl:text-4xl font-light text-slate-500 dark:text-slate-400 leading-tight">Hello,</h2>
                        <h3 class="text-4xl xl:text-5xl font-black text-blue-600 dark:text-blue-400 tracking-tight leading-tight">selamat datang!</h3>
                    </div>

                    @include('partials.flash_alerts', ['includeErrors' => true])

                    <form method="POST" action="{{ route('login.process') }}" class="space-y-5" x-data="{ submitting: false }" @submit="submitting = true">
                        @csrf

                        <div class="border border-slate-200 dark:border-slate-700 rounded-xl flex flex-col focus-within:border-blue-500 dark:focus-within:border-blue-400 focus-within:ring-4 focus-within:ring-blue-500/10 transition-all bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
                            <div class="relative border-b border-slate-100 dark:border-slate-800">
                                <label class="absolute top-2.5 left-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    Username
                                </label>
                                <input type="text"
                                       name="username"
                                       value="{{ old('username') }}"
                                       placeholder="Masukkan username"
                                       required
                                       autocomplete="username"
                                       class="w-full pt-7 pb-2.5 px-4 bg-transparent border-none text-[14px] font-medium text-slate-900 dark:text-white focus:ring-0 focus:outline-none placeholder-slate-300 dark:placeholder-slate-600">
                            </div>

                            <div class="relative">
                                <label class="absolute top-2.5 left-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    Kata Sandi
                                </label>
                                <input type="password"
                                       id="login_password"
                                       name="password"
                                       placeholder="********"
                                       required
                                       autocomplete="current-password"
                                       class="w-full pt-7 pb-2.5 pl-4 pr-14 bg-transparent border-none text-[14px] font-medium text-slate-900 dark:text-white focus:ring-0 focus:outline-none placeholder-slate-300 dark:placeholder-slate-600">
                                <button type="button"
                                        id="toggle_login_password"
                                        aria-label="Tampilkan password"
                                        aria-pressed="false"
                                        class="login-password-toggle z-10 rounded-md text-slate-400 transition-colors hover:text-blue-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40 dark:hover:text-blue-400">
                                    <svg id="login_password_icon_hidden" class="login-password-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                    </svg>
                                    <svg id="login_password_icon_visible" class="login-password-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" hidden>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-1">
                            <a href="{{ route('password.request') }}" class="text-[12px] text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 font-medium transition-colors">
                                Lupa kata sandi?
                            </a>
                        </div>

                        <div class="pt-4">
                            <button type="submit"
                                    aria-label="Login"
                                    :disabled="submitting"
                                    class="w-full py-3 bg-blue-600 text-white text-[14px] font-bold rounded-lg hover:bg-blue-700 active:scale-95 transition-all shadow-md shadow-blue-500/30 flex items-center justify-center">
                                <span x-show="!submitting">Masuk</span>
                                <svg x-show="submitting" x-cloak class="h-5 w-5 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </button>
                        </div>
                    </form>

                </div>
            </div>

        </div>
    </div>

</body>
</html>
