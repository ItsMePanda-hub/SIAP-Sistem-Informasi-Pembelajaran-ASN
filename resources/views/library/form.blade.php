<x-app-layout>
    <x-slot name="header">{{ $editing ? 'Edit Materi' : 'Tambah Materi' }} — SIAP Library</x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
            @if ($errors->any())
                <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg text-sm">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ $editing ? route('library.update', $material) : route('library.store') }}" enctype="multipart/form-data">
                @csrf
                @if($editing) @method('PUT') @endif

                <label class="block text-sm font-medium text-gray-700 mb-1">Judul</label>
                <input type="text" name="title" value="{{ old('title', $material->title ?? '') }}" class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-1 focus:border-primary focus:ring-primary" required>
                @error('title')<p class="text-xs text-red-600 mb-3">{{ $message }}</p>@enderror
                <div class="mb-4"></div>

                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                <textarea name="description" rows="4" class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-1 focus:border-primary focus:ring-primary">{{ old('description', $material->description ?? '') }}</textarea>
                @error('description')<p class="text-xs text-red-600 mb-3">{{ $message }}</p>@enderror
                <div class="mb-4"></div>

                <label class="block text-sm font-medium text-gray-700 mb-1">Scope</label>
                <select name="scope" class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-1 focus:border-primary focus:ring-primary" required>
                    @foreach($scopes as $s)
                        <option value="{{ $s->value }}" @selected(old('scope', $material->scope->value ?? '')===$s->value)>{{ $s->value==='core'?'Core':'Custom' }}</option>
                    @endforeach
                </select>
                @error('scope')<p class="text-xs text-red-600 mb-3">{{ $message }}</p>@enderror
                <div class="mb-4"></div>

                <label class="block text-sm font-medium text-gray-700 mb-1">Level</label>
                <select name="learning_level_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-1 focus:border-primary focus:ring-primary" required>
                    <option value="">-- Pilih Level --</option>
                    @foreach($levels as $lvl)
                        <option value="{{ $lvl->id }}" @selected((string)old('learning_level_id', $material->learning_level_id ?? '')===(string)$lvl->id)>{{ $lvl->name }}</option>
                    @endforeach
                </select>
                @error('learning_level_id')<p class="text-xs text-red-600 mb-3">{{ $message }}</p>@enderror
                <div class="mb-4"></div>

                <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                <select name="learning_category_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-1 focus:border-primary focus:ring-primary" required>
                    <option value="">-- Pilih Kategori --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected((string)old('learning_category_id', $material->learning_category_id ?? '')===(string)$cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
                @error('learning_category_id')<p class="text-xs text-red-600 mb-3">{{ $message }}</p>@enderror
                <div class="mb-4"></div>

                <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Materi</label>
                <select id="material_type" name="material_type" class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-1 focus:border-primary focus:ring-primary" required>
                    @foreach($types as $t)
                        <option value="{{ $t->value }}" @selected(old('material_type', $material->material_type->value ?? '')===$t->value)>
                            @if($t->value === 'native')
                                Native (Artikel Teks)
                            @elseif($t->value === 'pdf')
                                PDF (Dokumen File)
                            @elseif($t->value === 'link_only')
                                Link Only (Referensi Eksternal)
                            @else
                                {{ ucfirst($t->value) }}
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('material_type')<p class="text-xs text-red-600 mb-3">{{ $message }}</p>@enderror
                <div class="mb-4"></div>

                <div id="native-fields" class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Konten Artikel (Native)</label>
                    <textarea name="content" rows="10" class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-1 focus:border-primary focus:ring-primary" placeholder="Tulis konten artikel...">{{ old('content', $material->content ?? '') }}</textarea>
                    @error('content')<p class="text-xs text-red-600 mb-3">{{ $message }}</p>@enderror
                    <p class="text-xs text-gray-400">Wajib untuk tipe Native.</p>
                </div>

                <div id="pdf-fields" class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">File PDF</label>
                    @if($editing && $material && $material->hasFile())
                        <p class="text-xs text-gray-500 mb-2">File saat ini: <strong>{{ $material->file_original_name }}</strong> ({{ number_format($material->file_size / 1024, 1) }} KB) — kosongkan jika tidak ingin mengganti.</p>
                    @endif
                    <input type="file" name="pdf_file" accept="application/pdf,.pdf" class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-1 focus:border-primary focus:ring-primary">
                    @error('pdf_file')<p class="text-xs text-red-600 mb-3">{{ $message }}</p>@enderror
                    <p class="text-xs text-gray-400">Wajib saat membuat PDF baru. Maks 10MB. Hanya PDF.</p>
                </div>

                <div id="link-fields" class="mb-4 p-3 bg-blue-50/60 border border-blue-100 rounded-lg text-xs text-blue-700">
                    <span class="font-medium">Tipe Link Only:</span> Materi ini tidak memerlukan konten artikel atau upload file lokal. Pastikan mengisi <strong>URL Sumber</strong> pada menu Pengelolaan Sumber &amp; Provenance.
                </div>

                <label class="block text-sm font-medium text-gray-700 mb-1">Status Publikasi</label>
                <select name="publication_status" class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-1 focus:border-primary focus:ring-primary" required>
                    @foreach($publicationStatuses as $s)
                        <option value="{{ $s->value }}" @selected(old('publication_status', $material->publication_status->value ?? 'draft')===$s->value)>{{ $s->value==='published'?'Published':($s->value==='draft'?'Draft':'Unpublished') }}</option>
                    @endforeach
                </select>
                @error('publication_status')<p class="text-xs text-red-600 mb-3">{{ $message }}</p>@enderror
                <div class="mb-4"></div>

                <label class="block text-sm font-medium text-gray-700 mb-1">Status Verifikasi</label>
                <select name="verification_status" class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-1 focus:border-primary focus:ring-primary" required>
                    @foreach($verificationStatuses as $s)
                        <option value="{{ $s->value }}" @selected(old('verification_status', $material->verification_status->value ?? 'draft')===$s->value)>{{ ucfirst(str_replace('_',' ',$s->value)) }}</option>
                    @endforeach
                </select>
                @error('verification_status')<p class="text-xs text-red-600 mb-3">{{ $message }}</p>@enderror
                <div class="mb-4"></div>

                <label class="block text-sm font-medium text-gray-700 mb-1">Status Validitas</label>
                <select name="validity_status" class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-1 focus:border-primary focus:ring-primary" required>
                    @foreach($validityStatuses as $s)
                        <option value="{{ $s->value }}" @selected(old('validity_status', $material->validity_status->value ?? 'unknown')===$s->value)>{{ ucfirst($s->value) }}</option>
                    @endforeach
                </select>
                @error('validity_status')<p class="text-xs text-red-600 mb-3">{{ $message }}</p>@enderror
                <div class="mb-6"></div>

                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark">{{ $editing ? 'Perbarui' : 'Simpan' }}</button>
                    <a href="{{ route('library.index') }}" class="px-4 py-2 border border-gray-200 rounded-lg text-sm text-gray-600">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
