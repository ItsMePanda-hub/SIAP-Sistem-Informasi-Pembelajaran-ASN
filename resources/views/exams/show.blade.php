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
                        @if ($attempt->status === 'selesai_pelanggaran')
                            Ujian dikumpulkan otomatis (pelanggaran)
                        @elseif ($attempt->status === 'menunggu_penilaian_esai')
                            Menunggu penilaian esai
                        @else
                            Ujian selesai
                        @endif
                    </div>
                    <div class="text-xs text-gray-500">
                        Dikumpulkan {{ $attempt->submitted_at?->translatedFormat('d M Y, H:i') }}
                        @if ($attempt->violation_count > 0)
                            &middot; {{ $attempt->violation_count }} peringatan tercatat
                        @endif
                    </div>
                    @if ($attempt->status === 'menunggu_penilaian_esai')
                        <div class="text-xs text-gray-500 mt-1">Skor akhir akan muncul setelah atasan menilai jawaban esai Anda.</div>
                    @elseif ($attempt->score !== null)
                        <div class="text-xs text-gray-500 mt-1">Skor akhir: <strong>{{ $attempt->score }}</strong></div>
                    @endif
                </div>

                @if ($attempt->status === 'selesai_pelanggaran' && $attempt->violation_appeal_status === null)
                    <div class="mt-4 bg-orange-50 border border-orange-200 rounded-lg p-4">
                        <div class="text-sm font-semibold text-orange-800 mb-2">Ajukan Banding Pelanggaran</div>
                        <p class="text-xs text-orange-700 mb-3">Jika Anda merasa sistem keliru mendeteksi pelanggaran, Anda dapat mengajukan banding beserta alasannya.</p>
                        <form method="POST" action="{{ route('exams.appeal', $exam) }}">
                            @csrf
                            <textarea name="note" rows="2" class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 mb-2" placeholder="Tulis alasan banding Anda di sini..." required></textarea>
                            <button type="submit" class="px-3 py-1.5 bg-orange-600 text-white rounded text-xs font-medium hover:bg-orange-700">Ajukan Banding</button>
                        </form>
                    </div>
                @elseif ($attempt->violation_appeal_status !== null)
                    <div class="mt-4 bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <div class="text-sm font-semibold text-gray-700 mb-1">Status Banding: 
                            <span class="capitalize {{ $attempt->violation_appeal_status === 'diterima' ? 'text-green-600' : ($attempt->violation_appeal_status === 'ditolak' ? 'text-red-600' : 'text-blue-600') }}">{{ $attempt->violation_appeal_status }}</span>
                        </div>
                        <p class="text-xs text-gray-500 italic">"{{ $attempt->violation_appeal_note }}"</p>
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
