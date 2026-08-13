<x-app-layout>
    <x-slot name="header">{{ $training->title }}</x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        <a href="{{ route('trainings.index') }}" class="text-xs text-gray-400 hover:text-primary-dark">&larr; Kembali ke pelatihan</a>

        <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
            <p class="text-gray-700 leading-relaxed mb-6">{{ $training->description }}</p>

            @if (auth()->user()->role === 'admin')
                <p class="text-sm text-gray-400 italic">Admin tidak mengikuti pelatihan sebagai peserta — hanya memantau progres tim.</p>
            @elseif (!$progress || $progress->status === 'belum_mulai')
                <form method="POST" action="{{ route('trainings.start', $training) }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark">
                        Mulai pelatihan
                    </button>
                </form>
            @elseif ($progress->status === 'sedang_berjalan')
                <form method="POST" action="{{ route('trainings.complete', $training) }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-[#C9A15C] text-white rounded-lg text-sm font-medium hover:opacity-90">
                        Tandai selesai
                    </button>
                </form>
            @else
                <div class="bg-green-50 border border-green-100 rounded-lg p-4">
                    <div class="text-green-700 font-semibold text-sm mb-1">Pelatihan selesai</div>
                    <div class="text-xs text-gray-500">
                        Diselesaikan pada {{ $progress->completed_at->translatedFormat('d M Y, H:i') }}
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        Kode sertifikat: <span class="font-mono font-semibold text-gray-700">{{ $progress->certificate_code }}</span>
                    </div>
                </div>
            @endif
        </div>

        @if (in_array(auth()->user()->role, ['atasan', 'pemilik', 'admin']))
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
                <div class="text-sm font-semibold text-gray-800">Tingkat penyelesaian tim</div>
                <div class="text-2xl font-semibold text-primary-dark mt-1">{{ $training->completionPercentage() }}%</div>
            </div>
        @endif
    </div>
</x-app-layout>
