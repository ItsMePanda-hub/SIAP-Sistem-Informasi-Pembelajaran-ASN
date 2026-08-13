<x-app-layout>
    <x-slot name="header">{{ $exam->title }}</x-slot>

    <div class="max-w-2xl mx-auto space-y-6">
        <div class="flex justify-between items-center">
            <a href="{{ route('exams.index') }}" class="text-xs text-gray-400 hover:text-primary-dark">&larr; Kembali ke ujian</a>

            @if (in_array(auth()->user()->role, ['atasan', 'pemilik', 'admin']))
                <a href="{{ route('exams.results', $exam) }}" class="text-xs text-primary-dark hover:underline">Lihat hasil peserta &rarr;</a>
            @endif
        </div>

        <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
            <p class="text-gray-700 leading-relaxed mb-4">{{ $exam->description }}</p>
            <div class="text-xs text-gray-500 space-y-1 mb-6">
                <div>{{ $exam->questions()->count() }} soal</div>
                @if ($exam->duration_minutes)
                    <div>Durasi: {{ $exam->duration_minutes }} menit</div>
                @endif
                <div>Maksimal {{ $exam->max_violations }} kali peringatan sebelum dikumpulkan otomatis</div>
            </div>

            @if (auth()->user()->role === 'admin')
                <p class="text-sm text-gray-400 italic">Admin tidak mengikuti ujian sebagai peserta — hanya memantau hasil tim.</p>
            @elseif (!$attempt)
                <form method="POST" action="{{ route('exams.start', $exam) }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark">
                        Mulai ujian
                    </button>
                </form>
            @elseif ($attempt->status === 'sedang_berjalan')
                <a href="{{ route('exams.take', $exam) }}" class="inline-block px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark">
                    Lanjutkan ujian
                </a>
            @else
                <div class="bg-green-50 border border-green-100 rounded-lg p-4">
                    <div class="text-green-700 font-semibold text-sm mb-1">
                        {{ $attempt->status === 'selesai_pelanggaran' ? 'Ujian dikumpulkan otomatis (pelanggaran)' : 'Ujian selesai' }}
                    </div>
                    <div class="text-xs text-gray-500">
                        Dikumpulkan {{ $attempt->submitted_at?->translatedFormat('d M Y, H:i') }}
                        @if ($attempt->violation_count > 0)
                            &middot; {{ $attempt->violation_count }} peringatan tercatat
                        @endif
                    </div>
                    @if ($attempt->score !== null)
                        <div class="text-xs text-gray-500 mt-1">Skor pilihan ganda: <strong>{{ $attempt->score }}</strong></div>
                    @endif
                    <div class="text-xs text-gray-400 mt-1">Jawaban esai (jika ada) menunggu penilaian manual dari atasan.</div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
