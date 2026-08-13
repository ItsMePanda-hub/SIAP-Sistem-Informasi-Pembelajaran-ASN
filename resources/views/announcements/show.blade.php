<x-app-layout>
    <x-slot name="header">{{ $announcement->title }}</x-slot>

    <div class="max-w-4xl mx-auto space-y-6">
        <div class="flex justify-between items-center">
            <a href="{{ route('announcements.index') }}" class="text-xs text-gray-400 hover:text-primary-dark">&larr; Kembali ke pengumuman</a>
        </div>

        <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
            <span class="text-xs px-2 py-1 rounded-full {{ $announcement->category === 'mendesak' ? 'bg-red-50 text-red-600' : 'bg-primary-light text-primary-dark' }}">
                {{ $announcement->category === 'mendesak' ? 'Perlu tindak lanjut' : 'Info rutin' }}
            </span>
            <div class="text-xs text-gray-400 mt-3 mb-4">
                {{ $announcement->creator->name }} &middot; {{ $announcement->created_at->translatedFormat('d M Y, H:i') }}
            </div>
            <p class="text-gray-700 leading-relaxed">{{ $announcement->body }}</p>
        </div>

        @if (in_array(auth()->user()->role, ['atasan', 'pemilik', 'admin']))
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Status pembacaan ({{ $readers->count() }} orang)</h3>
                @forelse ($readers as $r)
                    <div class="flex justify-between text-sm border-b border-gray-100 py-2">
                        <span>{{ $r->user->name }}</span>
                        <span class="text-gray-400 text-xs">{{ $r->read_at->translatedFormat('d M Y, H:i') }}</span>
                    </div>
                @empty
                    <p class="text-gray-400 text-sm">Belum ada yang membaca.</p>
                @endforelse
            </div>
        @endif
    </div>
</x-app-layout>
