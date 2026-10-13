<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIAP — Sistem Informasi & Pembelajaran</title>
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
            <div class="hidden sm:flex items-center gap-6 text-sm text-gray-600">
                <a href="#tentang" class="hover:text-primary">Tentang</a>
                <a href="#fitur" class="hover:text-primary">Fitur</a>
                <a href="#cara-kerja" class="hover:text-primary">Cara Kerja</a>
                <a href="#pengguna" class="hover:text-primary">Pengguna</a>
            </div>
            <a href="{{ route('login') }}" class="inline-flex items-center px-5 py-2 rounded-lg bg-primary text-white text-sm font-semibold hover:bg-primary-dark transition">Masuk</a>
        </div>
    </nav>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
        <div class="max-w-3xl">
            <span class="inline-block px-3 py-1 rounded-full bg-accent-light text-accent text-xs font-semibold">Platform umum — instansi, organisasi, tim kerja</span>
            <h1 class="font-display font-bold text-3xl sm:text-4xl lg:text-5xl text-gray-900 leading-tight mt-4">SIAP — Sistem Informasi &amp; Pembelajaran</h1>
            <p class="mt-4 text-base sm:text-lg text-gray-500 leading-relaxed">Satu portal untuk informasi, dokumen, tugas, ujian, dan perpustakaan materi. Bisa dipakai instansi pemerintah, perusahaan, sekolah, maupun komunitas.</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('login') }}" class="inline-flex items-center px-8 py-3 rounded-xl bg-primary text-white font-semibold hover:bg-primary-dark transition shadow-sm">Masuk ke SIAP</a>
                <a href="#fitur" class="inline-flex items-center px-8 py-3 rounded-xl border border-gray-200 bg-white font-semibold text-gray-700 hover:border-primary hover:text-primary transition">Lihat Fitur</a>
            </div>
        </div>
    </section>

    <section id="tentang" class="bg-white border-y border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 grid gap-8 lg:grid-cols-2">
            <div>
                <h2 class="font-display font-semibold text-xl text-gray-800">Apa itu SIAP?</h2>
                <p class="mt-3 text-sm text-gray-500 leading-relaxed">SIAP menggabungkan alur kerja harian dan pembelajaran dalam satu tempat. Pimpinan dan atasan distribusikan info dan tugas, anggota terima, kerjakan, belajar, dan ikut ujian — semua tercatat dan terpantau.</p>
            </div>
            <div class="grid grid-cols-3 gap-4 text-center">
                <div class="rounded-xl bg-[#F7FAFB] border border-gray-100 p-4">
                    <div class="font-display font-bold text-2xl text-primary">5</div>
                    <div class="text-xs text-gray-500 mt-1">Modul utama</div>
                </div>
                <div class="rounded-xl bg-[#F7FAFB] border border-gray-100 p-4">
                    <div class="font-display font-bold text-2xl text-primary">3</div>
                    <div class="text-xs text-gray-500 mt-1">Peran pengguna</div>
                </div>
                <div class="rounded-xl bg-[#F7FAFB] border border-gray-100 p-4">
                    <div class="font-display font-bold text-2xl text-primary">1</div>
                    <div class="text-xs text-gray-500 mt-1">Satu pintu masuk</div>
                </div>
            </div>
        </div>
    </section>

    <section id="fitur" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <h2 class="font-display font-semibold text-xl text-gray-800">Fitur Utama</h2>
        <p class="text-sm text-gray-500 mt-1">Klik tiap kartu untuk detail.</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
            <button type="button" onclick="openFitur('pengumuman')" class="text-left bg-white rounded-xl border border-gray-100 p-6 hover:border-primary hover:shadow-md transition cursor-pointer">
                <div class="text-2xl mb-3">📢</div>
                <h3 class="font-semibold text-gray-800">Pengumuman</h3>
                <p class="text-sm text-gray-500 mt-1">Sebaran info resmi ke seluruh anggota, dengan status baca.</p>
                <span class="inline-block mt-3 text-xs font-semibold text-primary">Lihat detail →</span>
            </button>
            <button type="button" onclick="openFitur('surat')" class="text-left bg-white rounded-xl border border-gray-100 p-6 hover:border-primary hover:shadow-md transition cursor-pointer">
                <div class="text-2xl mb-3">📄</div>
                <h3 class="font-semibold text-gray-800">Surat &amp; Dokumen</h3>
                <p class="text-sm text-gray-500 mt-1">Upload, distribusi, dan unduh dokumen dinas digital.</p>
                <span class="inline-block mt-3 text-xs font-semibold text-primary">Lihat detail →</span>
            </button>
            <button type="button" onclick="openFitur('workspace')" class="text-left bg-white rounded-xl border border-gray-100 p-6 hover:border-primary hover:shadow-md transition cursor-pointer">
                <div class="text-2xl mb-3">💼</div>
                <h3 class="font-semibold text-gray-800">Workspace</h3>
                <p class="text-sm text-gray-500 mt-1">Bagi tugas, kumpulkan hasil kerja, review atasan.</p>
                <span class="inline-block mt-3 text-xs font-semibold text-primary">Lihat detail →</span>
            </button>
            <button type="button" onclick="openFitur('ujian')" class="text-left bg-white rounded-xl border border-gray-100 p-6 hover:border-primary hover:shadow-md transition cursor-pointer">
                <div class="text-2xl mb-3">📝</div>
                <h3 class="font-semibold text-gray-800">Ujian</h3>
                <p class="text-sm text-gray-500 mt-1">Ujian pilihan ganda dan esai dengan penilaian terstruktur.</p>
                <span class="inline-block mt-3 text-xs font-semibold text-primary">Lihat detail →</span>
            </button>
            <button type="button" onclick="openFitur('library')" class="text-left bg-white rounded-xl border border-gray-100 p-6 hover:border-primary hover:shadow-md transition cursor-pointer">
                <div class="text-2xl mb-3">📚</div>
                <h3 class="font-semibold text-gray-800">Perpustakaan</h3>
                <p class="text-sm text-gray-500 mt-1">Koleksi materi belajar: artikel, PDF, dan referensi eksternal.</p>
                <span class="inline-block mt-3 text-xs font-semibold text-primary">Lihat detail →</span>
            </button>
        </div>
    </section>

    <section id="cara-kerja" class="bg-white border-y border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <h2 class="font-display font-semibold text-xl text-gray-800">Cara Kerja Umum</h2>
            <div class="grid gap-4 sm:grid-cols-3 mt-6">
                <div class="rounded-xl border border-gray-100 p-5">
                    <div class="text-xs font-bold text-accent">1</div>
                    <h3 class="font-semibold mt-1">Distribusikan</h3>
                    <p class="text-sm text-gray-500 mt-1">Pimpinan buat pengumuman, dokumen, tugas, materi, atau ujian.</p>
                </div>
                <div class="rounded-xl border border-gray-100 p-5">
                    <div class="text-xs font-bold text-accent">2</div>
                    <h3 class="font-semibold mt-1">Kerjakan</h3>
                    <p class="text-sm text-gray-500 mt-1">Anggota baca, unduh, kerjakan tugas, belajar materi, dan ikut ujian.</p>
                </div>
                <div class="rounded-xl border border-gray-100 p-5">
                    <div class="text-xs font-bold text-accent">3</div>
                    <h3 class="font-semibold mt-1">Pantau</h3>
                    <p class="text-sm text-gray-500 mt-1">Status baca, unduhan, progres, dan nilai tercatat untuk evaluasi.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="pengguna" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h2 class="font-display font-semibold text-xl text-gray-800">Untuk Siapa?</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 mt-6">
            <div class="bg-white rounded-xl border border-gray-100 p-5">
                <h3 class="font-semibold">Pimpinan</h3>
                <p class="text-sm text-gray-500 mt-1">Pantau lintas unit, sebar info, evaluasi kinerja.</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-5">
                <h3 class="font-semibold">Atasan</h3>
                <p class="text-sm text-gray-500 mt-1">Kelola unit kerja sendiri: tugas, review, nilai esai.</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-5">
                <h3 class="font-semibold">Anggota</h3>
                <p class="text-sm text-gray-500 mt-1">Terima info, kerjakan tugas, belajar, ikut ujian.</p>
            </div>
        </div>
    </section>

    <div id="fitur-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" onclick="closeFitur()"></div>
        <div class="relative bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl">
            <button type="button" onclick="closeFitur()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl leading-none">×</button>
            <div id="fitur-icon" class="text-3xl mb-3">📢</div>
            <h3 id="fitur-title" class="font-display font-bold text-lg text-gray-900"></h3>
            <p id="fitur-desc" class="text-sm text-gray-500 mt-2 leading-relaxed"></p>
            <ul id="fitur-points" class="mt-4 space-y-2 text-sm text-gray-600 list-disc pl-5"></ul>
            <a href="{{ route('login') }}" class="inline-flex items-center mt-6 px-5 py-2 rounded-lg bg-primary text-white text-sm font-semibold hover:bg-primary-dark transition">Masuk untuk akses</a>
        </div>
    </div>

    <footer class="bg-white border-t border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-sm text-gray-500">
            <span>&copy; {{ date('Y') }} SIAP — Sistem Informasi &amp; Pembelajaran</span>
            <span class="text-xs">SIAP v1.0</span>
        </div>
    </footer>

    <script>
        const FITUR = {
            pengumuman: { icon: '📢', title: 'Pengumuman', desc: 'Pusat informasi resmi organisasi. Pimpinan buat pengumuman sekali, semua anggota terkait terima dan baca.', points: ['Target per unit kerja atau seluruh organisasi', 'Status siapa sudah / belum baca', 'Arsip pengumuman terdahulu'] },
            surat: { icon: '📄', title: 'Surat & Dokumen', desc: 'Distribusi dokumen digital tanpa cetak-fotokopi. Upload PDF/Word, anggota unduh sesuai hak akses.', points: ['Upload PDF / Word', 'Unduhan tercatat per anggota', 'Akses per unit kerja'] },
            workspace: { icon: '💼', title: 'Workspace', desc: 'Kelola tugas kerja harian. Atasan bagi tugas, anggota kumpulkan hasil, atasan review.', points: ['Penugasan per anggota / unit', 'Upload hasil kerja + lampiran', 'Review dan umpan balik atasan'] },
            ujian: { icon: '📝', title: 'Ujian', desc: 'Uji kompetensi terstruktur. Soal pilihan ganda dinilai otomatis, esai dinilai manual oleh penilai.', points: ['Soal pilihan ganda + esai', 'Auto-grading untuk PG', 'Peringatan anti-cheat bertingkat'] },
            library: { icon: '📚', title: 'Perpustakaan', desc: 'Koleksi pengetahuan organisasi. Artikel native, file PDF, dan referensi link eksternal dalam satu pencarian.', points: ['Artikel, PDF, link referensi', 'Kategori, level, status verifikasi', 'Bookmark dan lacak progres baca', 'Rekomendasi materi personal'] },
        };
        function openFitur(key) {
            const f = FITUR[key];
            if (!f) return;
            document.getElementById('fitur-icon').textContent = f.icon;
            document.getElementById('fitur-title').textContent = f.title;
            document.getElementById('fitur-desc').textContent = f.desc;
            document.getElementById('fitur-points').innerHTML = f.points.map(p => `<li>${p}</li>`).join('');
            document.getElementById('fitur-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        function closeFitur() {
            document.getElementById('fitur-modal').classList.add('hidden');
            document.body.style.overflow = '';
        }
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeFitur(); });
    </script>
</body>
</html>
