<x-app-layout>
    <x-slot name="header">Pelatihan</x-slot>

    <div class="max-w-4xl mx-auto space-y-4">
        @if (in_array(auth()->user()->role, ['atasan', 'pemilik', 'admin']))
            <div class="flex justify-end">
                <a href="{{ route('trainings.create') }}" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark">
                    Buat pelatihan
                </a>
            </div>
        @endif

        <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6 space-y-4">
            @forelse ($trainings as $t)
                @php $status = $progresses[$t->id] ?? 'belum_mulai'; @endphp
                <a href="{{ route('trainings.show', $t) }}" class="block border-b border-gray-100 pb-4">
                    <div class="flex justify-between items-center">
                        <div class="font-semibold text-gray-800">{{ $t->title }}</div>
                        <span class="text-xs px-2 py-1 rounded-full
                            {{ $status === 'selesai' ? 'bg-green-50 text-green-600' : ($status === 'sedang_berjalan' ? 'bg-accent-light text-accent' : 'bg-gray-100 text-gray-500') }}">
                            {{ ['belum_mulai' => 'Belum mulai', 'sedang_berjalan' => 'Sedang berjalan', 'selesai' => 'Selesai'][$status] }}
                        </span>
                    </div>
                    <div class="text-xs text-gray-400 mt-1">{{ Str::limit($t->description, 90) }}</div>
                </a>
            @empty
                <p class="text-gray-400 text-sm">Belum ada pelatihan.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
