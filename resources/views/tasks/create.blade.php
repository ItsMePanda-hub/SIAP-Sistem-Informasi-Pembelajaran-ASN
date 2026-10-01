<x-app-layout>
    <x-slot name="header">Workspace - Buat Task Baru</x-slot>

    <div class="max-w-2xl mx-auto space-y-4">
        <a href="{{ route('tasks.index') }}" class="text-xs text-gray-400 hover:text-primary transition">&larr; Kembali ke Workspace</a>

        <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
            @if ($errors->any())
                <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg text-xs space-y-1">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('tasks.store') }}" enctype="multipart/form-data" x-data="{ targetType: '{{ old('target_type', 'bidang') }}' }">
                @csrf

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Judul Task <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                           placeholder="Contoh: Penyusunan Laporan Kinerja Triwulan"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi & Instruksi <span class="text-red-500">*</span></label>
                    <textarea name="description" rows="4" required
                              placeholder="Jelaskan detail instruksi, format output yang diharapkan, dan ketentuan penting lainnya..."
                              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:border-primary focus:ring-primary">{{ old('description') }}</textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tenggat Waktu (Deadline) <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="deadline" value="{{ old('deadline') }}" required
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Target Penugasan <span class="text-red-500">*</span></label>
                    <div class="flex items-center gap-6 text-sm">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="target_type" value="bidang" x-model="targetType"
                                   class="text-primary focus:ring-primary">
                            <span>Satu Bidang / Unit Kerja</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="target_type" value="individu" x-model="targetType"
                                   class="text-primary focus:ring-primary">
                            <span>Pegawai Tertentu (Individu)</span>
                        </label>
                    </div>
                </div>

                {{-- Target Bidang --}}
                <div x-show="targetType === 'bidang'" class="mb-6">
                    @if (auth()->user()->role === 'admin')
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Bidang / Unit Kerja <span class="text-red-500">*</span></label>
                        <select name="target_unit_kerja" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                            <option value="">-- Pilih Bidang --</option>
                            @foreach ($unitKerjaList as $unit)
                                <option value="{{ $unit }}" {{ old('target_unit_kerja') === $unit ? 'selected' : '' }}>{{ $unit }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="hidden" name="target_unit_kerja" value="{{ auth()->user()->unit_kerja }}">
                        <p class="text-xs text-gray-500 bg-gray-50 p-3 rounded-lg border border-gray-100">
                            Task akan ditugaskan ke seluruh pegawai di bidang: <strong>{{ auth()->user()->unit_kerja }}</strong>
                        </p>
                    @endif
                </div>

                {{-- Target Individu --}}
                <div x-show="targetType === 'individu'" class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Pegawai <span class="text-red-500">*</span></label>
                    <p class="text-xs text-gray-400 mb-2">Pilih satu atau beberapa pegawai yang akan mengerjakan task ini.</p>
                    <div class="max-h-52 overflow-y-auto border border-gray-200 rounded-lg p-3 space-y-2 bg-gray-50/50">
                        @forelse ($users as $u)
                            <label class="flex items-center gap-3 p-1.5 hover:bg-white rounded cursor-pointer text-sm">
                                <input type="checkbox" name="user_ids[]" value="{{ $u->id }}"
                                       {{ in_array($u->id, old('user_ids', [])) ? 'checked' : '' }}
                                       class="rounded text-primary focus:ring-primary">
                                <div class="flex-1">
                                    <div class="font-medium text-gray-800">{{ $u->name }}</div>
                                    <div class="text-xs text-gray-400">{{ $u->jabatan ?? 'Pegawai' }} &bull; {{ $u->unit_kerja }}</div>
                                </div>
                            </label>
                        @empty
                            <p class="text-xs text-gray-400">Tidak ada pegawai yang tersedia.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Lampiran Berkas --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lampiran Berkas / Panduan Task (opsional)</label>
                    <p class="text-xs text-gray-500 mb-2">Format: PDF, Word, PowerPoint, Excel, ZIP, atau MP4 (maks 50MB)</p>
                    <input type="file" name="attachment" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.mp4"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('tasks.index') }}" class="px-4 py-2 border border-gray-200 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition">
                        Batal
                    </a>
                    <button type="submit" class="px-5 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark transition">
                        Simpan Task
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
