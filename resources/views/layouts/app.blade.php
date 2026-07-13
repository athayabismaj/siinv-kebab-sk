<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php $pageTitle = trim($__env->yieldContent('title')); @endphp
    <title>{{ $pageTitle !== '' ? $pageTitle . ' | Kebab SK' : 'Kebab SK | Sistem Inventory' }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @stack('styles')
    <style>
        [data-app-main] nav[class*="uppercase"][class*="tracking"] {
            display: flex;
            align-items: center;
            gap: .5rem;
            margin: 0 0 .75rem;
            padding-bottom: .25rem;
            overflow-x: auto;
            color: rgb(148 163 184);
            font-size: 11px;
            line-height: 1rem;
            font-weight: 800;
            letter-spacing: .14em;
            text-transform: uppercase;
            scrollbar-width: none;
        }

        [data-app-main] nav[class*="uppercase"][class*="tracking"]::-webkit-scrollbar {
            display: none;
        }

        [data-app-main] nav[class*="uppercase"][class*="tracking"] a,
        [data-app-main] nav[class*="uppercase"][class*="tracking"] span {
            white-space: nowrap;
        }

        [data-app-main] nav[class*="uppercase"][class*="tracking"] a {
            transition: color .2s ease;
        }

        [data-app-main] nav[class*="uppercase"][class*="tracking"] a:hover,
        [data-app-main] nav[class*="uppercase"][class*="tracking"] span:last-child {
            color: rgb(37 99 235);
        }

        .dark [data-app-main] nav[class*="uppercase"][class*="tracking"] {
            color: rgb(100 116 139);
        }

        .dark [data-app-main] nav[class*="uppercase"][class*="tracking"] a:hover,
        .dark [data-app-main] nav[class*="uppercase"][class*="tracking"] span:last-child {
            color: rgb(96 165 250);
        }

        [data-app-main] nav[class*="uppercase"][class*="tracking"] ~ h1,
        [data-app-main] nav[class*="uppercase"][class*="tracking"] + div h1:first-of-type {
            margin: 0;
            color: rgb(15 23 42);
            font-size: 1.625rem;
            line-height: 2rem;
            font-weight: 900;
            letter-spacing: 0;
        }

        .dark [data-app-main] nav[class*="uppercase"][class*="tracking"] ~ h1,
        .dark [data-app-main] nav[class*="uppercase"][class*="tracking"] + div h1:first-of-type {
            color: rgb(255 255 255);
        }

        [data-app-main] nav[class*="uppercase"][class*="tracking"] ~ h1 + p,
        [data-app-main] nav[class*="uppercase"][class*="tracking"] ~ p:first-of-type,
        [data-app-main] nav[class*="uppercase"][class*="tracking"] + div h1:first-of-type + p {
            max-width: 48rem;
            margin-top: .5rem;
            color: rgb(100 116 139);
            font-size: .875rem;
            line-height: 1.625rem;
            font-weight: 500;
        }

        .dark [data-app-main] nav[class*="uppercase"][class*="tracking"] ~ h1 + p,
        .dark [data-app-main] nav[class*="uppercase"][class*="tracking"] ~ p:first-of-type,
        .dark [data-app-main] nav[class*="uppercase"][class*="tracking"] + div h1:first-of-type + p {
            color: rgb(148 163 184);
        }

        @media (max-width: 640px) {
            [data-app-main] nav[class*="uppercase"][class*="tracking"] {
                font-size: 10px;
                letter-spacing: .12em;
            }

            [data-app-main] nav[class*="uppercase"][class*="tracking"] ~ h1,
            [data-app-main] nav[class*="uppercase"][class*="tracking"] + div h1:first-of-type {
                font-size: 1.5rem;
                line-height: 1.875rem;
            }
        }
    </style>
</head>

<body class="font-sans antialiased selection:bg-blue-500/30 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-200 overflow-x-hidden relative transition-colors duration-300" style="height: 100dvh; display: flex; flex-direction: column;">

{{-- DECORATIVE BACKGROUND BLOBS --}}
<div class="fixed inset-0 z-0 pointer-events-none overflow-hidden">
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-blue-500/5 dark:bg-blue-500/10 blur-3xl mix-blend-multiply dark:mix-blend-lighten"></div>
    <div class="absolute bottom-[-10%] right-[-5%] w-[35%] h-[35%] rounded-full bg-emerald-500/5 dark:bg-emerald-500/10 blur-3xl mix-blend-multiply dark:mix-blend-lighten"></div>
</div>

<div x-data="{ sidebarOpen: false }" class="flex flex-1 w-full relative z-10" style="min-height: 0; overflow: hidden;">

    {{-- OVERLAY MOBILE --}}
    <div
        x-show="sidebarOpen"
        @click="sidebarOpen = false"
        class="fixed inset-0 bg-black/40 md:hidden z-40">
    </div>

    {{-- SIDEBAR --}}
    @php
        $roleName = strtolower(optional(optional(auth()->user())->role)->name ?? '');
        $useOwnerSidebar = $roleName === 'owner' || request()->routeIs('owner.*');
        $useDeveloperSidebar = $roleName === 'developer' || request()->routeIs('developer.*');
    @endphp

    @if($useDeveloperSidebar)
        @include('partials.sidebar_developer')
    @elseif($useOwnerSidebar)
        @include('partials.sidebar_owner')
    @else
        @include('partials.sidebar_admin')
    @endif

    {{-- RIGHT SIDE --}}
    <div class="flex-1 flex flex-col w-full" style="min-height: 0; overflow: hidden;">

        {{-- HEADER --}}
        <div class="shrink-0">
            @include('partials.header')
        </div>

        {{-- SCROLLABLE CONTENT --}}
        <main data-app-main class="flex-1 overflow-y-auto p-6 md:p-8" style="min-height: 0;">
            @unless(trim($__env->yieldContent('disableGlobalAlerts')) === 'true')
                @include('partials.flash_alerts', ['class' => 'w-full space-y-2', 'position' => 'global'])
            @endunless
            @yield('content')
        </main>

        {{-- FOOTER (always pinned at bottom) --}}
        <div class="shrink-0 relative z-20">
            @include('partials.footer')
        </div>

    </div>

</div>

@stack('scripts')
<script>
    (() => {
        const moveGlobalFlashAlerts = () => {
            const main = document.querySelector('[data-app-main]');
            if (!main) return;

            const alert = Array.from(main.children).find((element) => element.dataset.flashAlerts === 'global');
            if (!alert) return;

            const pageRoot = Array.from(main.children).find((element) => element !== alert);
            if (!pageRoot) return;

            const rootClass = typeof pageRoot.className === 'string' ? pageRoot.className : '';
            const isPageWrapper = rootClass.includes('space-y') || rootClass.includes('-page') || pageRoot.dataset.pageRoot === 'true';
            const header = pageRoot.querySelector('[data-page-header]') || (isPageWrapper ? Array.from(pageRoot.children)[0] : pageRoot);
            if (!header) return;

            header.insertAdjacentElement('afterend', alert);
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', moveGlobalFlashAlerts, { once: true });
        } else {
            moveGlobalFlashAlerts();
        }

        // Global interceptor for native window.confirm triggered via onclick="return confirm('...')"
        document.addEventListener('click', function(e) {
            const target = e.target.closest('[onclick*="confirm("]');
            if (target) {
                e.preventDefault();
                e.stopPropagation();
                
                const onclickCode = target.getAttribute('onclick');
                const match = onclickCode.match(/confirm\(\s*['"](.*?)['"]\s*\)/);
                const message = match ? match[1] : 'Apakah Anda yakin ingin melanjutkan?';

                // Temporarily remove onclick to prevent loop if we trigger click programmatically
                target.removeAttribute('onclick');

                Swal.fire({
                    title: 'Konfirmasi',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#2563eb', // text-blue-600
                    cancelButtonColor: '#ef4444', // text-red-500
                    confirmButtonText: 'Ya, Lanjutkan',
                    cancelButtonText: 'Batal',
                    customClass: {
                        popup: 'rounded-3xl dark:bg-slate-900 dark:border dark:border-slate-800 shadow-xl',
                        title: 'text-lg font-bold text-slate-800 dark:text-white',
                        htmlContainer: 'text-sm font-medium text-slate-500 dark:text-slate-400',
                        confirmButton: 'rounded-xl px-5 py-2.5 font-bold shadow-sm focus:ring-4 focus:ring-blue-500/20',
                        cancelButton: 'rounded-xl px-5 py-2.5 font-bold shadow-sm bg-slate-100 text-slate-700 hover:bg-slate-200 focus:ring-4 focus:ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:focus:ring-slate-700'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (target.tagName.toLowerCase() === 'button' && target.type === 'submit') {
                            const form = target.closest('form');
                            if (form) form.submit();
                        } else if (target.tagName.toLowerCase() === 'a' && target.href) {
                            window.location.href = target.href;
                        } else {
                            target.click();
                        }
                    } else {
                        // Restore onclick if user cancels
                        target.setAttribute('onclick', onclickCode);
                    }
                });
            }
        }, true);
    })();
</script>
</body>
</html>
