<x-app-layout>
    <x-slot name="header">Dashboard</x-slot>

    <div class="max-w-4xl mx-auto space-y-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Selamat datang, {{ auth()->user()->name }}</h2>
            <p class="text-sm text-gray-500 mt-1">{{ auth()->user()->jabatan ?? '-' }} &middot; {{ auth()->user()->unit_kerja ?? '-' }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <a href="{{ route('announcements.index') }}" class="bg-white shadow-sm rounded-xl p-5 border border-gray-100 hover:border-primary transition">
                <div class="text-sm font-semibold text-gray-800 mb-1">Pengumuman</div>
                <div class="text-xs text-gray-500">Lihat surat edaran &amp; nota dinas terbaru</div>
            </a>
            <a href="{{ route('letters.index') }}" class="bg-white shadow-sm rounded-xl p-5 border border-gray-100 hover:border-primary transition">
                <div class="text-sm font-semibold text-gray-800 mb-1">Surat &amp; Dokumen</div>
                <div class="text-xs text-gray-500">Unduh SK, nota dinas, dan surat tugas</div>
            </a>
            <a href="{{ route('trainings.index') }}" class="bg-white shadow-sm rounded-xl p-5 border border-gray-100 hover:border-primary transition">
                <div class="text-sm font-semibold text-gray-800 mb-1">Pelatihan</div>
                <div class="text-xs text-gray-500">Ikuti pelatihan dan dapatkan sertifikat</div>
            </a>
            <a href="{{ route('exams.index') }}" class="bg-white shadow-sm rounded-xl p-5 border border-gray-100 hover:border-primary transition">
                <div class="text-sm font-semibold text-gray-800 mb-1">Ujian</div>
                <div class="text-xs text-gray-500">Kerjakan ujian yang tersedia</div>
            </a>
        </div>
    </div>
</x-app-layout>
