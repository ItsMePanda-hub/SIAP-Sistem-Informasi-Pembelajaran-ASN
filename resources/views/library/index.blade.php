<x-app-layout>
     <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-lg font-semibold text-gray-800">SIAP Library</div>
                <div class="text-xs text-gray-500">Kelola Materi Pembelajaran</div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('library.bookmarks') }}" class="px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 hover:bg-gray-50">Bookmark Saya</a>
                @can('create', \App\Models\LearningMaterial::class)
                    <a href="{{ route('library.create') }}" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark">Tambah Materi</a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto space-y-4">
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-4">
            <form method="GET" action="{{ route('library.index') }}" class="space-y-3">
                <div class="flex flex-wrap gap-2">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari judul / deskripsi..." class="flex-1 min-w-[200px] border border-gray-200 rounded-lg px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                    <select name="sort" class="border border-gray-200 rounded-lg px-2 py-2 text-sm">
                        <option value="">Terbaru</option>
                        <option value="judul_asc" @selected(request('sort')==='judul_asc')>Judul A–Z</option>
                        <option value="judul_desc" @selected(request('sort')==='judul_desc')>Judul Z–A</option>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium">Cari</button>
                    <a href="{{ route('library.index') }}" class="px-4 py-2 border border-gray-200 rounded-lg text-sm text-gray-600">Reset</a>
                </div>
                @php
                    $activeFilters = [];
                    if (trim((string) request('q', '')) !== '') $activeFilters[] = 'Pencarian: "'.request('q').'"';
                    foreach (['scope' => 'Scope', 'learning_level_id' => 'Level', 'learning_category_id' => 'Kategori', 'material_type' => 'Tipe', 'publication_status' => 'Publikasi', 'verification_status' => 'Verifikasi', 'validity_status' => 'Validitas', 'progress_status' => 'Progress'] as $k => $label) {
                        if (request()->filled($k)) $activeFilters[] = $label.': '.request($k);
                    }
                    if (request()->filled('sort')) $activeFilters[] = 'Urut: '.request('sort');
                @endphp
                @if(!empty($activeFilters))
                    <div class="text-xs text-gray-500">Filter aktif: {{ implode(' · ', $activeFilters) }}</div>
                @endif
                <div class="text-xs text-gray-500">Menampilkan {{ $materials->total() }} hasil</div>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-2">
                    <select name="progress_status" class="border border-gray-200 rounded-lg px-2 py-2 text-sm">
                        <option value="">Semua Progress</option>
                        <option value="belum_mulai" @selected(request('progress_status')==='belum_mulai')>Belum mulai</option>
                        <option value="sedang_membaca" @selected(request('progress_status')==='sedang_membaca')>Sedang membaca</option>
                        <option value="selesai" @selected(request('progress_status')==='selesai')>Selesai</option>
                    </select>
                    <select name="scope" class="border border-gray-200 rounded-lg px-2 py-2 text-sm">
                        <option value="">Semua Scope</option>
                        <option value="core" @selected(request('scope')==='core')>Core</option>
                        <option value="custom" @selected(request('scope')==='custom')>Custom</option>
                    </select>
                    <select name="learning_level_id" class="border border-gray-200 rounded-lg px-2 py-2 text-sm">
                        <option value="">Semua Level</option>
                        @foreach($levels as $lvl)
                            <option value="{{ $lvl->id }}" @selected((string)request('learning_level_id')===(string)$lvl->id)>{{ $lvl->name }}</option>
                        @endforeach
                    </select>
                    <select name="learning_category_id" class="border border-gray-200 rounded-lg px-2 py-2 text-sm">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected((string)request('learning_category_id')===(string)$cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    <select name="material_type" class="border border-gray-200 rounded-lg px-2 py-2 text-sm">
                        <option value="">Semua Tipe</option>
                        <option value="native" @selected(request('material_type')==='native')>Native</option>
                        <option value="pdf" @selected(request('material_type')==='pdf')>PDF</option>
                        <option value="link_only" @selected(request('material_type')==='link_only')>Link Only</option>
                    </select>
                    <select name="publication_status" class="border border-gray-200 rounded-lg px-2 py-2 text-sm">
                        <option value="">Semua Publikasi</option>
                        @foreach($publicationStatuses as $s)
                            <option value="{{ $s->value }}" @selected(request('publication_status')===$s->value)>{{ $s->value==='published'?'Published':($s->value==='draft'?'Draft':'Unpublished') }}</option>
                        @endforeach
                    </select>
                    <select name="verification_status" class="border border-gray-200 rounded-lg px-2 py-2 text-sm">
                        <option value="">Semua Verifikasi</option>
                        @foreach($verificationStatuses as $s)
                            <option value="{{ $s->value }}" @selected(request('verification_status')===$s->value)>{{ ucfirst(str_replace('_',' ',$s->value)) }}</option>
                        @endforeach
                    </select>
                    <select name="validity_status" class="border border-gray-200 rounded-lg px-2 py-2 text-sm">
                        <option value="">Semua Validitas</option>
                        @foreach($validityStatuses as $s)
                            <option value="{{ $s->value }}" @selected(request('validity_status')===$s->value)>{{ ucfirst($s->value) }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>

        @isset($recommendations)
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="text-sm font-semibold text-gray-800">Rekomendasi untuk Anda</div>
                <div class="text-xs text-gray-400">Maksimal 6 materi</div>
            </div>
            @if($recommendations->isEmpty())
                <div class="text-sm text-gray-400 text-center py-6">Belum ada rekomendasi. <a href="{{ route('library.index') }}" class="text-primary hover:underline">Jelajahi materi di pencarian</a></div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($recommendations as $rec)
                        @php $rm = $rec['material']; @endphp
                        <div class="border border-gray-100 rounded-lg p-3 space-y-2 bg-gray-50/50">
                            <div class="font-medium text-sm text-gray-800 line-clamp-2">{{ $rm->title }}</div>
                            <div class="flex flex-wrap gap-1 text-xs">
                                <span class="px-2 py-0.5 rounded-full bg-white border border-gray-100 text-gray-600">{{ $rm->category->name ?? '-' }}</span>
                                <span class="px-2 py-0.5 rounded-full bg-white border border-gray-100 text-gray-600">{{ $rm->level->name ?? '-' }}</span>
                                <span class="px-2 py-0.5 rounded-full bg-white border border-gray-100 text-gray-600">{{ $rm->isLinkOnly() ? 'Link Only' : ucfirst($rm->material_type->value) }}</span>
                            </div>
                            <div class="text-xs text-teal-700 italic">{{ $rec['reason'] }}</div>
                            @php $rp = $rec['progress'] ?? ($progressMap[$rm->id] ?? null); @endphp
                            <div class="text-xs {{ !is_null($rp) && $rp >= 100 ? 'text-green-600' : (!is_null($rp) && $rp > 0 ? 'text-amber-600' : 'text-gray-400') }}">{{ !is_null($rp) && $rp >= 100 ? 'Selesai' : (!is_null($rp) && $rp > 0 ? 'Sedang membaca '.$rp.'%' : 'Belum mulai') }}</div>
                            @can('view', $rm)
                                <a href="{{ route('library.read', $rm) }}" class="inline-block px-3 py-1.5 bg-primary text-white rounded-lg text-xs font-medium hover:bg-primary-dark">Baca</a>
                            @endcan
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        @endisset

        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Judul</th>
                            <th class="px-4 py-3">Scope</th>
                            <th class="px-4 py-3">Level</th>
                            <th class="px-4 py-3">Kategori</th>
                            <th class="px-4 py-3">Tipe</th>
                            <th class="px-4 py-3">Verifikasi</th>
                            <th class="px-4 py-3">Validitas</th>
                            <th class="px-4 py-3">Publikasi</th>
                            <th class="px-4 py-3">Sumber &amp; Lisensi</th>
                            <th class="px-4 py-3">Baca</th>
                            @if($isManager)<th class="px-4 py-3">Aksi</th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($materials as $m)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-800">{{ $m->title }}</div>
                                    @if($m->description)<div class="text-xs text-gray-400 line-clamp-1">{{ Str::limit($m->description, 80) }}</div>@endif
                                    @if($m->isNative() && $m->hasContent())<div class="text-xs text-gray-500 mt-1">{{ Str::limit(strip_tags($m->content), 60) }}</div>@endif
                                    @if($m->isPdf() && $m->hasFile())<div class="text-xs text-blue-600 mt-1">{{ $m->file_original_name }} ({{ number_format(($m->file_size ?? 0)/1024, 1) }} KB)</div>@endif
                                    @if($m->isLinkOnly())<div class="text-xs text-teal-600 mt-1">Referensi Eksternal</div>@endif
                                </td>
                                <td class="px-4 py-3 text-center"><span class="text-xs px-2 py-1 rounded-full {{ $m->scope->value==='core'?'bg-teal-50 text-teal-700':'bg-amber-50 text-amber-700' }}">{{ $m->scope->value==='core'?'Core':'Custom' }}</span></td>
                                <td class="px-4 py-3 text-center text-xs">{{ $m->level->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-center text-xs">{{ $m->category->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-center text-xs">{{ $m->isLinkOnly() ? 'Link Only' : $m->material_type->value }}</td>
                                <td class="px-4 py-3 text-center text-xs">{{ str_replace('_',' ',$m->verification_status->value) }}</td>
                                <td class="px-4 py-3 text-center text-xs">{{ $m->validity_status->value }}</td>
                                <td class="px-4 py-3 text-center"><span class="text-xs px-2 py-1 rounded-full {{ $m->publication_status->value==='published'?'bg-green-50 text-green-700':'bg-gray-100 text-gray-600' }}">{{ $m->publication_status->value==='published'?'Published':($m->publication_status->value==='draft'?'Draft':'Unpublished') }}</span></td>
                                <td class="px-4 py-3 text-center text-xs">
                                    @if($m->source)
                                        <div class="font-medium text-gray-700">{{ $m->source->source_name }}</div>
                                        <div class="text-gray-400">{{ $m->source->publisher ?? '-' }}</div>
                                        @if($m->source->license_type)
                                            <div class="mt-0.5"><span class="text-[10px] px-1.5 py-0.5 bg-gray-100 rounded text-gray-600 font-mono">{{ $m->source->license_type->value }}</span></div>
                                        @endif
                                    @else
                                        <span class="text-gray-300">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @can('view', $m)
                                        <a href="{{ route('library.read', $m) }}" class="inline-block px-3 py-1.5 bg-primary text-white rounded-lg text-xs font-medium hover:bg-primary-dark">Baca</a>
                                        @php $pp = $progressMap[$m->id] ?? null; @endphp
                                        @if(!is_null($pp))
                                            <div class="text-xs mt-1 {{ $pp >= 100 ? 'text-green-600' : ($pp > 0 ? 'text-amber-600' : 'text-gray-400') }}">{{ $pp >= 100 ? 'Selesai' : ($pp > 0 ? 'Sedang membaca '.$pp.'%' : 'Belum mulai') }}</div>
                                        @else
                                            <div class="text-xs mt-1 text-gray-300">Belum mulai</div>
                                        @endif
                                        @if(isset($bookmarkSet[$m->id]))
                                            <form method="POST" action="{{ route('library.bookmark.destroy', $m) }}" class="mt-1">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-amber-600 hover:underline">★ Tersimpan</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('library.bookmark.store', $m) }}" class="mt-1">
                                                @csrf
                                                <button type="submit" class="text-xs text-gray-400 hover:underline">☆ Simpan</button>
                                            </form>
                                        @endif
                                    @else
                                        <span class="text-xs text-gray-300">-</span>
                                    @endcan
                                </td>
                                @if($isManager)
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        @can('update', $m)<a href="{{ route('library.edit', $m) }}" class="text-xs text-primary hover:underline">Edit</a>@endcan
                                        @can('update', $m)<a href="{{ route('library.source.edit', $m) }}" class="text-xs text-teal-700 hover:underline">Sumber</a>@endcan
                                        @can('update', $m)
                                            @if(!$m->isPublished())
                                                <form method="POST" action="{{ route('library.publish', $m) }}">
                                                    @csrf
                                                    <button type="submit" class="text-xs text-green-700 hover:underline">Publish</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('library.unpublish', $m) }}">
                                                    @csrf
                                                    <button type="submit" class="text-xs text-amber-700 hover:underline">Unpublish</button>
                                                </form>
                                            @endif
                                        @endcan
                                        @can('delete', $m)
                                        <form method="POST" action="{{ route('library.destroy', $m) }}" onsubmit="return confirm('Hapus materi ini?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-xs text-red-600 hover:underline">Hapus</button>
                                        </form>
                                        @endcan
                                    </div>
                                </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="{{ $isManager?11:10 }}" class="px-4 py-6 text-center text-gray-400 text-sm">Belum ada materi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-100">{{ $materials->links() }}</div>
        </div>
    </div>
</x-app-layout>
