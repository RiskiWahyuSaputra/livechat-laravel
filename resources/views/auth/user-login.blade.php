<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LiveChat</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/best-logo-1.png') }}">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Load Tailwind CSS via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Inter', sans-serif; }
        
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active{
            -webkit-box-shadow: 0 0 0 30px white inset !important;
            -webkit-text-fill-color: #0f2a4a !important;
        }

        .eye-icon { transition: color 0.15s ease; }
        .eye-icon:hover { color: #0f2a4a; cursor: pointer; }
        
        .fade-in-up {
            animation: fadeInUp 0.4s ease-out forwards;
            opacity: 0;
            transform: translateY(15px);
        }
        @keyframes fadeInUp {
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800 antialiased selection:bg-[#d11f26] selection:text-white flex items-center justify-center p-4 sm:p-6 lg:p-8">

    <div class="w-full max-w-md bg-white rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] ring-1 ring-slate-100 overflow-hidden fade-in-up">
        
        <!-- Top Colored Border Accent -->
        <div class="flex h-1.5 w-full">
            <div class="w-1/2 bg-[#0f2a4a]"></div> <!-- Navy Blue -->
            <div class="w-1/2 bg-[#d11f26]"></div> <!-- Red -->
        </div>

        <div class="px-8 py-10 sm:px-10">
            <!-- Header & Logo -->
            <div class="flex flex-col items-center mb-8">
                <!-- Placeholder untuk Logo Asli -->
                <div class="h-14 mb-4 flex justify-center items-center relative group">
                    <!-- Ganti 'images/logo.png' dengan path dan nama file logo Anda -->
                    <img src="{{ asset('images/best-logo-1.png') }}" alt="Logo" class="max-h-full max-w-[120px] object-contain drop-shadow-sm" onerror="this.outerHTML='<div class=\'text-xs text-slate-400 border border-dashed border-slate-300 rounded p-1.5 text-center\'>Logo<br/>Not Found</div>'">
                </div>

                <h2 class="text-2xl font-bold tracking-tight text-[#0f2a4a]">Selamat datang!</h2>
                <p class="mt-2 text-[14px] text-slate-500 font-medium">Masuk untuk memulai obrolan.</p>
            </div>

            <!-- Authentic Tabs -->
            <div class="flex mb-8 border-b border-slate-200">
                <a href="{{ route('user.login') }}" class="w-1/2 py-3 text-center text-sm font-semibold text-[#0f2a4a] border-b-2 border-[#d11f26]">
                    Masuk
                </a>
                <a href="{{ route('user.register') }}" class="w-1/2 py-3 text-center text-sm font-medium text-slate-500 hover:text-slate-800 transition-colors">
                    Daftar
                </a>
            </div>

            @if(session('error'))
                <div class="mb-6 rounded-lg bg-red-50 p-4 border border-red-100 flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3 text-sm text-red-700">
                        {{ session('error') }}
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('user.login') }}" class="space-y-6">
                @csrf
                
                <!-- Email Field -->
                <div>
                    <label for="email" class="block text-sm font-semibold leading-6 text-[#0f2a4a]">Alamat Email</label>
                    <div class="mt-2">
                        <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" required autofocus
                            class="block w-full rounded-lg border-0 py-2.5 px-3.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-[#d11f26] sm:text-sm sm:leading-6 transition-colors duration-200" 
                            placeholder="email@kamu.com">
                    </div>
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password Field -->
                <div>
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-sm font-semibold leading-6 text-[#0f2a4a]">Password</label>
                        <a href="#" class="text-xs font-semibold text-[#0f2a4a] hover:text-[#d11f26] transition-colors">Lupa password?</a>
                    </div>
                    <div class="mt-2 relative">
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                            class="block w-full rounded-lg border-0 py-2.5 pl-3.5 pr-10 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-[#d11f26] sm:text-sm sm:leading-6 transition-colors duration-200" 
                            placeholder="••••••••">
                            
                        <!-- Eye Icon -->
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                            <button type="button" tabindex="-1" class="text-slate-400 hover:text-[#0f2a4a] focus:outline-none transition-colors" onclick="togglePassword('password', 'eyeOpenLogin', 'eyeClosedLogin')">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5" id="eyeOpenLogin">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 hidden" id="eyeClosedLogin">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit" 
                        class="flex w-full justify-center rounded-lg bg-[#d11f26] px-3 py-2.5 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-[#b01a20] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#d11f26] transition-all duration-200">
                        Masuk & Mulai Chat
                    </button>
                </div>
            </form>
            
        </div>
        
    </div>

    <!-- Password Visibility Script -->
    <script>
        function togglePassword(inputId, openIconId, closedIconId) {
            const pwd = document.getElementById(inputId);
            const eyeOpen = document.getElementById(openIconId);
            const eyeClosed = document.getElementById(closedIconId);
            
            if (pwd.type === 'password') {
                pwd.type = 'text';
                eyeOpen.classList.add('hidden');
                eyeClosed.classList.remove('hidden');
            } else {
                pwd.type = 'password';
                eyeOpen.classList.remove('hidden');
                eyeClosed.classList.add('hidden');
            }
        }
    </script>
</body>
</html>
