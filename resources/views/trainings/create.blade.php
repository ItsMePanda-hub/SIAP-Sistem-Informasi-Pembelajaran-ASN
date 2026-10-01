<x-app-layout>
    <x-slot name="header">Buat pelatihan</x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
            <form method="POST" action="{{ route('trainings.store') }}" enctype="multipart/form-data">
                @csrf
                <label class="block text-sm font-medium text-gray-700 mb-1">Judul pelatihan</label>
                <input type="text" name="title" value="{{ old('title') }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-4 focus:border-primary focus:ring-primary" required>

                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                <textarea name="description" rows="5"
                          class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-4 focus:border-primary focus:ring-primary">{{ old('description') }}</textarea>

                @if (in_array(auth()->user()->role, ['admin', 'pemilik']))
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kirim ke</label>
                    <select name="target_unit_kerja" class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-6 focus:border-primary focus:ring-primary">
                        <option value="">Semua bidang</option>
                        @foreach ($unitKerjaList as $unit)
                            <option value="{{ $unit }}">{{ $unit }}</option>
                        @endforeach
                    </select>
                @else
                    <p class="text-xs text-gray-400 mb-6">Akan dikirim ke bidang: <strong>{{ auth()->user()->unit_kerja }}</strong></p>
                @endif

                <label class="block text-sm font-medium text-gray-700 mb-1">Materi Pelatihan (opsional)</label>
                <p class="text-xs text-gray-500 mb-2">Format: PDF, Word, PowerPoint, Excel, ZIP, atau MP4 (maks 50MB)</p>
                <input type="file" name="materi" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.mp4"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-6 focus:border-primary focus:ring-primary">

                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark">
                    Simpan pelatihan
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
