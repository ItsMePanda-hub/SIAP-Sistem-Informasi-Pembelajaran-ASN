<x-app-layout>
    <x-slot name="header">Buat pengumuman</x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
            <form method="POST" action="{{ route('announcements.store') }}">
                @csrf
                <label class="block text-sm font-medium text-gray-700 mb-1">Judul</label>
                <input type="text" name="title" value="{{ old('title') }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-4 focus:border-primary focus:ring-primary" required>

                <label class="block text-sm font-medium text-gray-700 mb-1">Isi pengumuman</label>
                <textarea name="body" rows="5"
                          class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-4 focus:border-primary focus:ring-primary" required>{{ old('body') }}</textarea>

                <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                <select name="category" class="w-full border border-gray-200 rounded-lg px-3 py-2 mb-4 focus:border-primary focus:ring-primary">
                    <option value="rutin">Info rutin</option>
                    <option value="mendesak">Perlu tindak lanjut</option>
                </select>

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

                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark">
                    Kirim pengumuman
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
