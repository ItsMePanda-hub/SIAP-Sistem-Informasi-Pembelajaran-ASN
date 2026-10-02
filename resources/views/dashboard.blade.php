<x-app-layout>
    <x-slot name="header">Dashboard</x-slot>

    <div class="max-w-4xl mx-auto space-y-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Selamat datang, {{ auth()->user()->name }}</h2>
            <p class="text-sm text-gray-500 mt-1">{{ auth()->user()->jabatan ?? '-' }} &middot; {{ auth()->user()->unit_kerja ?? '-' }}</p>
        </div>

        @if(isset($stats))
            <div class="mb-6">
                <h3 class="font-semibold text-gray-800 mb-3">Statistik</h3>
                @if($stats['type'] === 'manager')
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                        <div class="bg-white shadow-sm rounded-xl p-5 border border-gray-100">
                            <div class="text-sm font-semibold text-gray-800">Total Pengumuman</div>
                            <div class="text-2xl font-bold text-teal-600 mt-1">{{ $stats['total_announcements'] }}</div>
                        </div>
                    </div>
                    
                    <h4 class="text-sm font-semibold text-gray-600 mb-2">Per Unit Kerja</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($stats['unit_stats'] as $unitStat)
                            <div class="bg-white shadow-sm rounded-xl p-5 border border-gray-100">
                                <div class="text-sm font-semibold text-gray-800 border-b pb-2 mb-2">{{ $unitStat['unit_kerja'] }}</div>
                                <div class="grid grid-cols-3 gap-2 text-center">
                                    <div>
                                        <div class="text-xs text-gray-500">Tingkat Baca</div>
                                        <div class="text-lg font-bold text-teal-600">{{ $unitStat['avg_read_rate'] }}%</div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500">Workspace</div>
                                        <div class="text-lg font-bold text-yellow-600">{{ $unitStat['avg_task_completion'] }}%</div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500">Nilai Ujian</div>
                                        <div class="text-lg font-bold text-teal-600">{{ $unitStat['avg_exam_score'] }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white shadow-sm rounded-xl p-5 border border-gray-100">
                            <div class="text-sm font-semibold text-gray-800">Pengumuman Belum Dibaca</div>
                            <div class="text-2xl font-bold text-teal-600 mt-1">{{ $stats['unread_announcements'] }}</div>
                        </div>
                        <div class="bg-white shadow-sm rounded-xl p-5 border border-gray-100">
                            <div class="text-sm font-semibold text-gray-800">Penyelesaian Workspace</div>
                            <div class="text-2xl font-bold text-yellow-600 mt-1">{{ $stats['task_completion_percent'] }}%</div>
                        </div>
                        <div class="bg-white shadow-sm rounded-xl p-5 border border-gray-100">
                            <div class="text-sm font-semibold text-gray-800">Riwayat Nilai Ujian</div>
                            @if(count($stats['exam_history']) > 0)
                                <ul class="text-xs mt-2 space-y-1">
                                    @foreach($stats['exam_history'] as $history)
                                        <li class="flex justify-between border-b border-gray-50 pb-1">
                                            <span class="truncate pr-2">{{ $history->exam->title }}</span>
                                            <span class="font-semibold text-teal-600">{{ $history->score }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="text-xs text-gray-500 mt-2">Belum ada ujian</div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <h3 class="font-semibold text-gray-800 mb-3">Akses Cepat</h3>
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
