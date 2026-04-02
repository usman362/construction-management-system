<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $companyName = \App\Models\Setting::get('company_name', 'BuildTrack');
        $companyTagline = \App\Models\Setting::get('company_tagline', 'Construction Mgmt');
        $companyLogo = \App\Models\Setting::get('company_logo');
        $appFavicon = \App\Models\Setting::get('favicon');
    @endphp
    <title>Login - {{ $companyName }}</title>
    @if($appFavicon)
    <link rel="icon" href="{{ $appFavicon }}" type="image/png">
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center px-4">

    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-2xl mb-4 overflow-hidden">
                @if($companyLogo)
                    <img src="{{ $companyLogo }}" class="w-full h-full object-contain p-1" alt="{{ $companyName }}">
                @else
                    <span class="text-2xl font-bold text-white">{{ strtoupper(substr($companyName, 0, 2)) }}</span>
                @endif
            </div>
            <h1 class="text-3xl font-bold text-white">{{ $companyName }}</h1>
            <p class="text-gray-400 mt-1">{{ $companyTagline }}</p>
        </div>

        <!-- Login Card -->
        <div class="bg-gray-800 rounded-2xl shadow-2xl p-8 border border-gray-700">
            <h2 class="text-xl font-semibold text-white mb-6">Sign in to your account</h2>

            @if($errors->any())
                <div class="bg-red-500/10 border border-red-500/30 text-red-400 rounded-lg px-4 py-3 mb-6 text-sm">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            @if(session('success'))
                <div class="bg-green-500/10 border border-green-500/30 text-green-400 rounded-lg px-4 py-3 mb-6 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-300 mb-1.5">Email Address</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                           placeholder="admin@cms.com">
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-300 mb-1.5">Password</label>
                    <input type="password" id="password" name="password" required
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                           placeholder="Enter your password">
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded bg-gray-700 border-gray-600 text-blue-600 focus:ring-blue-500 focus:ring-offset-gray-800">
                        <span class="text-sm text-gray-400">Remember me</span>
                    </label>
                </div>

                <!-- Submit -->
                <button type="submit"
                        class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-800">
                    Sign In
                </button>
            </form>

            <!-- Register Link -->
            <div class="mt-6 text-center">
                <p class="text-gray-400 text-sm">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="text-blue-400 hover:text-blue-300 font-medium">Create one</a>
                </p>
            </div>
        </div>

        <!-- Default Credentials Hint -->
        <div class="mt-6 bg-gray-800/50 rounded-xl p-4 border border-gray-700/50">
            <p class="text-gray-500 text-xs text-center">
                Default credentials: <span class="text-gray-400">admin@cms.com</span> / <span class="text-gray-400">password</span>
            </p>
        </div>
    </div>

</body>
</html>
