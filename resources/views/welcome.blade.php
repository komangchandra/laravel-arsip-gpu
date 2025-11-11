<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PT. Gorby Putra Utama - Document Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-100">

    <div class="min-h-screen flex flex-col items-center justify-center p-6">
        {{-- Header --}}
        <header class="w-full max-w-5xl flex justify-between items-center mb-10">
            <div class="flex items-center space-x-3">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-12 h-12 rounded-lg shadow-md">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">PT. Gorby Putra Utama</h1>
            </div>
            @if (Route::has('login'))
                <div class="space-x-4">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="text-sm font-medium text-blue-600 hover:underline">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-gray-700 hover:text-blue-600">Login</a>
                        @if (Route::has('register'))
                            
                        @endif
                    @endauth
                </div>
            @endif
        </header>

        {{-- Main Content --}}
        <main class="flex flex-col lg:flex-row bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden max-w-5xl w-full">
            {{-- Left Content --}}
            <div class="flex-1 p-10">
                <h2 class="text-3xl font-semibold mb-3">Selamat Datang di Portal Dokumen</h2>
                <p class="text-gray-600 dark:text-gray-300 mb-6">
                    Sistem ini digunakan untuk pengelolaan dokumen internal PT. Gorby Putra Utama.  
                    Masuk ke akun Anda untuk mulai mengunggah, mengelola, dan meninjau dokumen perusahaan.
                </p>

                <ul class="space-y-3 mb-6 text-sm text-gray-700 dark:text-gray-300">
                    <li class="flex items-center gap-2">
                        <i class="bi bi-check-circle text-green-500"></i>
                        Keamanan data terenkripsi dan aman
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="bi bi-check-circle text-green-500"></i>
                        Akses cepat dan terpusat antar divisi
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="bi bi-check-circle text-green-500"></i>
                        Terintegrasi dengan sistem internal
                    </li>
                </ul>

                <div>
                    <a href="{{ route('login') }}" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-all">
                        Masuk Sekarang
                    </a>
                </div>
            </div>

            {{-- Right Illustration --}}
            <div class="lg:w-1/2 bg-gradient-to-br from-blue-100 to-blue-300 dark:from-gray-700 dark:to-gray-900 flex items-center justify-center p-6">
                <img src="{{ asset('images/document-illustration.svg') }}" alt="Documents Illustration" class="w-3/4">
            </div>
        </main>

        {{-- Footer --}}
        <footer class="mt-10 text-sm text-gray-500 dark:text-gray-400">
            © {{ date('Y') }} PT. Gorby Putra Utama. All rights reserved.
        </footer>
    </div>
</body>
</html>
