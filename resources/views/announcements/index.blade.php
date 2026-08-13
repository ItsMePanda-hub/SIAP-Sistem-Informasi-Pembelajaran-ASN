<x-app-layout>
    <x-slot name="header">Pengumuman</x-slot>

    <div class="max-w-4xl mx-auto space-y-4">
        @if (in_array(auth()->user()->role, ['atasan', 'pemilik', 'admin']))
            <div class="flex justify-end">
                <a href="{{ route('announcements.create') }}" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark">
                    Buat pengumuman
                </a>
            </div>
        @endif

        <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6 space-y-4">
            @forelse ($announcements as $a)
                <div class="border-b border-gray-100 pb-4">
                    <a href="{{ route('announcements.show', $a) }}" class="font-semibold text-gray-800 hover:text-primary-dark">
                        {{ $a->title }}
                    </a>
                    <span class="ml-2 text-xs px-2 py-1 rounded-full {{ $a->category === 'mendesak' ? 'bg-red-50 text-red-600' : 'bg-primary-light text-primary-dark' }}">
                        {{ $a->category === 'mendesak' ? 'Perlu tindak lanjut' : 'Info rutin' }}
                    </span>
                    <div class="text-xs text-gray-400 mt-1">
                        {{ $a->creator->name }} &middot; {{ $a->created_at->translatedFormat('d M Y') }}
                    </div>
                </div>
            @empty
                <p class="text-gray-400 text-sm">Belum ada pengumuman.</p>
            @endforelse
        </div>

        <div>{{ $announcements->links() }}</div>
    </div>
</x-app-layout>
