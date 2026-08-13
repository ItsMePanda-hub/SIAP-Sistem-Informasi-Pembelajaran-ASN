<x-app-layout>
    <x-slot name="header">Surat &amp; Dokumen Dinas</x-slot>

    <div class="max-w-4xl mx-auto space-y-6">
        @if (in_array(auth()->user()->role, ['atasan', 'pemilik', 'admin']))
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Unggah surat baru</h3>
                <form method="POST" action="{{ route('letters.store') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="text" name="title" placeholder="Judul surat"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-3 focus:border-primary focus:ring-primary" required>
                    <input type="text" name="nomor_surat" placeholder="Nomor surat (opsional)"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-3 focus:border-primary focus:ring-primary">
                    <select name="category" class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-3 focus:border-primary focus:ring-primary">
                        <option value="sk">SK</option>
                        <option value="nota_dinas">Nota dinas</option>
                        <option value="surat_tugas">Surat tugas</option>
                        <option value="lainnya">Lainnya</option>
                    </select>

                    <select name="visibility" class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-3 focus:border-primary focus:ring-primary" required>
                        <option value="all">Semua (Publik)</option>
                        <option value="unit">Per Unit Kerja</option>
                        <option value="role">Per Peran (Role)</option>
                    </select>

                    @if (in_array(auth()->user()->role, ['admin', 'pemilik']))
                        <select name="target_unit_kerja" class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-3 focus:border-primary focus:ring-primary">
                            <option value="">Pilih Unit Kerja (jika Per Unit)</option>
                            @foreach ($unitKerjaList as $unit)
                                <option value="{{ $unit }}">{{ $unit }}</option>
                            @endforeach
                        </select>
                    @else
                        <p class="text-xs text-gray-400 mb-3">Unit kerja target: <strong>{{ auth()->user()->unit_kerja }}</strong> (jika Per Unit)</p>
                    @endif

                    <select name="target_role" class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-3 focus:border-primary focus:ring-primary">
                        <option value="">Pilih Role (jika Per Role)</option>
                        <option value="admin">Admin</option>
                        <option value="pemilik">Pemilik</option>
                        <option value="atasan">Atasan</option>
                        <option value="pengguna">Pengguna</option>
                    </select>

                    <input type="file" name="file" accept="application/pdf" class="mb-4" required>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark">
                        Unggah
                    </button>
                </form>
            </div>
        @endif

        <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
            @forelse ($letters as $letter)
                <div class="flex justify-between items-center border-b border-gray-100 py-3">
                    <div>
                        <div class="font-semibold text-sm text-gray-800">{{ $letter->title }}</div>
                        <div class="text-xs text-gray-400">
                            {{ $letter->nomor_surat ?? '-' }} &middot; diunggah oleh {{ $letter->uploader->name }}
                            &middot; {{ $letter->created_at->translatedFormat('d M Y') }}
                        </div>
                    </div>
                    <a href="{{ route('letters.download', $letter) }}" class="px-3 py-1 border border-gray-200 rounded-lg text-sm hover:border-primary hover:text-primary-dark">
                        Unduh
                    </a>
                </div>
            @empty
                <p class="text-gray-400 text-sm">Belum ada surat yang diunggah.</p>
            @endforelse
        </div>

        <div>{{ $letters->links() }}</div>
    </div>
</x-app-layout>
