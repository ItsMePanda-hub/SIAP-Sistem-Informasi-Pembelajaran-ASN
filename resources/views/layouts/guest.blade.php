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
    <div class="min-h-screen flex flex-col items-center justify-center bg-[#F7FAFB] px-4">

        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-lg bg-primary text-white flex items-center justify-center font-semibold font-display">SP</div>
            <div>
                <div class="font-display font-semibold text-gray-800 leading-tight">SIAP</div>
                <div class="text-[11px] text-gray-400">Diskominfo Sawahlunto</div>
            </div>
        </div>

        <div class="w-full sm:max-w-md bg-white rounded-2xl shadow-sm border border-gray-100 px-8 py-8">
            {{ $slot }}
        </div>

    </div>
</body>
</html>
