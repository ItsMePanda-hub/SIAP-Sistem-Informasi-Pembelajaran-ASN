<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIAP — Sistem Informasi & Pembelajaran ASN</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-[#F7FAFB] text-gray-800">
    <nav class="bg-white border-b border-gray-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-primary text-white flex items-center justify-center font-semibold text-sm">SI</div>
                <span class="font-display font-bold text-lg text-primary">SIAP</span>
            </div>
            <a href="{{ route('login') }}" class="inline-flex items-center px-5 py-2 rounded-lg bg-primary text-white text-sm font-semibold hover:bg-primary-dark transition">Masuk</a>
        </div>
    </nav>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
        <div class="max-w-3xl">
            <h1 class="font-display font-bold text-3xl sm:text-4xl lg:text-5xl text-gray-900 leading-tight">SIAP — Sistem Informasi & Pembelajaran ASN</h1>
            <p class="mt-4 text-base sm:text-lg text-gray-500 leading-relaxed">Portal internal Diskominfo Kota Sawahlunto untuk pengumuman, surat dinas, workspace/tugas, pelatihan, dan ujian pegawai dalam satu tempat yang terpadu.</p>
            <div class="mt-8">
                <a href="{{ route('login') }}" class="inline-flex items-center px-8 py-3 rounded-xl bg-primary text-white font-semibold hover:bg-primary-dark transition shadow-sm">Masuk ke SIAP</a>
            </div>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
        <h2 class="font-display font-semibold text-xl text-gray-800 mb-6">Fitur Utama</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-gray-100 p-6">
                <div class="text-2xl mb-3">📢</div>
                <h3 class="font-semibold text-gray-800">Pengumuman</h3>
                <p class="text-sm text-gray-500 mt-1">Sebaran informasi resmi dan nota dinas ke seluruh pegawai.</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-6">
                <div class="text-2xl mb-3">📄</div>
                <h3 class="font-semibold text-gray-800">Surat & Dokumen</h3>
                <p class="text-sm text-gray-500 mt-1">Kelola dan unduh SK, surat tugas, dan dokumen dinas digital.</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-6">
                <div class="text-2xl mb-3">💼</div>
                <h3 class="font-semibold text-gray-800">Workspace</h3>
                <p class="text-sm text-gray-500 mt-1">Distribusi tugas, pengumpulan hasil kerja, dan review atasan.</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-6">
                <div class="text-2xl mb-3">📝</div>
                <h3 class="font-semibold text-gray-800">Ujian</h3>
                <p class="text-sm text-gray-500 mt-1">Ujian kompetensi pilihan ganda dan esai dengan penilaian terstruktur.</p>
            </div>
        </div>
    </section>

    <section class="bg-white border-y border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <h2 class="font-display font-semibold text-xl text-gray-800">Dinas Komunikasi dan Informatika Kota Sawahlunto</h2>
            <!-- TODO: sesuaikan deskripsi instansi sesuai profil resmi Diskominfo -->
            <p class="mt-3 text-sm text-gray-500 leading-relaxed max-w-3xl">Diskominfo Kota Sawahlunto bertanggung jawab atas pengelolaan informasi publik, infrastruktur teknologi, dan transformasi digital pelayanan pemerintahan untuk mendukung kinerja ASN yang profesional dan akuntabel.</p>
        </div>
    </section>

    <footer class="bg-white border-t border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-sm text-gray-500">
            <span>&copy; {{ date('Y') }} Diskominfo Kota Sawahlunto</span>
            <!-- TODO: tambahkan kontak/alamat jika diperlukan -->
            <span class="text-xs">SIAP v1.0</span>
        </div>
    </footer>
</body>
</html>
