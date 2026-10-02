<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SIAP - {{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen flex bg-[#F7FAFB]">

        <aside class="w-60 bg-white border-r border-gray-100 flex-shrink-0 hidden md:flex md:flex-col">
            <div class="flex items-center gap-3 px-6 py-6 border-b border-gray-100">
                <div class="w-9 h-9 rounded-lg bg-primary text-white flex items-center justify-center font-semibold text-sm">SP</div>
                <div>
                    <div class="font-semibold text-sm text-gray-800">SIAP</div>
                    <div class="text-[11px] text-gray-400">Diskominfo Sawahlunto</div>
                </div>
            </div>
            <nav class="flex-1 px-3 py-4 space-y-1">
                <a href="{{ route('dashboard') }}"
                   class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-primary text-white' : 'text-gray-500 hover:bg-primary-light hover:text-primary-dark' }}">
                    Dashboard
                </a>
                @if (in_array(auth()->user()->role, ['admin', 'pemilik', 'atasan']))
                <a href="{{ route('team.index') }}"
                   class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('team.*') ? 'bg-primary text-white' : 'text-gray-500 hover:bg-primary-light hover:text-primary-dark' }}">
                    Tim Saya
                </a>
                @endif
                <a href="{{ route('announcements.index') }}"
                   class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('announcements.*') ? 'bg-primary text-white' : 'text-gray-500 hover:bg-primary-light hover:text-primary-dark' }}">
                    Pengumuman
                </a>
                <a href="{{ route('letters.index') }}"
                   class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('letters.*') ? 'bg-primary text-white' : 'text-gray-500 hover:bg-primary-light hover:text-primary-dark' }}">
                    Surat &amp; Dokumen
                </a>
                <a href="{{ route('tasks.index') }}"
                   class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('tasks.*') ? 'bg-primary text-white' : 'text-gray-500 hover:bg-primary-light hover:text-primary-dark' }}">
                    Workspace
                </a>
                <a href="{{ route('exams.index') }}"
                   class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('exams.*') ? 'bg-primary text-white' : 'text-gray-500 hover:bg-primary-light hover:text-primary-dark' }}">
                    Ujian
                </a>
                @if (in_array(auth()->user()->role, ['admin', 'pemilik']))
                <a href="{{ route('users.index') }}"
                   class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('users.*') ? 'bg-primary text-white' : 'text-gray-500 hover:bg-primary-light hover:text-primary-dark' }}">
                    Kelola Pengguna
                </a>
                @endif
                @if (auth()->user()->role === 'admin')
                <a href="{{ route('activity-log.index') }}"
                   class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('activity-log.*') ? 'bg-primary text-white' : 'text-gray-500 hover:bg-primary-light hover:text-primary-dark' }}">
                    Log Aktivitas
                </a>
                @endif
            </nav>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <header class="flex items-center justify-between px-8 py-4 border-b border-gray-100 bg-white">
                <div>
                    @isset($header)
                        <div class="font-semibold text-lg text-gray-800">{{ $header }}</div>
                    @endisset
                </div>

                <div class="flex items-center gap-3">
                    <x-notification-bell />
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" @click.outside="open = false"
                                class="flex items-center gap-2 text-sm text-gray-600">
                            <div class="w-8 h-8 rounded-full bg-accent-light text-accent flex items-center justify-center font-semibold text-xs">
                                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                            </div>
                            {{ auth()->user()->name }}
                        </button>
                        <div x-show="open" x-cloak
                             class="absolute right-0 mt-2 w-44 bg-white rounded-lg shadow-lg border border-gray-100 py-1 text-sm z-50">
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-gray-600 hover:bg-primary-light">Profil</a>
                            <a href="{{ route('notifications.index') }}" class="block px-4 py-2 text-gray-600 hover:bg-primary-light">Notifikasi</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-gray-600 hover:bg-primary-light">Keluar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 px-8 py-6 overflow-y-auto">
                @if (session('status'))
                    <div class="mb-4 p-3 bg-green-50 text-green-700 rounded-lg text-sm">{{ session('status') }}</div>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Chatbot floating widget --}}
    @auth
        <x-chatbot-widget />
    @endauth
</body>
</html>
