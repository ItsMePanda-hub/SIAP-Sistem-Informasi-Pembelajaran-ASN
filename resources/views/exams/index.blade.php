<x-app-layout>
    <x-slot name="header">Ujian</x-slot>

    <div class="max-w-4xl mx-auto space-y-4">
        @if (in_array(auth()->user()->role, ['atasan', 'pemilik', 'admin']))
            <div class="flex justify-end">
                <a href="{{ route('exams.create') }}" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark">
                    Buat ujian
                </a>
            </div>
        @endif

        <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6 space-y-4">
            @forelse ($exams as $exam)
                <a href="{{ route('exams.show', $exam) }}" class="block border-b border-gray-100 pb-4">
                    <div class="font-semibold text-gray-800">{{ $exam->title }}</div>
                    <div class="text-xs text-gray-400 mt-1">
                        {{ $exam->questions()->count() }} soal &middot; dibuat oleh {{ $exam->creator->name }}
                        @if ($exam->duration_minutes)
                            &middot; {{ $exam->duration_minutes }} menit
                        @endif
                    </div>
                </a>
            @empty
                <p class="text-gray-400 text-sm">Belum ada ujian.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
