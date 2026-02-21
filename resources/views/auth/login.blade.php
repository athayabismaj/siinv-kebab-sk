<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Kebab SK</title>
    @vite('resources/css/app.css')
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center px-4">

<div class="w-full max-w-5xl bg-white rounded-3xl shadow-2xl overflow-hidden 
            flex flex-col md:flex-row">

    {{-- LEFT PANEL --}}
    <div class="md:w-1/2 w-full bg-blue-600 text-white 
                flex flex-col justify-center items-center 
                p-10 md:p-14 text-center">

        <h2 class="text-3xl md:text-4xl font-bold mb-4">
            Welcome Back!
        </h2>

        <p class="text-sm md:text-base opacity-90 max-w-xs">
            Sistem Inventory
            <br>
            Kebab SK
        </p>

    </div>

    {{-- RIGHT PANEL --}}
    <div class="md:w-1/2 w-full p-8 md:p-14 flex flex-col justify-center">

        <h2 class="text-2xl font-bold mb-8 text-gray-800">
            Login
        </h2>

        <form method="POST" action="{{ route('login.process') }}" 
              class="space-y-5">
            @csrf

            {{-- Username --}}
            <div>
                <label class="block text-sm text-gray-600 mb-2">
                    Username
                </label>
                <input type="text"
                       name="username"
                       required
                       class="w-full px-4 py-3 rounded-lg 
                              border border-gray-200 
                              focus:outline-none 
                              focus:ring-2 
                              focus:ring-blue-500 
                              focus:border-transparent
                              transition">
            </div>

            {{-- Password --}}
            <div>
                <label class="block text-sm text-gray-600 mb-2">
                    Password
                </label>
                <input type="password"
                       name="password"
                       required
                       class="w-full px-4 py-3 rounded-lg 
                              border border-gray-200 
                              focus:outline-none 
                              focus:ring-2 
                              focus:ring-blue-500 
                              focus:border-transparent
                              transition">
            </div>

            <div class="text-right text-sm">
                <a href="#" 
                   class="text-gray-500 hover:text-blue-600 transition">
                    Forgot password?
                </a>
            </div>

            {{-- Button --}}
            <button type="submit"
                    class="w-full bg-blue-600 text-white 
                           py-3 rounded-lg font-semibold
                           hover:bg-blue-700
                           active:scale-[0.98]
                           transition duration-200">
                Login
            </button>

        </form>

        <div class="mt-8 text-center text-xs text-gray-400">
            © {{ date('Y') }} Kebab SK
        </div>

    </div>

</div>

</body>
</html>