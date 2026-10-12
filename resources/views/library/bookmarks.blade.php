<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-lg font-semibold text-gray-800">Bookmark Saya</div>
                <div class="text-xs text-gray-500">Materi yang Anda simpan</div>
            </div>
            <a href="{{ route('library.index') }}" class="px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 hover:bg-gray-50">Kembali ke Library</a>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto">
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Judul</th>
                            <th class="px-4 py-3">Level</th>
                            <th class="px-4 py-3">Kategori</th>
                            <th class="px-4 py-3">Tipe</th>
                            <th class="px-4 py-3">Scope</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Progress</th>
                            <th class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($materials as $m)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-800">{{ $m->title }}</div>
                                    <div class="text-xs text-gray-400">{{ Str::limit($m->description ?? '', 60) }}</div>
                                </td>
                                <td class="px-4 py-3 text-center text-xs">{{ $m->level->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-center text-xs">{{ $m->category->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-center text-xs">{{ $m->material_type->value }}</td>
                                <td class="px-4 py-3 text-center"><span class="text-xs px-2 py-1 rounded-full {{ $m->scope->value==='core'?'bg-teal-50 text-teal-700':'bg-amber-50 text-amber-700' }}">{{ $m->scope->value==='core'?'Core':'Custom' }}</span></td>
                                <td class="px-4 py-3 text-center text-xs">
                                    @can('view', $m)
                                        <span class="text-green-600">Tersedia</span>
                                    @else
                                        <span class="text-amber-600">Tidak tersedia</span>
                                    @endcan
                                </td>
                                <td class="px-4 py-3 text-center text-xs">
                                    @php $pp = $progressMap[$m->id] ?? null; @endphp
                                    @if(!is_null($pp))
                                        <span class="{{ $pp >= 100 ? 'text-green-600' : ($pp > 0 ? 'text-amber-600' : 'text-gray-400') }}">{{ $pp >= 100 ? 'Selesai' : ($pp > 0 ? $pp.'%' : 'Belum mulai') }}</span>
                                    @else
                                        <span class="text-gray-400">Belum mulai</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        @can('view', $m)
                                            <a href="{{ route('library.read', $m) }}" class="px-3 py-1.5 bg-primary text-white rounded-lg text-xs font-medium">Baca</a>
                                        @endcan
                                        <form method="POST" action="{{ route('library.bookmark.destroy', $m) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-1.5 border border-gray-200 rounded-lg text-xs text-gray-600">Hapus Bookmark</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-6 text-center text-gray-400 text-sm">Belum ada bookmark.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-100">{{ $materials->links() }}</div>
        </div>
    </div>
</x-app-layout>
